<?php

namespace Debva\Nix;

class App extends Bridge
{
    protected $appPath = 'app';

    protected $middlewarePath = 'middleware';

    protected $routePath = 'routes';

    protected $servicePath = 'services';

    protected $requestPath;

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

        if (!$this->requestPath) {
            $this->requestPath = nix('route')->requestPath;
        }
    }

    public function __invoke()
    {
        $requestPath = array_filter(explode('/', $this->requestPath));

        if (startsWith($queue = reset($requestPath), '___queue')) {
            if (endsWith($queue, env('QUEUE_ID', 'nix'))) {
                $request = request(['username', 'password']);
                if (
                    $request['username'] == env('QUEUE_USER', '') &&
                    $request['password'] == env('QUEUE_PASSWORD', '')
                ) {
                    $queue = nix('queue');
                    return response($queue());
                }
            }
        }

        $basePath = implode(DIRECTORY_SEPARATOR, [basePath(), $this->appPath, $this->routePath, $this->requestPath]);
        $actionPath = implode('.', [$basePath, 'php']);

        if (!file_exists($actionPath)) {
            $actionPath = implode(DIRECTORY_SEPARATOR, [$basePath, 'index.php']);

            if (!file_exists($actionPath) && empty($requestPath)) {
                $actionPath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', $this->routePath, 'welcome.php']);
                $action = require_once($actionPath);
                return response($action());
            }

            if (!file_exists($actionPath)) {
                throw new \Exception('Route not found!', 404);
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

    public function __get($name)
    {
        if ($name === 'service') {
            return $this->service();
        }
    }

    private function handleError($type, $message, $code, $file, $line, $trace = [])
    {
        if (env('APP_DEBUG', true)) {
            response([
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
            response([
                'code'      => 500,
                'message'   => $message
            ], 500, true);
        }

        exit;
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

            if (!$middleware instanceof \Closure) $action = $middleware;
            else $action = $action();

            if (is_array($action)) return response($action);
            return print($action);
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
