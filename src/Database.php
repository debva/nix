<?php

namespace Debva\Nix;

class Database
{
    public function __invoke($connection = null)
    {
        $connection = is_null($connection) ? $connection : strtoupper($connection);
        $driver = env("DB{$connection}_CONNECTION", null);
        $driver = is_null($driver) ? env("DB_{$connection}_CONNECTION", null) : $driver;

        if (!$driver) throw new \Exception('Database is not supported', 500);

        $driver = [
            'mysql' => \Debva\Nix\Database\MySQL::class,
            'pgsql' => \Debva\Nix\Database\PostgreSQL::class,
        ][$driver];

        return new $driver($connection);
    }
}
