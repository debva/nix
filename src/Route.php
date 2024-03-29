<?php

namespace Debva\Nix;

class Route
{
    protected $prefixPath;

    public $requestPath;

    public $requestMethod;

    public function __construct()
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            throw new \Exception('Invalid request!', 500);
        }

        if (!$this->requestPath) {
            $requestPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
            $this->prefixPath = env('APP_PATH', '') ? trim(env('APP_PATH', ''), '/') : '';
            $this->requestPath = trim(str_replace($this->prefixPath, '', $requestPath), '/');
        }

        $this->requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : false;
    }

    public function __invoke($route = null, $query = [])
    {
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
        $route = $route ? trim($route, '\/') : $this->requestPath;
        $query = !empty($query) ? (strpos($route, '?') !== false ? '&' : '?') . http_build_query($query) : '';
        return trim(join('/', array_filter([$host, $this->prefixPath, $route . $query])), '\/');
    }
}
