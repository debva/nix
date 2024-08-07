<?php

namespace Debva\Nix\Database;

class PostgreSQL extends Base
{
    protected $withCast = true;

    protected $quoteMark = '"';

    protected $whereOperator = 'ILIKE';

    protected $bindingSymbol = '?';

    protected $bindingVariable = '';

    public function __construct($connection = null)
    {
        parent::__construct($connection);
    }

    protected function setDSN($connection, $host, $port, $dbname, $user, $password)
    {
        return "{$connection}:host={$host};port={$port};dbname={$dbname}";
    }

    protected function setConnection($dsn, $user, $password)
    {
        return new \PDO($dsn, $user, $password, [
            \PDO::ATTR_ERRMODE              => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE   => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES     => true,
        ]);
    }

    protected function setDestroyConnection($connection)
    {
        $this->connection = null;
    }

    protected function getOperator()
    {
        return [
            '=', '<', '>', '<=', '>=', '<>', '!=', '||',
            '!!=', '~~', '!~~', '~', '~*', '!~', '!~',
            'LIKE', 'NOT LIKE', 'ILIKE', 'NOT ILIKE',
            'IS', 'IS NOT', 'IN', 'NOT IN',
            'BETWEEN', 'NOT BETWEEN',
        ];
    }

    protected function getLogicalOperator()
    {
        return  ['AND', 'OR', 'NOT'];
    }

    protected function getBeginTransaction($connection)
    {
        return $connection->beginTransaction();
    }

    protected function getInTransaction($connection)
    {
        return $connection->inTransaction();
    }

    protected function getCommit($connection)
    {
        return $connection->commit();
    }

    protected function getRollBack($connection)
    {
        return $connection->rollBack();
    }

    protected function getPrepare($connection, $query)
    {
        return $connection->prepare($query);
    }

    protected function getExecute($connection, $statement, $bindings)
    {
        $params = [
            'NULL'      => \PDO::PARAM_NULL,
            'integer'   => \PDO::PARAM_INT,
            'string'    => \PDO::PARAM_STR,
            'boolean'   => \PDO::PARAM_BOOL,
        ];

        foreach ($bindings as $key => &$value) {
            $type = gettype($value);
            $type = in_array($type, array_keys($params)) ? $params[$type] : $params['string'];
            $statement->bindParam((is_int($key) ? $key + 1 : $key), $value, $type);
        }

        $statement->execute();
        return $statement;
    }

    protected function getFetchColumn($statement)
    {
        return $statement->fetchColumn();
    }

    protected function getFetch($statement)
    {
        return $statement->fetch();
    }

    protected function getFetchAll($statement)
    {
        return $statement->fetchAll();
    }

    protected function getLastInsertId($connection)
    {
        return $this->getPrimaryKey() ? $this->getFetchColumn($connection) : null;
    }

    public function getDataType($type)
    {
        $types = [
            'string'    => 'VARCHAR',
            'int'       => 'INT',
        ];

        return isset($types[$type]) ? $types[$type] : $types['int'];
    }

    public function cast($value, $dataType)
    {
        return $this->raw("CAST({$this->bindingSymbol}{$this->bindingVariable} AS {$dataType})", $value);
    }

    public function create($table, $data = [])
    {
        $level = $this->beginTransaction();

        try {
            if (!is_array($data)) throw new \Exception('Data must be an array', 500);

            $result = [];

            if (count($data) != count($data, COUNT_RECURSIVE)) {
                foreach ($data as $item) $result = array_merge($result, [$this->create($table, $item)]);
            } else {
                $indexBinding = 0;

                $table = implode('.', array_map(function ($table) {
                    return $this->buildQuotationMark($table);
                }, array_filter(explode('.', $table))));

                $fields = $this->buildFields($data);
                $values = $this->buildPlaceholder($indexBinding, $data);

                $returning = $this->getPrimaryKey() ? "RETURNING {$this->buildQuotationMark($this->primaryKey)}" : '';

                $query = "INSERT INTO {$table} ({$fields}) VALUES ($values) {$returning}";
                $query = $this->query($query, array_values($data), true);

                $result = $this->getLastInsertId($query);
            }

            $this->commit($level);
            return $result;
        } catch (\Exception $e) {
            $this->rollBack($level);
            throw new \Exception($e->getMessage(), is_int($e->getCode()) ? $e->getCode() : 500);
        }
    }

    public function update($table, $data = [], $conditions = [])
    {
        $level = $this->beginTransaction();

        try {
            if (!is_array($data)) throw new \Exception('Data must be an array', 500);

            if (!is_array($conditions)) throw new \Exception('Conditions must be an array', 500);

            if (count($data) != count($data, COUNT_RECURSIVE)) {
                $isConditionsArray = array_reduce($conditions, function ($cary, $item) {
                    return $cary && is_array($item);
                }, true);

                if (!$isConditionsArray) throw new \Exception('Conditions do not match the data', 500);

                return array_map(function ($data, $conditions) use ($table) {
                    return $this->update($table, $data, $conditions);
                }, $data, $conditions);
            } else {
                $indexBinding = 0;

                $table = implode('.', array_map(function ($table) {
                    return $this->buildQuotationMark($table);
                }, array_filter(explode('.', $table))));

                $fields = $this->buildFields($data, $indexBinding, self::FIELD_BINDINGS);
                $conditions = $this->buildConditions($conditions, $indexBinding);

                $query = "UPDATE {$table} SET {$fields} WHERE {$conditions['query']}";
                $query = $this->query($query, array_merge(array_values($data), $conditions['bindings']), true);
            }

            $this->commit($level);
            return true;
        } catch (\Exception $e) {
            $this->rollBack($level);
            throw new \Exception($e->getMessage(), is_int($e->getCode()) ? $e->getCode() : 500);
        }
    }

    public function delete($table, $conditions = [], $isMultiple = false)
    {
        $level = $this->beginTransaction();

        try {
            $result = [];

            if ($isMultiple) {
                foreach ($conditions as $condition) $result = array_merge($result, [$this->delete($table, $condition)]);
            } else {
                $indexBinding = 0;

                $table = implode('.', array_map(function ($table) {
                    return $this->buildQuotationMark($table);
                }, array_filter(explode('.', $table))));

                $conditions = $this->buildConditions($conditions, $indexBinding);

                $query = "DELETE FROM {$table} WHERE {$conditions['query']}";
                $query = $this->query($query, $conditions['bindings'], true);

                $result = true;
            }

            $this->commit($level);
            return $result;
        } catch (\Exception $e) {
            $this->rollBack($level);
            throw new \Exception($e->getMessage(), is_int($e->getCode()) ? $e->getCode() : 500);
        }
    }

    public function upsert($table, $data = [], $uniqueBy = [], $isUpdate = false)
    {
        $level = $this->beginTransaction();

        try {
            if (!is_array($data)) throw new \Exception('Data must be an array', 500);

            $result = [];

            if (count($data) != count($data, COUNT_RECURSIVE)) {
                foreach ($data as $item) $result = array_merge($result, [$this->upsert($table, $item, $uniqueBy, $isUpdate)]);
            } else {
                $uniqueBy = array_intersect_key($data, array_flip(array_filter(is_array($uniqueBy) ? $uniqueBy : [$uniqueBy])));
                $conditions = array_reduce($uniqueBy, function ($cary, $item) use ($uniqueBy) {
                    $cary[] = [array_search($item, $uniqueBy, true), $item];
                    $cary[] = 'AND';
                    return $cary;
                }, []);

                array_pop($conditions);

                $fields = $this->buildFields($data);

                if (empty($conditions)) {
                    $result = $this->create($table, $data);
                } else {
                    $indexBinding = 0;
                    $fieldsBindings = $this->buildFields($data, $indexBinding, self::FIELD_BINDINGS);

                    $indexBinding = 0;
                    $fieldsAlias = $this->buildFields($data, $indexBinding, self::FIELD_ALIAS);

                    $table = implode('.', array_map(function ($table) {
                        return $this->buildQuotationMark($table);
                    }, array_filter(explode('.', $table))));

                    $returning = $this->getPrimaryKey() ? "RETURNING {$this->buildQuotationMark($this->primaryKey)}" : '';

                    $conditions = $this->buildConditions($conditions, $indexBinding);
                    $bindings = array_merge(array_values($data), $conditions['bindings']);

                    $queryInsert = "INSERT INTO {$table} ({$fields}) SELECT * FROM (SELECT {$fieldsAlias}) AS {$this->buildQuotationMark('tmp')} WHERE NOT EXISTS (SELECT {$fields} FROM {$table} WHERE {$conditions['query']}) {$returning}";
                    $queryUpdate = "UPDATE {$table} SET {$fieldsBindings} WHERE {$conditions['query']}";

                    $query = array_merge([$queryInsert], $isUpdate ? [$queryUpdate] : []);
                    $bindings = array_merge([$bindings], $isUpdate ? [$bindings] : []);

                    $query = $this->query($query, $bindings, true);

                    if (is_array($query)) $query = reset($query);
                    $result = $this->getLastInsertId($query);
                }
            }

            $this->commit($level);
            return $result;
        } catch (\Exception $e) {
            $this->rollBack($level);
            throw new \Exception($e->getMessage(), is_int($e->getCode()) ? $e->getCode() : 500);
        }
    }
}
