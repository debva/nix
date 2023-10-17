<?php

namespace Debva\Nix;

class Extension
{
    protected $cacheClass = [];

    public function __call($extension, $parameters)
    {
        $extensions = [
            'bpjs'      => \Debva\Nix\Extension\BPJS::class,
            'inacbg'    => \Debva\Nix\Extension\InaCBGs::class,
        ];

        $ext = strtolower($extension);

        if (!in_array($ext, array_keys($extensions))) {
            throw new \Exception("Extension {$extension} not found!");
        }

        return isset($this->cacheClass[$ext])
            ? $this->cacheClass[$ext]
            : $this->cacheClass[$ext] = new $extensions[$ext](...$parameters);
    }
}
