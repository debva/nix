<?php

namespace Debva\Nix;

abstract class Macro
{
    public $macros = [];

    public function macro($name, $callback)
    {
        $this->macros[$name] = $callback;
    }

    public function __call($method, $args)
    {
        if (isset($this->macros[$method])) {
            $macro = $this->macros[$method];
            if (is_callable($macro)) {
                return call_user_func_array($macro, $args);
            }
        }

        die("Method [$method] does not exist.");
    }
}
