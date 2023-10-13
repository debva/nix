<?php

namespace Debva\Nix;

abstract class Bridge extends Environment
{
    protected $loadtime;

    protected $sapiName;

    public function __construct()
    {
        $this->loadtime = microtime(true);

        $this->sapiName = php_sapi_name();

        parent::__construct();

        date_default_timezone_set(env('DATE_TIMEZONE', 'Asia/Jakarta'));
    }

    public function loadtime()
    {
        return microtime(true) - $this->loadtime;
    }
}
