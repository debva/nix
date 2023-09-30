<?php

namespace Debva\Nix;

class Database extends Environment
{
    protected $database;

    public function __construct($connection = null)
    {
        if ($connection) $connection = '_' . strtoupper($connection);

        list($connection, $host, $port, $dbname, $user, $password) = [
            getenv("DB{$connection}_CONNECTION"),
            getenv("DB{$connection}_HOST"),
            getenv("DB{$connection}_PORT"),
            getenv("DB{$connection}_DATABASE"),
            getenv("DB{$connection}_USER"),
            getenv("DB{$connection}_PASSWORD"),
        ];

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

    public function getConnection()
    {
        return $this->database;
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
        $this->beginTransaction();
        try {
            $statement = $this->database->prepare($query);
            $statement->execute($bindings);
            $this->commit();
            return $statement;
        } catch (\PDOException $e) {
            $this->rollBack();
            throw new \PDOException($e->getMessage(), 500);
        }
    }

    public function transaction(\Closure $transaction)
    {
        return $transaction($this);
    }
}
