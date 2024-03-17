<?php

namespace Debva\Nix;

class App extends Bridge
{
    protected $requestPath;

    protected $requestMethod;

    protected $storage;

    protected $debug;

    protected $middlewarePath = 'app/middleware';

    protected $routePath = 'app/routes';

    protected $middlewareCache = 'cache/middleware.json';

    protected $routeCache = 'cache/routes.json';

    protected $errors = [];

    protected $httpMethod = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct()
    {
        error_reporting(0);

        set_exception_handler(function ($e) {
            $this->errors[] = [
                'type'          => 'Exception',
                'statusCode'    => $e->getCode(),
                'message'       => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
                'trace'         => $e->getTrace()
            ];
        });

        set_error_handler(function ($errno, $message, $file, $line) {
            $this->errors[] = [
                'type'          => 'Error',
                'statusCode'    => $errno,
                'message'       => $message,
                'file'          => $file,
                'line'          => $line,
                'trace'         => debug_backtrace()
            ];
        });

        register_shutdown_function(function () {
            $this->handleError();
        });

        parent::__construct();

        if (!$this->requestPath || !$this->requestMethod) {
            $route = nix('route');
            $this->requestPath = $route->requestPath;
            $this->requestMethod = $route->requestMethod;
        }

        $this->storage = storage();

        $this->debug = env('APP_DEBUG', false);
    }

    public function __invoke()
    {
        $routes = [];

        if (!$this->debug) $routes = json_decode($this->storage->get($this->routeCache));

        if ($this->debug || (!$this->debug && !$routes)) {
            $basePath = $this->storage->basePath($this->routePath);

            $files = $this->storage->scan($this->routePath, true);

            $files = array_filter($files, function ($file) {
                return pathinfo($file, PATHINFO_EXTENSION) === 'php';
            });

            $routes = array_map(function ($action) use ($basePath) {
                $name = pathinfo($action, PATHINFO_FILENAME);
                $fullPath = str_replace([$basePath, DIRECTORY_SEPARATOR], ['', '/'], $action);
                $methods = implode('|', array_map('strtolower', $this->httpMethod));

                preg_match("/^__({$methods})(_|$)([^.]+)?/", $name, $matches);

                $name = strtolower(empty($matches) ? $name : (isset($matches[3]) ? $matches[3] : 'index'));
                $path = implode('/', array_filter([trim(dirname($fullPath), '.'), $name === 'index' ? '' : $name]));
                $path = preg_replace('/\/+/', '/', $path);
                $methods = isset($matches[1]) ? [strtoupper($matches[1])] : $this->httpMethod;

                $search = ['(', ')', '-', ' '];
                $replace = ['', '', '_', '_'];

                $name = trim(implode('-', array_merge(
                    array_filter(explode('/', str_replace($search, $replace, dirname($fullPath)))),
                    [str_replace($search, $replace, $name)]
                )), '-_');

                $params = [];
                if (preg_match_all('/\(([^)]+)\)/', $path, $matches)) {
                    $params = array_combine((array) $matches[1], array_map(function ($index) {
                        return $index;
                    }, range(1, count((array) $matches[1]))));
                }

                return [
                    'path'      => $path,
                    'name'      => $name,
                    'action'    => $action,
                    'params'    => $params,
                    'methods'   => $methods
                ];
            }, $files);

            if (!$this->debug) $this->storage->save($this->routeCache, json_encode($routes), true);
            $routes = json_decode(json_encode($routes));
        } else $routes = json_decode($this->storage->get($this->routeCache));

        $request = null;

        foreach ($routes as $route) {
            if (preg_match_all('/\(([^)]+)\)/', $route->path, $matches)) {
                foreach ((array) $matches[1] as $param) {
                    $route->path = str_replace("({$param})", '([\w\s%\~\-\_]+)', $route->path);
                }
            }

            $route->path = str_replace('/', '\/', $route->path);;

            if (preg_match("/^{$route->path}$/", ($path = "/{$this->requestPath}"), $matches) && in_array($this->requestMethod, $route->methods)) {
                $request = json_decode(json_encode([
                    'name'      => $route->name,
                    'action'    => $route->action,
                    'path'      => $path,
                    'fullPath'  => trim(implode('?', [$path, http_build_query($_GET)]), '?'),
                    'methods'   => $route->methods,
                    'params'    => $route->params,
                    'query'     => $_GET,
                    'body'      => array_merge($_POST, empty($body = json_decode(file_get_contents("php://input"), true)) ? [] : $body)
                ]));

                if (isset($request->params)) {
                    foreach ($request->params as $param => $key) {
                        $request->params->$param = is_string($matches[$key])
                            ? urldecode((string) $matches[$key]) : $matches[$key];
                    }
                }
            }
        }

        if (!$request) throw new \Exception('Route not found', 404);

        $action = $this->middleware($request, function () use ($request) {
            if (!file_exists($request->action)) throw new \Exception('File action not found', 500);

            $method = require_once($request->action);
            $class = new \stdClass;
            $class->method = $method;

            if (is_callable($method)) {
                $reflection = new \ReflectionFunction($method);
                $params = $reflection->getParameters();

                $args = [];
                foreach ($request->params as $value) $args[] = $value;

                $args = array_merge($args, count($params) > count($args) ? array_fill(count($args), count($params), null) : []);
                $class->args = $args;
            }

            return $class;
        });

        $action = $action instanceof \Closure ? $action() : $action;
        $action = $action instanceof \stdClass
            ? (property_exists($action, 'args') ? ($action->method)(...$action->args) : $action->method)
            : $action;

        if (empty($this->errors)) exit(print(response($action)->buffer));

        exit(0);
    }

    protected function middleware(\stdClass $request, \Closure $action)
    {
        if (!$this->debug) $middlewares = json_decode($this->storage->get($this->middlewareCache), true);

        if ($this->debug || (!$this->debug && !$middlewares)) {
            $middlewares = $this->storage->scan($this->storage->basePath($this->middlewarePath));

            $middlewares = array_filter($middlewares, function ($middleware) {
                return pathinfo($middleware, PATHINFO_EXTENSION) === 'php';
            });

            if (!$this->debug) $this->storage->save($this->middlewareCache, json_encode($middlewares), true);
        } else $middlewares = json_decode($this->storage->get($this->middlewareCache));

        if (!$middlewares) return $action;

        $next = function ($request, $middlewares) use (&$next, $action) {
            if (!$middlewares) return $action;

            $middleware = array_shift($middlewares);
            $middleware = require_once($middleware);
            $next = $middleware($next, $request);

            return $next instanceof \Closure ? $next($request, $middlewares) : $next;
        };

        return $next($request, $middlewares);
    }

    protected function handleError()
    {
        if (!empty($this->errors)) {
            $response = response([
                'statusCode'    => 500,
                'message'       => !empty($this->errors) ? $this->errors[0]['message'] : 'Internal Server Error',
            ], 500);

            if (env('APP_DEBUG', false)) {
                $response = response([
                    'os'            => PHP_OS,
                    'version'       => 'PHP ' . PHP_VERSION,
                    'statusCode'    => !empty($this->errors) ? $this->errors[0]['statusCode'] : 0,
                    'message'       => !empty($this->errors) ? $this->errors[0]['message'] : null,
                    'file'          => !empty($this->errors) ? $this->errors[0]['file'] : null,
                    'line'          => !empty($this->errors) ? $this->errors[0]['line'] : null,
                    'errors'        => $this->errors
                ], !empty($this->errors) ? $this->errors[0]['statusCode'] : 500);
            }

            exit(print($response->buffer));
        }

        return true;
    }
}
