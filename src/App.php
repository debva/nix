<?php

namespace Debva\Nix;

class App extends Core
{
    public $service;

    protected $middlewareFolderName = 'middleware';

    protected $routeFolderName = 'routes';

    protected $serviceFolderName = 'services';

    public function __construct()
    {
        ini_set('display_errors', 'Off');

        set_exception_handler(function ($e) {
            $this->handleError('Exception', $e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e->getTrace());
        });

        set_error_handler(function ($errno, $message, $file, $line) {
            $this->handleError('Error', $message, 500, $file, $line);
        });

        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error !== null) {
                $this->handleError('Fatal Error', $error['message'], 500, $error['file'], $error['line']);
            }
        });

        parent::__construct();

        define('FRAMEWORK_VERSION', '1.5.0');
        define('APP_VERSION', $this->env('APP_VERSION', '1.0.0'));

        $this->service = $this->service();
    }

    public function __invoke()
    {
        if (in_array($this->sapiName, ['cli'])) {
            $console = new Console;
            return $console();
        }
        
        $path = array_filter(explode('/', $this->requestPath));

        if (_startsWith($queue = reset($path), '___queue')) {
            if (_endsWith($queue, $this->env('QUEUE_ID', 'nix'))) {
                $request = $this->request(['username', 'password']);
                if (
                    $request['username'] == $this->env('QUEUE_USER', '') &&
                    $request['password'] == $this->env('QUEUE_PASSWORD', '')
                ) {
                    $queue = new Queue;
                    return $this->response($queue());
                }
            }
        }

        $basePath = implode(DIRECTORY_SEPARATOR, array_merge([$this->rootPath, 'app', $this->routeFolderName], $path));
        $actionPath = implode('.', [$basePath, 'php']);

        if (!file_exists($actionPath)) {
            $actionPath = implode(DIRECTORY_SEPARATOR, [$basePath, 'index.php']);

            if (!file_exists($actionPath) && empty($path)) {
                $actionPath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', $this->routeFolderName, 'welcome.php']);
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
            $reflection = new \ReflectionFunction($action);
            $parameter = array_values($this->request());
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
        if ($this->env('APP_DEBUG', true)) {
            $this->response([
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
            $this->response([
                'code'      => 500,
                'message'   => $message
            ], 500, true);
        }

        exit;
    }

    private function middleware(\Closure $action)
    {
        $middlewares = [];
        $middlewarePath = implode(DIRECTORY_SEPARATOR, [$this->rootPath, 'app', $this->middlewareFolderName]);

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
        $servicePath = implode(DIRECTORY_SEPARATOR, [$this->rootPath, 'app', $this->serviceFolderName]);

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
