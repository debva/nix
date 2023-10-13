<?php

namespace Debva\Nix;

class Route
{
    public $requestPath;

    protected $prefixPath;

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
    }

    public function __invoke($route = null, $query = [])
    {
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
        $route = $route ? trim($route, '\/') : $this->requestPath;
        $query = !empty($query) ? (strpos($route, '?') !== false ? '&' : '?') . http_build_query($query) : '';
        return trim(join('/', [$host, $this->prefixPath, $route . $query]), '\/');
    }
}
