<?php

namespace Debva\Nix\Database;

class PostgreSQL extends Base
{
    protected $withCast = true;

    protected $quoteMark = '"';

    protected $whereOperator = 'ILIKE';

    protected $bindingSymbol = '${symbol}';

    public function __construct($connection = null)
    {
        parent::__construct($connection);
    }

    protected function setDSN($connection, $host, $port, $dbname, $user, $password)
    {
        return "host={$host} port={$port} dbname={$dbname} user={$user} password={$password}";
    }

    protected function setConnection($dsn, $user, $password)
    {
        return pg_connect($dsn);
    }

    protected function setDestroyConnection($connection)
    {
        return pg_close($connection);
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

    protected function getDataType($type)
    {
        $types = [
            'string'    => 'VARCHAR',
            'int'       => 'INT',
        ];

        return isset($types[$type]) ? $types[$type] : $types['int'];
    }

    protected function getBeginTransaction($connection)
    {
        return pg_query($connection, 'BEGIN');
    }

    protected function getInTransaction($connection)
    {
        $result = pg_query($connection, 'SELECT NOW() = STATEMENT_TIMESTAMP()');
        $result = pg_fetch_result($result, 0);
        return $result === 'f' ? true : false;
    }

    protected function getCommit($connection)
    {
        return pg_query($connection, 'COMMIT');
    }

    protected function getRollBack($connection)
    {
        return pg_query($connection, 'ROLLBACK');
    }

    protected function getPrepare($connection, $query)
    {
        $stmtname = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 25);
        pg_prepare($connection, $stmtname, $query);
        return $stmtname;
    }

    protected function getExecute($connection, $statement, $bindings)
    {
        return pg_execute($connection, $statement, $bindings);
    }

    protected function getFetchColumn($statement)
    {
        return pg_fetch_result($statement, 0);
    }

    protected function getFetch($statement)
    {
        return pg_fetch_assoc($statement);
    }

    protected function getFetchAll($statement)
    {
        return pg_fetch_all($statement);
    }

    protected function getLastInsertId($connection)
    {
        return pg_fetch_result($connection, 0);
    }

    public function create($table, $data = [])
    {
        $level = $this->beginTransaction();

        try {
            if (!is_array($data)) throw new \Exception('Data must be an array', 500);

            $result = [];

            if (count($data) != count($data, COUNT_RECURSIVE)) {
                foreach ($data as $item) $result = array_merge($result, $this->create($table, $item));
            } else {
                $indexBinding = 0;

                $fields = $this->buildFields($data);
                $values = $this->buildPlaceholder($indexBinding, $data);

                $query = "INSERT INTO {$this->buildQuotationMark($table)} ({$fields}) VALUES ($values) RETURNING {$this->buildQuotationMark($this->primaryKey)}";
                $query = $this->query(
                    $this->sanitizeQuery($query),
                    $this->sanitizeBindings(array_values($data)),
                    true
                );

                $result = $this->getLastInsertId($query);
            }

            $this->commit($level);
            return $result;
        } catch (\Exception $e) {
            $this->rollBack($level);
            throw new \Exception($e->getMessage(), $e->getCode());
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

                array_map(function ($data, $conditions) use ($table) {
                    $this->update($table, $data, $conditions);
                }, $data, $conditions);
            } else {
                $indexBinding = 0;

                $fields = $this->buildFields($data, $indexBinding, self::FIELD_BINDINGS);
                $conditions = $this->buildConditions($conditions, $indexBinding);

                $query = "UPDATE {$this->buildQuotationMark($table)} SET {$fields} WHERE {$conditions['query']}";
                $query = $this->query(
                    $this->sanitizeQuery($query),
                    $this->sanitizeBindings(array_merge(array_values($data), $conditions['bindings'])),
                    true
                );
            }

            $this->commit($level);
            return true;
        } catch (\Exception $e) {
            $this->rollBack($level);
            throw new \Exception($e->getMessage(), $e->getCode());
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
                $conditions = $this->buildConditions($conditions, $indexBinding);

                $query = "DELETE FROM {$this->buildQuotationMark($table)} WHERE {$conditions['query']}";
                $query = $this->query(
                    $this->sanitizeQuery($query),
                    $this->sanitizeBindings($conditions['bindings']),
                    true
                );

                $result = true;
            }

            $this->commit($level);
            return $result;
        } catch (\Exception $e) {
            $this->rollBack($level);
            throw new \Exception($e->getMessage(), $e->getCode());
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

                    $table = $this->buildQuotationMark($table);
                    $primaryKey = $this->buildQuotationMark($this->primaryKey);

                    $conditions = $this->buildConditions($conditions, $indexBinding);
                    $bindings = $this->sanitizeBindings(array_merge(array_values($data), $conditions['bindings']));

                    $queryInsert = "INSERT INTO {$table} ({$fields}) SELECT * FROM (SELECT {$fieldsAlias}) AS {$this->buildQuotationMark('tmp')} WHERE NOT EXISTS (SELECT {$fields} FROM {$table} WHERE {$conditions['query']}) RETURNING {$primaryKey}";
                    $queryUpdate = "UPDATE {$table} SET {$fieldsBindings} WHERE {$conditions['query']}";
                    $query = $this->query(array_merge([$queryInsert], $isUpdate ? [$queryUpdate] : []), array_merge([$bindings], $isUpdate ? [$bindings] : []), true);

                    if (is_array($query)) $query = reset($query);
                    $result = $this->getLastInsertId($query);
                }
            }

            $this->commit($level);
            return $result;
        } catch (\Exception $e) {
            $this->rollBack($level);
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }
}
