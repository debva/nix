<?php

namespace Debva\Nix;

class App extends Core
{
    public $service;

    public function __construct()
    {
        parent::__construct();

        $envpath = implode(DIRECTORY_SEPARATOR, [getcwd(), '..', '.env']);

        if (file_exists($envpath)) {
            $env = file_get_contents($envpath);
            $lines = preg_split('/\r\n|\r|\n/', $env);

            foreach ($lines as $line) {
                $line = trim($line);

                if (!$line || strpos($line, '#') === 0) continue;
                list($name, $value) = explode('=', $line, 2);

                $name = trim($name);
                $value = trim($value, "\"");

                if (!array_key_exists($name, $_ENV) && !array_key_exists($name, $_SERVER)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                }
            }
        } else {
            $defaultenvpath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '.env.example']);
            copy($defaultenvpath, $envpath);
        }

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

        date_default_timezone_set($this->env('DATE_TIMEZONE', 'Asia/Jakarta'));

        define('FRAMEWORK_VERSION', '1.0.0');
        define('APP_VERSION', $this->env('APP_VERSION', '1.0.0'));

        $requestPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $this->path = trim($this->env('APP_PATH'), '/');
        $this->requestPath = trim(str_replace($this->path, '', $requestPath), '/');

        $this->service = $this->service();
    }

    public function __invoke()
    {
        $path = explode('/', $this->requestPath);

        if (reset($path) === 'api') {
            return $this->middleware(function () use ($path) {
                array_shift($path);
                $actionPath = implode('.', [implode(DIRECTORY_SEPARATOR, array_merge([getcwd(), '..', 'server', 'api'], $path)), 'php']);

                if (!file_exists($actionPath)) throw new \Exception('API not found!', 404);

                $action = require_once($actionPath);

                if (!is_callable($action)) throw new \Exception('API is not callable!', 500);

                $response = $action(...array_values($this->request()));

                if ($response === get_parent_class(__CLASS__)) return $response;
                else return print($response);
            });
        }

        throw new \Exception('Invalid API', 400);
    }


    private function middleware(\Closure $action)
    {
        $middlewares = [];
        $middlewarePath = implode(DIRECTORY_SEPARATOR, [getcwd(), '..', 'server', 'middleware']);

        if (!is_dir($middlewarePath)) return $action();

        $middlewares = glob(implode(DIRECTORY_SEPARATOR, [$middlewarePath, '*.php']));
        $middlewares = array_filter($middlewares, 'file_exists');

        foreach ($middlewares as $index => $middleware) {
            $middleware = require_once($middleware);

            if (!is_callable($middleware)) throw new \Exception('Middleware is not callable!', 500);

            $next = isset($middlewares[$index + 1]) ? $middlewares[$index + 1] : $action;
            return $middleware($next);
        }

        return $action();
    }

    private function service()
    {
        $class = new Anonymous;
        $services = [];
        $servicePath = implode(DIRECTORY_SEPARATOR, [getcwd(), '..', 'server', 'service']);

        if (is_dir($servicePath)) {
            $services = glob(implode(DIRECTORY_SEPARATOR, [$servicePath, '*.php']));
            $services = array_filter($services, 'file_exists');
        }

        foreach ($services as $service) {
            require_once($service);
            $name = strtolower(preg_replace('/([a-z])([A-Z])|-/', '$1_$2', basename($service, '.php')));
            $serviceClassName = basename($service, '.php');
            $class->macro($name, function (...$args) use ($serviceClassName) {
                return new $serviceClassName(...$args);
            });
        }

        return $class;
    }
}
