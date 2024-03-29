<?php

namespace Debva\Nix;

abstract class Bridge extends Environment
{
    public function __construct()
    {
        if (!defined('FRAMEWORK_VERSION')) {
            define('FRAMEWORK_VERSION', '1.5.0');
        }

        if (!defined('NIX_START')) {
            throw new \Exception('Constants NIX_START must be defined first!', 500);
        }

        date_default_timezone_set(env('DATE_TIMEZONE', 'Asia/Jakarta'));

        parent::__construct();

        $origin = !empty(getenv('ACCESS_CONTROL_ALLOW_ORIGIN')) ? getenv('ACCESS_CONTROL_ALLOW_ORIGIN') : '*';
        $methods = !empty(getenv('ACCESS_CONTROL_ALLOW_METHODS')) ?  getenv('ACCESS_CONTROL_ALLOW_METHODS') : 'GET, POST, PATCH, DELETE, OPTIONS';
        $headers = !empty(getenv('ACCESS_CONTROL_ALLOW_HEADERS')) ?  getenv('ACCESS_CONTROL_ALLOW_HEADERS') : 'Content-Type, Authorization';

        header("Access-Control-Allow-Origin: {$origin}");
        header("Access-Control-Allow-Methods: {$methods}");
        header("Access-Control-Allow-Headers: {$headers}");

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == "OPTIONS") {
            http_response_code(200);
            exit(1);
        }
    }

    public function loadtime()
    {
        return microtime(true) - NIX_START;
    }
}
