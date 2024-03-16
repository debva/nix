<?php

namespace Debva\Nix\Database;

abstract class Base
{
    protected const FIELD_ONLY = 1;

    protected const FIELD_BINDINGS = 2;

    protected const FIELD_ALIAS = 3;

    protected $dsn;

    protected $connection;

    protected $primaryKey = 'id';

    protected $withCast;

    protected $quoteMark;

    protected $whereOperator;

    protected $bindingSymbol;

    protected $bindingVariable;

    protected $level = 0;

    protected $currentLevel = null;

    protected $query;

    protected $bindings;

    protected $rawValue;

    protected $statement;

    protected $lastInsertId;

    abstract protected function setDSN($connection, $host, $port, $dbname, $user, $password);

    abstract protected function setConnection($dsn, $user, $password);

    abstract protected function setDestroyConnection($connection);

    abstract protected function getOperator();

    abstract protected function getLogicalOperator();

    abstract protected function getBeginTransaction($connection);

    abstract protected function getInTransaction($connection);

    abstract protected function getCommit($connection);

    abstract protected function getRollBack($connection);

    abstract protected function getPrepare($connection, $query);

    abstract protected function getExecute($connection, $statement, $bindings);

    abstract protected function getFetchColumn($statement);

    abstract protected function getFetch($statement);

    abstract protected function getFetchAll($statement);

    abstract protected function getLastInsertId($connection);

    abstract public function getDataType($type);

    abstract public function cast($value, $dataType);

    abstract public function create($table, $data = []);

    abstract public function update($table, $data = [], $conditions = []);

    abstract public function delete($table, $conditions = [], $isMultile = false);

    abstract public function upsert($table, $data = [], $uniqueBy = [], $isUpdate = false);

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

        $this->dsn = $this->setDSN($connection, $host, $port, $dbname, $user, $password);

        $this->connection = $this->setConnection($this->dsn, $user, $password);
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function setPrimaryKey($primaryKey)
    {
        return $this->primaryKey = $primaryKey;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getWhereOperator()
    {
        return $this->whereOperator;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getBindings()
    {
        return $this->bindings;
    }

    public function getStatement()
    {
        return $this->statement;
    }

    public function sanitizeQuery($query)
    {
        return trim(preg_replace('/[\n\t]+|\s+/', ' ', $query));
    }

    public function sanitizeBindings($bindings)
    {
        if (!is_array($bindings)) $bindings = [$bindings];

        $bindings = array_map(function ($bindings) {
            return $bindings instanceof \stdClass ? $bindings->bindings : $bindings;
        }, array_filter($bindings, function ($bindings) {
            $instance = $bindings instanceof \stdClass;
            return !$instance || ($instance && $bindings->bindings);
        }));
        return $bindings;
    }

    public function buildQuotationMark($value, $withQuotationMark = true)
    {
        if (!$this->quoteMark) throw new \Exception('Quotation marks have not been set', 500);
        return $withQuotationMark ? "{$this->quoteMark}{$value}{$this->quoteMark}" : $value;
    }

    public function buildPlaceholder(&$indexBinding = 0, $data = [], $prefixBindingName = null)
    {
        if (empty($data)) {
            if (!$this->bindingSymbol && !$this->bindingVariable) throw new \Exception('Binding symbol or variable have not been set', 500);

            $indexBinding++;
            return is_null($prefixBindingName)
                ? str_replace($this->bindingVariable, $indexBinding, "{$this->bindingSymbol}{$this->bindingVariable}")
                : ":{$prefixBindingName}_{$indexBinding}";
        }

        if (!is_array($data)) throw new \Exception('Field must be an array', 500);

        return implode(', ', array_map(function ($value) use (&$indexBinding, $prefixBindingName) {
            if ($value instanceof \stdClass) {
                $bindingSymbol = $value->bindings ? $this->buildPlaceholder($indexBinding, [], $prefixBindingName) : '';
                return str_replace($this->bindingVariable, $bindingSymbol, $value->query);
            }

            return $this->buildPlaceholder($indexBinding, [], $prefixBindingName);
        }, array_values($data)));
    }

    public function buildFields($data, &$indexBinding = 0, $flag = self::FIELD_ONLY)
    {
        switch ($flag) {
            case self::FIELD_ONLY:
                return implode(', ', array_map(function ($field) {
                    return $this->buildQuotationMark($field);
                }, array_keys($data)));

            case self::FIELD_BINDINGS:
                return implode(', ', array_map(function ($field, $value) use (&$indexBinding) {
                    $placeholder = $this->buildPlaceholder($indexBinding, [$field => $value]);
                    return "{$this->buildQuotationMark($field)} = {$placeholder}";
                }, array_keys($data), array_values($data)));

            case self::FIELD_ALIAS:
                return implode(', ', array_map(function ($field, $value) use (&$indexBinding) {
                    $placeholder = $this->buildPlaceholder($indexBinding, [$field => $value]);
                    return "{$placeholder} AS {$this->buildQuotationMark($field)}";
                }, array_keys($data), array_values($data)));
        }

        return null;
    }

    public function buildConditions($conditions = [], $indexBinding = 0, $withQuotationMark = true, $prefixBindingName = null)
    {
        if (!is_array($conditions)) throw new \Exception('Conditions must be an array', 500);

        $query = $bindings = [];
        $logicalOperator = [null];

        if (count($conditions) % 2 == 0) {
            throw new \Exception('Invalid conditions', 500);
        }

        foreach ($conditions as $index => $item)
            if (($index % 2 == 0 && !is_array($item)) || ($index % 2 != 0 && !is_string($item)))
                throw new \Exception('Invalid conditions', 500);

        foreach ($conditions as $condition) {
            if (is_array($condition) || (is_array($condition) && is_array(reset($condition)))) $query[] = $condition;
            else if (!in_array($condition, $this->getLogicalOperator()))
                throw new \Exception("Logical operator {$condition} are not supported", 500);
            else $logicalOperator[] = $condition;
        }

        $query = array_map(function ($condition, $logicalOperator) use (&$indexBinding, &$bindings, $withQuotationMark, $prefixBindingName) {
            if (is_array(reset($condition))) {
                $conditions = $this->buildConditions($condition, $indexBinding);
                $bindings = array_merge($bindings, $conditions['bindings']);
                return "{$logicalOperator} ({$conditions['query']})";
            }

            if (count($condition) < 2 || count($condition) > 3) {
                throw new \Exception('Conditions must have at least two elements in the condition array or no more than 3', 500);
            }

            $operator = '=';
            if (count($condition) < 3) list($column, $value) = $condition;
            else list($column, $operator, $value) = $condition;

            if ($column instanceof \stdClass) $column = str_replace($this->bindingVariable, $column->bindings, $column->query);

            if (!in_array($operator, $this->getOperator())) {
                throw new \Exception("Operator {$operator} not supported", 500);
            }

            $operator = strtoupper($operator);
            if (in_array($operator, ['BETWEEN', 'IN', 'NOT IN'])) {
                if (strContains($operator, 'IN', true)) {
                    if (!is_array($value)) throw new \Exception("{$operator} have invalid values", 500);

                    $in = [];
                    foreach ($value as $item) {
                        $in[] = $this->buildPlaceholder($indexBinding, [$column => $item], $prefixBindingName);
                        if ($item instanceof \stdClass) {
                            if ($item->bindings) $bindings[] = $item->bindings;
                        } else $bindings[] = $item;
                    }

                    $in = implode(', ', $in);
                    return trim("{$logicalOperator} {$this->buildQuotationMark($column,$withQuotationMark)} {$operator} ({$in})");
                }

                if (strContains($operator, 'BETWEEN', true)) {
                    if (!is_array($value) || count($value) < 3) throw new \Exception("{$operator} have invalid values", 500);
                    list($value1, $logical, $value2) = $value;

                    if ($value1 instanceof \stdClass) {
                        if ($value1->bindings) $bindings[] = $value1->bindings;
                    } else $bindings[] = $value1;

                    if ($value2 instanceof \stdClass) {
                        if ($value2->bindings) $bindings[] = $value2->bindings;
                    } else $bindings[] = $value2;

                    $placeholder1 = $this->buildPlaceholder($indexBinding, [$column => $value1], $prefixBindingName);
                    $placeholder2 = $this->buildPlaceholder($indexBinding, [$column => $value2], $prefixBindingName);

                    return trim("{$logicalOperator} {$this->buildQuotationMark($column,$withQuotationMark)} {$operator} {$placeholder1} {$logical} {$placeholder2}");
                }
            }

            if ($value instanceof \stdClass) {
                if ($value->bindings) $bindings[] = $value->bindings;
            } else $bindings[] = $value;

            $column = $this->buildQuotationMark($column, $withQuotationMark);
            $placeholder = $this->buildPlaceholder($indexBinding, [$column => $value], $prefixBindingName);
            return trim("{$logicalOperator} {$column} {$operator} {$placeholder}");
        }, $query, $logicalOperator);

        return [
            'query'     => empty($query) ? null : $this->sanitizeQuery(implode(' ', $query)),
            'bindings'  => $this->sanitizeBindings($bindings),
        ];
    }

    public function beginTransaction()
    {
        $this->level++;

        if (!$this->currentLevel) {
            $this->currentLevel = $this->level;
            $this->getBeginTransaction($this->getConnection());
        }

        return $this->level;
    }

    public function inTransaction()
    {
        return $this->getInTransaction($this->getConnection());
    }

    public function commit($level = null)
    {
        if ($level && $this->currentLevel != $level) return null;
        $this->level = 0;
        $this->currentLevel = null;
        return $this->getCommit($this->getConnection());
    }

    public function rollBack($level = null)
    {
        if ($level && $this->currentLevel != $level) return null;
        $this->level = 0;
        $this->currentLevel = null;
        return $this->getRollBack($this->getConnection());
    }

    public function raw($query, $bindings = [])
    {
        $class = new \stdClass;
        $class->query = $query;
        $class->bindings = $bindings;
        return $class;
    }

    public function query($query, $bindings = [], $execute = false)
    {
        $this->query = $this->bindings = $this->statement = [];

        if (!is_array($bindings)) throw new \Exception('Bindings must be an array', 500);

        if (is_array($query)) {
            if (!empty($bindings)) {
                $isBindingsArray = array_reduce($bindings, function ($cary, $item) {
                    return $cary && is_array($item);
                }, true);

                if (!$isBindingsArray) throw new \Exception('Bindings do not match the query', 500);
            }

            array_map(function ($query, $bindings) {
                $query = $this->sanitizeQuery($query);
                $bindings = $this->sanitizeBindings($bindings);

                $this->query[] = $query;
                $this->bindings[] = is_null($bindings) ? [] : $bindings;
                $this->statement[] = $this->getPrepare($this->getConnection(), $query);
            }, $query, $bindings);
        } else {
            $query = $this->sanitizeQuery($query);
            $bindings = $this->sanitizeBindings($bindings);

            $this->query = $query;
            $this->bindings = $bindings;
            $this->statement = $this->getPrepare($this->getConnection(), $query);
        }

        if ($execute) return $this->execute();
        return $this;
    }

    public function execute($callback = null)
    {
        if (is_array($this->statement)) {
            return array_map(function ($statement, $bindings) use ($callback) {
                $statement = $this->getExecute($this->getConnection(), $statement, $bindings);
                return is_callable($callback) ? $callback($statement) : $statement;
            }, $this->statement, $this->bindings);
        }

        $statement = $this->getExecute($this->getConnection(), $this->statement, $this->bindings);
        return is_callable($callback) ? $callback($statement) : $statement;
    }

    public function column()
    {
        return $this->execute(function ($statement) {
            return $this->getFetchColumn($statement);
        });
    }

    public function first()
    {
        return $this->execute(function ($statement) {
            return $this->getFetch($statement);
        });
    }

    public function get()
    {
        return $this->execute(function ($statement) {
            return $this->getFetchAll($statement);
        });
    }
}
