<?php

namespace Debva\Nix;

class App extends Bridge
{
    protected $appPath = 'app';

    protected $middlewarePath = 'middleware';

    protected $routePath = 'routes';

    protected $servicePath = 'services';

    protected $requestPath;

    protected $requestMethod;

    protected $verbose = true;

    protected $httpMethod = ['__GET', '__POST', '__PUT', '__PATCH', '__DELETE'];

    public function __construct()
    {
        error_reporting(0);

        ini_set('display_errors', 'Off');

        set_exception_handler(function ($e) {
            if ($this->verbose) {
                $this->verbose = false;
                $this->handleError('Exception', $e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e->getTrace());
            }

            exit(0);
        });

        set_error_handler(function ($errno, $message, $file, $line) {
            if ($this->verbose) {
                $this->verbose = false;
                $this->handleError('Error', $message, 500, $file, $line);
            }

            exit(0);
        });

        register_shutdown_function(function () {
            if ($this->verbose) {
                $this->verbose = false;
                $error = error_get_last();

                if ($error !== null && env('APP_DEBUG')) {
                    $this->handleError('Fatal Error', $error['message'], 500, $error['file'], $error['line']);
                }
            }

            exit(0);
        });

        parent::__construct();

        if (!$this->requestPath) {
            $route = nix('route');
            $this->requestPath = $route->requestPath;
            $this->requestMethod = $route->requestMethod;
        }
    }

    public function __get($name)
    {
        if ($name === 'service') {
            return $this->service();
        }
    }

    public function __invoke()
    {
        $requestPath = array_filter(explode('/', $this->requestPath));

        if (
            in_array(strtoupper(end($requestPath)), array_merge($this->httpMethod, ['INDEX'])) ||
            preg_match('/^(' . implode('|', $this->httpMethod) . ')/i', end($requestPath))
        ) {
            throw new \Exception('Route not found!', 404);
        }

        $basePath = rtrim(implode(DIRECTORY_SEPARATOR, [basePath(), $this->appPath, $this->routePath, implode(DIRECTORY_SEPARATOR, $requestPath)]), '\/');
        $actionPath = implode('.', [$basePath, 'php']);

        if (!file_exists($actionPath)) {
            $actionPath = implode(DIRECTORY_SEPARATOR, [$basePath, 'index.php']);

            if (!file_exists($actionPath) && empty($requestPath)) {
                $actionPath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', $this->routePath, 'welcome.php']);
                $action = require_once($actionPath);

                print(response($action()));

                exit(1);
            }

            if (!file_exists($actionPath)) {
                if (!in_array('__' . strtoupper($this->requestMethod), $this->httpMethod)) {
                    throw new \Exception('Route not found!', 404);
                }

                $actionHttpMethod = '__' . strtolower($this->requestMethod);
                $matchActionPath = [
                    "{$actionHttpMethod}_" . end($requestPath) . '.php',
                    "{$actionHttpMethod}_index.php",
                    "{$actionHttpMethod}.php"
                ];

                array_walk(
                    $matchActionPath,
                    function ($file, $index) use (&$matchActionPath, $basePath) {
                        $matchActionPath[$index] = implode(DIRECTORY_SEPARATOR, [$index ? $basePath : dirname($basePath), $file]);
                    }
                );

                $actionPath = array_filter($matchActionPath, 'file_exists');
                $actionPath = reset($actionPath);

                if ($actionPath === false) {
                    throw new \Exception('Route not found!', 404);
                }
            }
        }

        $action = require_once($actionPath);

        if (!is_callable($action)) {
            throw new \Exception('Route is not valid!', 500);
        }

        return $this->middleware(function () use ($action) {
            $reflection = new \ReflectionFunction($action);
            $parameter = array_values(request());
            $parameters = $reflection->getParameters();

            $arguments = [];
            foreach ($parameters as $index => $param) {
                $arguments[$index] = isset($parameter[$index]) ? $arguments[$index] = $parameter[$index] : null;
                if (empty($arguments[$index]) && $param->isDefaultValueAvailable()) $arguments[$index] = $param->getDefaultValue();
            }

            return $action(...$arguments);
        });
    }

    private function handleError($type, $message, $code, $file, $line, $trace = [])
    {
        if (env('APP_DEBUG', true)) {
            $response = response([
                'os'        => PHP_OS,
                'version'   => 'PHP ' . PHP_VERSION,
                'type'      => $type,
                'message'   => $message,
                'code'      => $code,
                'file'      => $file,
                'line'      => $line,
                'trace'     => $trace
            ], $code, true);
        } else {
            $response = response([
                'code'      => $code,
                'message'   => $message,
            ], $code, true);
        }

        print($response);

        exit(0);
    }

    private function middleware(\Closure $action)
    {
        $middlewares = [];
        $middlewarePath = implode(DIRECTORY_SEPARATOR, [basePath(), $this->appPath, $this->middlewarePath]);

        if (!is_dir($middlewarePath)) return $action();
        $middlewares = array_filter(glob(implode(DIRECTORY_SEPARATOR, [$middlewarePath, '*.php'])), 'file_exists');

        $next = function ($middlewares) use (&$next, &$middleware, $action) {
            if (!empty($middlewares)) {
                $file = array_shift($middlewares);
                $middleware = require_once($file);
                $middleware = $middleware($next);

                if ($middleware instanceof \Closure && !empty($middlewares)) {
                    return $next($middlewares);
                }
            }

            if (!($middleware instanceof \Closure)) $action = $middleware;
            else $action = $action();

            $this->verbose = false;
            print(is_array($action) ? response($action) : $action);

            exit(1);
        };

        return $next($middlewares);
    }

    private function service()
    {
        $services = [];
        $servicePath = implode(DIRECTORY_SEPARATOR, [basePath(), $this->appPath, $this->servicePath]);

        if (is_dir($servicePath)) {
            $services = array_filter(glob(implode(DIRECTORY_SEPARATOR, [$servicePath, '*.php'])), 'file_exists');
        }

        $class = nix('anonymous');

        foreach ($services as $service) {
            $serviceClass = basename($service, '.php');
            $name = strtolower(preg_replace('/([a-z])([A-Z])|-/', '$1_$2', $serviceClass));
            $class->macro($name, function ($self, ...$args) use ($service, $serviceClass) {
                require_once($service);
                return new $serviceClass(...$args);
            });
        }

        return $class;
    }
}
