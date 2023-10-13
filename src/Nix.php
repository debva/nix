<?php

namespace Debva\Nix;

class Nix
{
    protected $cacheClass = [];

    public function __invoke($class, ...$args)
    {
        $class = strtolower($class);

        $classes = [
            'anonymous' => Anonymous::class,
            'auth'      => Authentication::class,
            'bpjs'      => BPJS::class,
            'console'   => Console::class,
            'crypt'     => Cryptography::class,
            'db'        => Database::class,
            'datatable' => Datatable::class,
            'http'      => Http::class,
            'task'      => Task::class,
            'request'   => Request::class,
            'response'  => Response::class,
            'route'     => Route::class,
            'session'   => Session::class,
            'storage'   => Storage::class,
            'telegram'  => Telegram::class,
            'userAgent' => UserAgent::class,
            'validate'  => Validate::class
        ];

        if (!in_array($class, array_keys($classes))) {
            throw new \Exception("Class {$class} is not defined!");
        }

        if (in_array($class, ['auth', 'crypt', 'http', 'task', 'request', 'response', 'route', 'storage'])) {
            return isset($this->cacheClass[$class])
                ? $this->cacheClass[$class]
                : $this->cacheClass[$class] = new $classes[$class](...$args);
        }

        return new $classes[$class](...$args);
    }
}