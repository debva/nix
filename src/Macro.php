<?php

namespace Debva\Nix;

abstract class Macro
{
    public $macros = [];

    public function __set($name, $value)
    {
        return $this->{$name} = $value;
    }

    public function __get($name)
    {
        return $this->{$name};
    }

    public function __call($method, $args)
    {
        if (isset($this->macros[$method])) {
            $macro = $this->macros[$method];
            if (is_callable($macro)) {
                return $macro($this, ...$args);
            }
        }

        throw new \Exception("Method [$method] does not exist.", 500);
    }

    public function macro($name, $callback)
    {
        $this->macros[$name] = $callback;
    }
}
