<?php

namespace Debva\Nix;

class App extends Core
{
    public $service;

    const FRAMEWORK_VERSION = '1.5.0';

    public function __construct()
    {
        parent::__construct();

        set_exception_handler(function ($exception) {
            if ($this->env('APP_DEBUG', true)) {
                $this->response([
                    'os'        => PHP_OS,
                    'version'   => 'PHP ' . PHP_VERSION,
                    'message'   => $exception->getMessage(),
                    'code'      => $exception->getCode(),
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                    'trace'     => $exception->getTrace()
                ], $exception->getCode(), true);
            } else {
                $this->response([
                    'code'      => 500,
                    'message'   => $exception->getMessage()
                ], 500, true);
            }

            exit;
        });

        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new \ErrorException($errstr, 500, $errno, $errfile, $errline);
        });

        $this->service = $this->service();
    }

    public function __invoke()
    {
        $path = array_filter(explode('/', $this->requestPath));
        $basePath = implode(DIRECTORY_SEPARATOR, array_merge([getcwd(), '..', 'app', 'routes'], $path));
        $actionPath = implode('.', [$basePath, 'php']);

        if (!file_exists($actionPath)) {
            $actionPath = implode(DIRECTORY_SEPARATOR, [$basePath, 'index.php']);

            if (!file_exists($actionPath) && empty($path)) {
                $actionPath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'routes', 'welcome.php']);
                $action = require_once($actionPath);
                return $this->response($action());
            }

            if (!file_exists($actionPath)) {
                throw new \Exception('Route not found!', 404);
            }
        }

        $action = require_once($actionPath);

        if (!is_callable($action)) {
            throw new \Exception('Route is not callable!', 500);
        }

        return $this->middleware(function () use ($action) {
            return $action(...array_values($this->request()));
        });
    }

    private function middleware(\Closure $action)
    {
        $middlewares = [];
        $middlewarePath = implode(DIRECTORY_SEPARATOR, [getcwd(), '..', 'app', 'middlewares']);

        if (!is_dir($middlewarePath)) return $action();
        $middlewares = array_filter(glob(implode(DIRECTORY_SEPARATOR, [$middlewarePath, '*.php'])), 'file_exists');

        $next = function ($middlewares) use (&$next, &$middleware, $action) {
            if (!empty($middlewares)) {
                $file = array_shift($middlewares);
                if (file_exists($file)) {
                    $middleware = require_once($file);
                    $middleware = $middleware($next);

                    if ($middleware instanceof \Closure && !empty($middlewares)) {
                        return $next($middlewares);
                    }
                }
            }

            if (!$middleware instanceof \Closure) $action = $middleware;
            else $action = $action();

            if (is_array($action)) return $this->response($action);
            return print($action);
        };

        return $next($middlewares);
    }

    private function service()
    {
        $class = new Anonymous;
        $services = [];
        $servicePath = implode(DIRECTORY_SEPARATOR, [getcwd(), '..', 'app', 'services']);

        if (is_dir($servicePath)) {
            $services = array_filter(glob(implode(DIRECTORY_SEPARATOR, [$servicePath, '*.php'])), 'file_exists');
        }

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
