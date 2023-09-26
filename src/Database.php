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

    public function beginTransaction()
    {
        return $this->database->beginTransaction();
    }

    public function commit()
    {
        return $this->database->commit();
    }

    public function rollBack()
    {
        return $this->database->rollBack();
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
        $statement = $this->database->prepare($query);
        $statement->execute($bindings);
        return $statement;
    }

    public function transaction(\Closure $transaction)
    {
        return $transaction($this);
    }
}
