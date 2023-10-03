<?php

namespace Debva\Nix;

abstract class Core extends Environment
{
    public $http;

    public $auth;

    public $queue;

    public $datatable;

    protected $path;

    protected $requestPath;

    protected $loadtime;

    protected $sapiName;

    public function __construct()
    {
        $this->loadtime = microtime(true);
        
        $this->sapiName = php_sapi_name();

        parent::__construct();

        if (!in_array($this->sapiName, ['cli'])) {
            date_default_timezone_set($this->env('DATE_TIMEZONE', 'Asia/Jakarta'));
    
            $requestPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
            $this->path = $this->env('APP_PATH', '') ? trim($this->env('APP_PATH', ''), '/') : '';
            $this->requestPath = trim(str_replace($this->path, '', $requestPath), '/');
    
            $this->http = new Http;
    
            $this->crypt = new Cryptography;
    
            $this->auth = new Authentication;
    
            $this->queue = new Queue;
        }
    }

    public function env($key, $default = null)
    {
        $value = getenv($key);

        if (!$value || (!$value && $default !== null)) $value = $default;
        if (is_string($value) && strtolower($value) !== 'null' && trim($value) !== '') {
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
        $request = new Request;
        return $request($keys, $default);
    }

    public function validate($rules, $options = [])
    {
        $validator = new Validator($rules, $options);
        return $validator();
    }

    public function response($data, $code = 200, $gzip = false, $sanitize = false, $except_sanitize = [])
    {
        $response = new Response;
        return $response($data, $code, $gzip, $sanitize, $except_sanitize);
    }

    public function bpjs($options, $isProduction = false)
    {
        return new BPJS($options, $isProduction);
    }

    public function db($connection = null)
    {
        return new Database($connection);
    }

    public function query($query, $bindings = [], $connection = null)
    {
        return $this->db($connection)->query($query, $bindings);
    }

    public function transaction(\Closure $transaction, $connection = null)
    {
        return $this->db($connection)->transaction($transaction);
    }

    public function datatable($data)
    {
        return new Datatable($data);
    }

    public function telegram($token = null)
    {
        return new Telegram($token ? $token : $this->env('TELEGRAM_TOKEN', ''));
    }

    public function loadtime()
    {
        return microtime(true) - $this->loadtime;
    }
}
