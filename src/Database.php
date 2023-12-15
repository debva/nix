<?php

namespace Debva\Nix;

class Database
{
    protected $database;

    protected $connection;

    protected $whereClause;

    protected $mark;

    protected $primaryKey = 'id';

    protected $query;

    protected $bindings;

    protected $statement;

    protected $transactionLevel = 0;

    protected $transactionLevelUsed = 0;

    protected $transactionCompleted = false;

    protected $withTransaction = true;

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

        if (in_array($connection, ['pgsql'])) {
            $this->whereClause = 'ILIKE';
            $this->mark = '"';
        } else {
            $this->whereClause = 'LIKE';
            $this->mark = '`';
        }

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

    public function sanitizeQuery($query)
    {
        return trim(preg_replace('/[\n\t]+|\s+/', ' ', $query));
    }

    public function sanitizeBindings($bindings)
    {
        if (!is_array($bindings)) return [$bindings];
        return $bindings;
    }

    public function buildConditions(array $values)
    {
        if (empty($values)) return [
            'query'     => null,
            'bindings'  => []
        ];

        array_walk($values, function ($value) use (&$operators, &$conditions) {
            $operator = isset($value['operator']) ? $value['operator'] : 'AND';
            $condition = $value['condition'];

            if (count($condition) < 2 || count($condition) > 3) {
                throw new \Exception('Condition must have 2/3 parameters!');
            }

            $operators[] = strtoupper($operator);
            $conditions[] = $condition;
        });

        $bindings = [];
        $query = array_map(function ($operator, $condition) use (&$bindings) {
            if (count($condition) !== count($condition, COUNT_RECURSIVE)) {
                $result = $this->buildConditions($condition);
                $query = $result['query'];
                $bindings = array_merge($bindings, $result['bindings']);
                return "{$operator} ({$query})";
            }

            $bindings[] = $condition[count($condition) > 2 ? 2 : 1];

            $condition[0] = "{$this->mark}{$condition[0]}{$this->mark}";
            $condition[1] = count($condition) < 3 ? '=' : $condition[1];
            $condition[2] = '?';
            $condition = implode(' ', $condition);

            return "{$operator} {$condition}";
        }, $operators, $conditions);

        return [
            'query'     => $this->sanitizeQuery(trim(implode(' ', $query), 'ANDOR ')),
            'bindings'  => $this->sanitizeBindings($bindings)
        ];
    }

    public function buildFields(array $fields, $update = null)
    {
        if (is_null($update)) {
            return implode('', [$this->mark, implode("{$this->mark}, {$this->mark}", array_keys($fields)), $this->mark]);
        }

        if ($update) {
            return implode(', ', array_map(function ($field) {
                return "{$this->mark}{$field}{$this->mark} = ?";
            }, array_keys($fields)));
        }

        return implode(', ', array_map(function ($field) {
            return "? AS {$this->mark}{$field}{$this->mark}";
        }, array_keys($fields)));
    }

    public function buildPlaceholder(array $fields)
    {
        return implode(', ', array_fill(0, count($fields), '?'));
    }

    public function withoutTransaction()
    {
        $this->withTransaction = false;
        return $this;
    }

    public function getConnection()
    {
        return $this->database;
    }

    public function getWhereClause()
    {
        return $this->whereClause;
    }

    public function getMark()
    {
        return $this->mark;
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

    public function getKeyName()
    {
        return $this->primaryKey;
    }

    public function setKeyName($key)
    {
        $this->primaryKey = $key;
        return $this;
    }

    public function getTransactionLevel()
    {
        return $this->transactionLevel;
    }

    public function beginTransaction()
    {
        $this->transactionLevel++;
        $this->transactionCompleted = false;

        if (!$this->getConnection()->inTransaction()) {
            $this->transactionLevelUsed = $this->getTransactionLevel();
            return $this->getConnection()->beginTransaction();
        }

        return null;
    }

    public function commit()
    {
        $this->transactionLevelUsed = 0;
        $this->transactionCompleted = true;
        return $this->getConnection()->commit();
    }

    public function rollBack()
    {
        $this->transactionLevelUsed = 0;
        $this->transactionCompleted = true;
        return $this->getConnection()->rollBack();
    }

    public function toRawSql($query = null, $bindings = null)
    {
        $query = is_null($query) ? $this->query : $query;
        $bindings = is_null($bindings) ? $this->bindings : $bindings;

        if (is_array($query)) {
            return array_map(function ($q, $b) {
                return $this->toRawSql($q, $b);
            }, $query, $bindings);
        }

        $withKeyBinding = count(array_filter(array_keys($bindings), 'is_string')) > 0;
        $sql = str_replace(array_merge(['?'], $withKeyBinding ? array_map(function ($binding) {
            return substr($binding, 0, 1) === ':' ? $binding : ":{$binding}";
        }, array_keys($bindings)) : []), "'%s'", $query);
        return vsprintf($sql, $bindings);
    }

    public function column()
    {
        try {
            if (is_array($this->statement)) {
                if (empty($this->statement)) return [];
                return array_map(function ($statement, $bindings) {
                    $statement->execute($bindings);
                    return $statement->fetchColumn();
                }, $this->statement, $this->bindings);
            }

            if (empty($this->statement)) return null;
            $this->statement->execute($this->bindings);
            return $this->statement->fetchColumn();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function first()
    {
        try {
            if (is_array($this->statement)) {
                if (empty($this->statement)) return [];
                return array_map(function ($statement, $bindings) {
                    $statement->execute($bindings);
                    return $statement->fetch();
                }, $this->statement, $this->bindings);
            }

            if (empty($this->statement)) return null;
            $this->statement->execute($this->bindings);
            return $this->statement->fetch();
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), 500);
        }
    }

    public function get()
    {
        try {
            if (is_array($this->statement)) {
                if (empty($this->statement)) return [];
                return array_map(function ($statement, $bindings) {
                    $statement->execute($bindings);
                    return $statement->fetchAll();
                }, $this->statement, $this->bindings);
            }

            if (empty($this->statement)) return null;
            $this->statement->execute($this->bindings);
            return $this->statement->fetchAll();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function execute()
    {
        if (is_array($this->statement)) {
            return array_map(function ($statement, $bindings) {
                return $statement->execute($bindings);
            }, $this->statement, $this->bindings);
        }

        return $this->statement->execute($this->bindings);
    }

    public function query($query, array $bindings = [])
    {
        $this->query = null;
        $this->bindings = $this->statement = [];

        if (is_array($query)) {
            if (empty($query)) {
                $this->query = $this->bindings = $this->statement = [];
            }
            foreach ($query as $q) {
                if (isset($q['query'])) {
                    $q['bindings'] = isset($q['bindings']) ? $q['bindings'] : [];
                    $this->statement[] = $this->getConnection()->prepare($this->sanitizeQuery($q['query']));
                    $this->query[] = $this->sanitizeQuery($q['query']);
                    $this->bindings[] = $this->sanitizeBindings(empty($q['bindings']) ? [] : $q['bindings']);
                }
            }
        } else {
            if (empty($query)) {
                $this->query = $this->bindings = $this->statement = null;
            }
            $this->statement = $this->getConnection()->prepare($this->sanitizeQuery($query));;
            $this->query = $this->sanitizeQuery($query);
            $this->bindings = $this->sanitizeBindings(empty($bindings) ? [] : $bindings);
        }
        return $this;
    }

    public function create($table, array $values)
    {
        if ($this->withTransaction) $this->beginTransaction();
        $currentTransactionLevel = $this->getTransactionLevel();

        try {
            $result = [];

            if (count($values) != count($values, COUNT_RECURSIVE)) {
                foreach ($values as $value) {
                    $result = array_merge($result, [$this->create($table, $value)]);
                }
            } else {
                $fields = $this->buildFields($values);
                $placeholder = $this->buildPlaceholder($values);

                $this->query(
                    $this->sanitizeQuery("INSERT INTO {$table} ({$fields}) VALUES ({$placeholder})"),
                    $this->sanitizeBindings(array_values($values))
                )->execute();

                $lastInsertId = $this->getConnection()->lastInsertId($this->getKeyName());
                $result = $lastInsertId === 0 ? $values : array_merge([$this->getKeyName() => $lastInsertId], $values);
            }

            if ($currentTransactionLevel === $this->transactionLevelUsed) $this->commit();
            return $result;
        } catch (\Exception $e) {
            if ($currentTransactionLevel === $this->transactionLevelUsed) $this->rollBack();
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function update($table, array $values, array $conditions)
    {
        if ($this->withTransaction) $this->beginTransaction();
        $currentTransactionLevel = $this->getTransactionLevel();

        try {
            $result = [];

            if (count($values) != count($values, COUNT_RECURSIVE)) {
                if (count($values) !== count($conditions)) {
                    throw new \Exception("Invalid number of where for update statement!");
                }

                array_map(function ($values, $conditions) use (&$result, $table) {
                    $result = array_merge($result, [$this->update($table, $values, $conditions)]);
                }, $values, $conditions);
            } else {
                $fields = $this->buildFields($values, true);
                $builder = $this->buildConditions($conditions);

                $this->query(
                    $this->sanitizeQuery("UPDATE {$table} SET {$fields} WHERE {$builder['query']}"),
                    $this->sanitizeBindings(array_merge(array_values($values), $builder['bindings']))
                )->execute();

                $result = $values;
            }

            if ($currentTransactionLevel === $this->transactionLevelUsed) $this->commit();
            return $result;
        } catch (\Exception $e) {
            if ($currentTransactionLevel === $this->transactionLevelUsed) $this->rollBack();
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function delete($table, array $conditions = [])
    {
        if ($this->withTransaction) $this->beginTransaction();
        $currentTransactionLevel = $this->getTransactionLevel();

        try {
            $builder = $this->buildConditions($conditions);
            $query = is_null($builder['query']) ? '' : "WHERE {$builder['query']}";
            $this->query("DELETE FROM {$table} {$query}", $builder['bindings'])->execute();

            if ($currentTransactionLevel === $this->transactionLevelUsed) $this->commit();
            return true;
        } catch (\Exception $e) {
            if ($currentTransactionLevel === $this->transactionLevelUsed) $this->rollBack();
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function upsert($table, array $values, $uniqueBy = [], $update = false)
    {
        if ($this->withTransaction) $this->beginTransaction();
        $currentTransactionLevel = $this->getTransactionLevel();

        try {
            $result = [];
            $uniqueBy = is_array($uniqueBy) ? $uniqueBy : [$uniqueBy];

            if (count($values) != count($values, COUNT_RECURSIVE)) {
                foreach ($values as $value) {
                    $result = array_merge($result, [$this->upsert($table, $value, $uniqueBy, $update)]);
                }
            } else {
                $uniqueBy = array_intersect_key($values, array_flip($uniqueBy));
                $conditions = array_map(function ($field, $value) {
                    return ['condition' => [$field, '=', $value]];
                }, array_keys($uniqueBy), array_values($uniqueBy));

                $builder = $this->buildConditions($conditions);
                $bindings = array_merge(array_values($values), $builder['bindings']);

                $fieldsInsert = $this->buildFields($values);
                $fieldsAlias = $this->buildFields($values, false);
                $fieldsUpdate = $this->buildFields($values, true);
                $fieldsSelect = $this->buildFields($uniqueBy);

                $this->query(array_merge([
                    [
                        'query'     => $this->sanitizeQuery("INSERT INTO {$table} ({$fieldsInsert}) SELECT * FROM (SELECT {$fieldsAlias}) AS temp WHERE NOT EXISTS (SELECT {$fieldsSelect} FROM {$table} WHERE {$builder['query']})"),
                        'bindings'  => $this->sanitizeBindings($bindings),
                    ],
                    $update ? [
                        'query'     => $this->sanitizeQuery("UPDATE {$table} SET {$fieldsUpdate} WHERE {$builder['query']}"),
                        'bindings'  => $this->sanitizeBindings($bindings),
                    ] : []
                ]))->execute();

                $result = $values;
            }

            if ($currentTransactionLevel === $this->transactionLevelUsed) $this->commit();
            return $result;
        } catch (\Exception $e) {
            if ($currentTransactionLevel === $this->transactionLevelUsed) $this->rollBack();
            throw new \Exception($e->getMessage(), 500);
        }
    }
}
