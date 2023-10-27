<?php

namespace Debva\Nix;

class Database
{
    protected $database;

    protected $connection;

    protected $whereClause;

    public function __construct($connection = null)
    {
        if ($connection) $connection = "_" . strtoupper($connection);

        list($connection, $host, $port, $dbname, $user, $password) = [
            env("DB{$connection}_CONNECTION"),
            env("DB{$connection}_HOST"),
            env("DB{$connection}_PORT"),
            env("DB{$connection}_DATABASE"),
            env("DB{$connection}_USER"),
            env("DB{$connection}_PASSWORD"),
        ];

        $this->connection = $connection;

        $this->whereClause = in_array($connection, ['pgsql']) ? 'ILIKE' : 'LIKE';

        try {
            if (!isset($connection, $host, $dbname, $user, $password)) {
                throw new \Exception('Invalid database connection!', 500);
            }

            $dsn = "{$connection}:host={$host};port={$port};dbname={$dbname}";
            $options = [
                \PDO::ATTR_ERRMODE              => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE   => \PDO::FETCH_ASSOC
            ];

            $this->database = new \PDO($dsn, $user, $password, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), 500);
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
        foreach ([
            'connection'    => strtolower($this->connection),
            'whereClause'   => $this->whereClause,
            'database'      => $this->database,
            'query'         => $this->sanitize($query),
            'bindings'      => $this->parseBindings($bindings)
        ] as $method => $value) {
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
            if (is_array($query)) {
                foreach ($query as $sql) {
                    $bindings = [];
                    if (is_array($sql)) {
                        $bindings = count($sql) === 2 ? end($sql) : [];
                        $sql = reset($sql);
                    }

                    $statement = $this->database->prepare($this->sanitize($sql));
                    $statement->execute($this->parseBindings($bindings));
                }

                $this->commit();
                return true;
            }

            $statement = $this->database->prepare($this->sanitize($query));
            $statement->execute($this->parseBindings($bindings));

            $this->commit();
            return $statement;
        } catch (\PDOException $e) {
            $this->rollBack();
            throw new \PDOException($e->getMessage(), 500);
        }
    }

    public function parseBindings($bindings)
    {
        if (!is_array($bindings)) return null;
        return $bindings;
    }

    public function sanitize($sql)
    {
        return trim(preg_replace('/[\n\t]+|\s+/', ' ', $sql));
    }

    public function transaction(\Closure $transaction)
    {
        return $transaction($this);
    }
}
