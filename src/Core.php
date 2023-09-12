<?php

namespace Debva\Nix;

abstract class Core
{
    public $http;

    public $datatable;

    protected $path;

    protected $requestPath;

    protected $loadtime;

    public function __construct()
    {
        $this->loadtime = microtime(true);

        $this->http = new Http;
    }

    public function env($key, $default = null)
    {
        $value = getenv($key);

        if (!$value || (!$value && $default !== null)) $value = $default;
        if (is_string($value) && strtolower($value) !== 'null') {
            $decodedValue = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) $value = $decodedValue;
        }

        return $value;
    }

    public function route($route = null, $query = [])
    {
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
        $query = !empty($query) ? (strpos($route, '?') !== false ? '&' : '?') . http_build_query($query) : '';
        $route = $route ? trim($route, '\/') : $this->requestPath;
        return trim(join('/', [$host, $this->path, $route . $query]), '\/');
    }

    public function request($keys = null, $default = null)
    {
        $request = $_REQUEST;

        if (!empty($_FILES)) {
            $request = array_merge($request, $_FILES);
        }

        if (!empty($body = file_get_contents("php://input")) and !is_null(json_decode($body, true))) {
            $request = array_merge($request, json_decode($body, true));
        }

        if (!is_null($keys)) {
            if (is_array($keys)) {
                $result = array_intersect_key($request, array_flip($keys));
                return empty($result) ? $default : $result;
            }

            return isset($request[$keys])
                ? $request[$keys]
                : (!is_null($default) ? $default : null);
        }

        return $request;
    }

    public function validate()
    {
    }

    public function response($data, $code = 200, $gzip = false, $sanitize = false, $except_sanitize = [])
    {
        if (is_int($code)) http_response_code($code);
        header("Access-Control-Allow-Origin: {$this->env('ACCESS_CONTROL_ALLOW_ORIGIN', '*')}");
        header("Access-Control-Allow-Methods: {$this->env('ACCESS_CONTROL_ALLOW_METHODS', 'GET, POST, DELETE, OPTIONS')}");
        header("Access-Control-Allow-Headers: {$this->env('ACCESS_CONTROL_ALLOW_HEADERS', '*')}");
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
            header("HTTP/1.1 200 OK");
            die();
        }

        if ($sanitize) {
            foreach ($data as $key => $value) {
                if (!in_array($key, $except_sanitize)) {
                    $data[$key] = preg_replace(['/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s', '/<!--(.|\s)*?-->/'], ['>', '<', '\\1', ''], $value);
                }
            }
        }

        if ($gzip) ini_set('zlib.output_compression', 'on');

        print(json_encode($data));
        return __CLASS__;
    }

    public function database($connection = null)
    {
        if ($connection) $connection = '_' . strtoupper($connection);

        $dsn = [
            getenv("DB{$connection}_CONNECTION"),
            getenv("DB{$connection}_HOST"),
            getenv("DB{$connection}_PORT"),
            getenv("DB{$connection}_DATABASE"),
            getenv("DB{$connection}_USER"),
            getenv("DB{$connection}_PASSWORD"),
        ];

        return new Database(...$dsn);
    }

    public function query($query, $bindings = [], $connection = null)
    {
        return $this->database($connection)->query($query, $bindings);
    }

    public function datatable($data)
    {
        return new Datatable($data);
    }

    public function telegram($token = null)
    {
        return new Telegram($token ? $token : $this->env('TELEGRAM_TOKEN', ''));
    }

    public function dump(...$vars)
    {
        var_dump(...$vars);
        exit;
    }

    public function loadtime()
    {
        return microtime(true) - $this->loadtime;
    }
}
