<?php

namespace Debva\Nix;

class App extends Core
{
    protected $path;

    protected $requestPath;

    public $service;

    public $code = 200;

    public function __construct()
    {
        parent::__construct();

        $envpath = join(DIRECTORY_SEPARATOR, [getcwd(), '..', '.env']);
        if (file_exists($envpath)) {
            $env = file_get_contents($envpath);
            $lines = explode(PHP_EOL, $env);

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
                ]);
            } else {
                $this->response([
                    'code'      => 500,
                    'message'   => $exception->getMessage()
                ]);
            }

            exit;
        });

        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new \ErrorException($errstr, 500, $errno, $errfile, $errline);
        });

        date_default_timezone_set($this->env('DATE_TIMEZONE', 'Asia/Jakarta'));

        define('FRAMEWORK_VERSION', 'v1.0.0');
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
                $actionPath = join('.', [join(DIRECTORY_SEPARATOR, array_merge([getcwd(), '..', 'server', 'api'], $path)), 'php']);

                if (!file_exists($actionPath)) throw new \Exception('API not found!', 404);

                $action = require_once($actionPath);

                if (!is_callable($action)) throw new \Exception('API is not callable!', 500);

                $response = $action(...array_values($this->request()));

                if (is_array($response)) return $this->response($response, $this->code);
                return print(strval($response));
            });
        }
    }


    private function middleware(\Closure $action)
    {
        $middlewares = [];
        $middlewarePath = join(DIRECTORY_SEPARATOR, [getcwd(), '..', 'server', 'middleware']);

        if (!is_dir($middlewarePath)) return $action();

        $middlewares = glob(join(DIRECTORY_SEPARATOR, [$middlewarePath, '*.php']));
        $middlewares = array_filter($middlewares, 'file_exists');

        foreach ($middlewares as $index => $middleware) {
            $middleware = require_once($middleware);
            $next = isset($middlewares[$index + 1]) ? $middlewares[$index + 1] : $action;
            return $middleware($this->request(), $next);
        }
    }

    private function service()
    {
        $class = new Anonymous;
        $services = [];
        $servicePath = join(DIRECTORY_SEPARATOR, [getcwd(), '..', 'server', 'service']);

        if (is_dir($servicePath)) {
            $services = glob(join(DIRECTORY_SEPARATOR, [$servicePath, '*.php']));
            $services = array_filter($services, 'file_exists');
        }

        foreach ($services as $service) {
            require_once($service);
            $name = strtolower(preg_replace('/([a-z])([A-Z])|-/', '$1_$2', basename($service, '.php')));
            $serviceClassName = basename($service, '.php');
            $class->macro($name, function () use ($serviceClassName) {
                return new $serviceClassName;
            });
        }

        return $class;
    }
}
