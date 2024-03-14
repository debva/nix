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

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == "OPTIONS") {
            header("HTTP/1.1 200 OK");
            exit(0);
        }

        parent::__construct();

        date_default_timezone_set(env('DATE_TIMEZONE', 'Asia/Jakarta'));
    }

    public function loadtime()
    {
        return microtime(true) - NIX_START;
    }
}
