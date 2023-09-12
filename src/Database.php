<?php

namespace Debva\Nix;

class Database
{
    protected $database;

    public function __construct($connection, $host, $port, $dbname, $user, $password)
    {
        try {
            $dsn = "{$connection}:host={$host};port={$port};dbname={$dbname}";
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ];

            $this->database = new \PDO($dsn, $user, $password, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage());
        }
    }

    public function raw($query, $bindings = [])
    {
        $class = new Anonymous;
        foreach (['connection' => $this->database, 'query' => $query, 'bindings' => $bindings] as $method => $value) {
            $class->macro($method, function () use ($value) {
                return $value;
            });
        }
        return $class;
    }

    public function query($query, $bindings = [])
    {
        try {
            $this->database->beginTransaction();
            $statement = $this->database->prepare($query);
            $statement->execute($bindings);
            $this->database->commit();
            return $statement;
        } catch (\PDOException $e) {
            $this->database->rollBack();
            throw new \PDOException($e->getMessage());
        }
    }
}
