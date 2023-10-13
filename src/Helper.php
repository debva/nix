<?php

use Debva\Nix\Nix;

if (!function_exists('dd')) {
    function dd(...$vars)
    {
        var_dump(...(!empty($vars) ? $vars : [null]));
        exit;
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

if (!function_exists('nix')) {
    function nix($class, ...$args)
    {
        if (!isset($GLOBALS['_NIX_APP'])) {
            $GLOBALS['_NIX_APP'] = new Nix;
        }

        return $GLOBALS['_NIX_APP']($class, ...$args);
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

if (!function_exists('bpjs')) {
    function bpjs($options, $isProduction = false)
    {
        return nix('bpjs', $options, $isProduction);
    }
}

if (!function_exists('db')) {
    function db($connection)
    {
        return nix('db', $connection);
    }
}

if (!function_exists('query')) {
    function query($query, $bindings = [], $connection = null)
    {
        return db($connection)->query($query, $bindings);
    }
}

if (!function_exists('transaction')) {
    function transaction(\Closure $transaction, $connection = null)
    {
        return db($connection)->transaction($transaction);
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

if (!function_exists('userAgent')) {
    function userAgent($uagent)
    {
        $userAgent = nix('userAgent');
        return $userAgent($uagent);
    }
}

if (!function_exists('validate')) {
    function validate($rules, $options = [])
    {
        $validate = nix('validate');
        return $validate($rules, $options);
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
