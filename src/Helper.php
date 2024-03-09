<?php

use Debva\Nix\Nix;

if (!function_exists('dd')) {
    function dd(...$vars)
    {
        var_dump(...(!empty($vars) ? $vars : [null]));
        exit;
    }
}

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

if (!function_exists('nix')) {
    function nix($class, ...$args)
    {
        if (!isset($GLOBALS['_NIX_APP'])) {
            $GLOBALS['_NIX_APP'] = new Nix;
        }

        return $GLOBALS['_NIX_APP']($class, ...$args);
    }
}

if (!function_exists('env')) {
    function env($key, $default = null)
    {
        $value = getenv($key);

        if (!$value || (!$value && $default !== null)) {
            $value = $default;
        }

        if (is_string($value) && strtolower($value) !== 'null' && trim($value) !== '') {
            $decodedValue = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decodedValue;
            }
        }

        return $value;
    }
}

if (!function_exists('loadEnv')) {
    function loadEnv($env)
    {
        $envPath = storage()->basePath($env);

        if (!file_exists($envPath)) {
            return false;
        }

        $lines = preg_split('/\r\n|\r|\n/', file_get_contents($envPath));

        foreach ($lines as $line) {
            $line = trim($line);

            if (!$line || strpos($line, '#') === 0) continue;
            list($key, $value) = explode('=', $line, 2);

            $key = trim($key);
            $value = trim($value, "\"");

            putenv(sprintf('%s=%s', $key, $value));
            $_ENV[$key] = $value;
        }

        return true;
    }
}

if (!function_exists('service')) {
    function service()
    {
        $services = [];
        $servicePath = implode(DIRECTORY_SEPARATOR, [basePath(), 'app', 'services']);

        if (is_dir($servicePath)) {
            $services = array_filter(glob(implode(DIRECTORY_SEPARATOR, [$servicePath, '*.php'])), 'file_exists');
        }

        $class = nix('anonymous');

        foreach ($services as $service) {
            $serviceClass = basename($service, '.php');

            if (ctype_upper($serviceClass)) $name = strtolower($serviceClass);
            else $name = lcfirst(implode('', array_map('ucfirst', preg_split('/(?=[A-Z])/', $serviceClass, -1, PREG_SPLIT_NO_EMPTY))));

            $class->macro($name, function ($self, ...$args) use ($service, $serviceClass) {
                require_once($service);
                $class = new $serviceClass(...$args);
                return method_exists($class, '__invoke') ? $class(...$args) : $class;
            });
        }

        return $class;
    }
}

if (!function_exists('auth')) {
    function auth()
    {
        return nix('auth');
    }
}

if (!function_exists('secret')) {
    function secret()
    {
        return nix('crypt');
    }
}

if (!function_exists('http')) {
    function http()
    {
        return nix('http');
    }
}

if (!function_exists('session')) {
    function session($name = null)
    {
        return nix('session', $name);
    }
}

if (!function_exists('storage')) {
    function storage()
    {
        return nix('storage');
    }
}

if (!function_exists('basePath')) {
    function basePath($path = null)
    {
        return storage()->basePath($path);
    }
}

if (!function_exists('task')) {
    function task()
    {
        return null;
    }
}

if (!function_exists('ext')) {
    function ext()
    {
        return nix('ext');
    }
}

if (!function_exists('db')) {
    function db($connection = null)
    {
        $db = nix('db');
        return $db($connection);
    }
}

if (!function_exists('telegram')) {
    function telegram($token = null)
    {
        return nix('telegram', $token);
    }
}

if (!function_exists('datatable')) {
    function datatable($data)
    {
        return nix('datatable', $data);
    }
}

if (!function_exists('request')) {
    function request($key = null, $default = null)
    {
        $request = nix('request');
        return $request($key, $default);
    }
}

if (!function_exists('response')) {
    function response($data, $code = 200, $gzip = true, $sanitize = false, $except_sanitize = [])
    {
        $response = nix('response');
        return $response($data, $code, $gzip, $sanitize, $except_sanitize);
    }
}

if (!function_exists('route')) {
    function route($path = null)
    {
        $route = nix('route');
        return $route($path);
    }
}

if (!function_exists('guid')) {
    function guid()
    {
        return nix('uuid');
    }
}

if (!function_exists('isMethod')) {
    function isMethod(...$method)
    {
        $method = (count($method) < 1) ? null : array_map('strtoupper', $method);
        $requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);

        return is_null($method)
            ? $requestMethod
            : in_array($requestMethod, $method);
    }
}

if (!function_exists('validate')) {
    function validate($rules, $options = [])
    {
        $validate = nix('validator');
        return $validate($rules, $options);
    }
}

if (!function_exists('document')) {
    function document()
    {
        return nix('document');
    }
}

if (!function_exists('strContains')) {
    function strContains($haystack, $needle, $caseInsensitive = false)
    {
        if ($caseInsensitive) {
            return stripos($haystack, $needle) !== false;
        }

        return strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('startsWith')) {
    function startsWith($haystack, $needle, $caseInsensitive = false)
    {
        if ($caseInsensitive) {
            $haystack = strtolower($haystack);
            $needle = strtolower($needle);
        }

        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

if (!function_exists('endsWith')) {
    function endsWith($haystack, $needle, $caseInsensitive = false)
    {
        if ($caseInsensitive) {
            $haystack = strtolower($haystack);
            $needle = strtolower($needle);
        }

        return substr($haystack, -strlen($needle)) === $needle;
    }
}
