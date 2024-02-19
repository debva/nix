<?php

namespace Debva\Nix;

class Database
{
    protected $database;

    protected $connection;

    protected $whereClause;

    protected $mark;

    protected $primaryKey = null;

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

        $this->connection = $this->getDatabaseManagementSystem($connection);

        try {
            if (!isset($connection, $host, $dbname, $user, $password)) {
                throw new \Exception('Invalid database connection!', 500);
            }

            $dsn = "{$connection}:host={$host};port={$port};dbname={$dbname}";
            $options = [
                \PDO::ATTR_ERRMODE              => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE   => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES     => true,
            ];

            $this->database = new \PDO($dsn, $user, $password, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), 500);
        }
    }

    public function getDatabaseManagementSystem($connection)
    {
        $rdbms = [
            'mysql' => [
                'mark'          => '`',
                'whereClause'   => 'LIKE'
            ],
            'pgsql' => [
                'mark'          => '"',
                'whereClause'   => 'ILIKE'
            ],
        ];

        if (!isset($rdbms[$connection])) {
            throw new \Exception('Database management system is not available');
        }

        $rdbms = $rdbms[$connection];

        $this->mark = $rdbms['mark'];
        $this->whereClause = $rdbms['whereClause'];

        return $connection;
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

    public function getBindingType($value)
    {
        $type = gettype($value);
        $types = [
            'NULL'      => \PDO::PARAM_NULL,
            'integer'   => \PDO::PARAM_INT,
            'string'    => \PDO::PARAM_STR,
            'boolean'   => \PDO::PARAM_BOOL,
        ];
        return in_array($type, array_keys($types)) ? $types[$type] : $types[0];
    }

    public function getStatement()
    {
        return $this->statement;
    }

    public function getKeyName()
    {
        return is_null($this->primaryKey) ? 'id' : $this->primaryKey;
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
                foreach ($bindings as $key => &$value) {
                    $statement->bindParam((is_int($key) ? $key + 1 : $key), $value, $this->getBindingType($value));
                }
                $statement->execute();
                return $statement;
            }, $this->statement, $this->bindings);
        }

        foreach ($this->bindings as $key => &$value) {
            $this->statement->bindParam((is_int($key) ? $key + 1 : $key), $value, $this->getBindingType($value));
        }
        $this->statement->execute();
        return $this->statement;
    }

    public function getLastId($statement, $table = null, $value = [])
    {
        if (!in_array($this->connection, ['pgsql'])) {
            $builder = $this->buildConditions(array_map(function ($value, $key) {
                return ['condition' => [$key, $value]];
            }, array_values($value), array_keys($value)));

            return $this->query(
                "SELECT {$this->mark}{$this->getKeyName()}{$this->mark} FROM {$table} WHERE {$builder['query']}",
                $builder['bindings']
            )->column();
        }

        return $statement->fetchColumn();
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
                $mark = $this->getMark();
                $table = "{$mark}{$table}{$mark}";
                $fields = $this->buildFields($values);
                $placeholder = $this->buildPlaceholder($values);
                $keyName = $this->getKeyName();

                if (in_array($this->connection, ['pgsql'])) {
                    $statement = $this->query(
                        $this->sanitizeQuery("INSERT INTO {$table} ({$fields}) VALUES ({$placeholder}) RETURNING {$keyName}"),
                        $this->sanitizeBindings(array_values($values))
                    )->execute();
                } else {
                    $statement = $this->query(
                        $this->sanitizeQuery("INSERT INTO {$table} ({$fields}) VALUES ({$placeholder})"),
                        $this->sanitizeBindings(array_values($values))
                    )->execute();
                }

                $lastInsertId = $this->getLastId($statement, $table, $values);
                $result = $lastInsertId === null ? $values : array_merge(
                    [(is_null($this->getKeyName()) ? 'id' : $this->getKeyName()) => $lastInsertId],
                    $values
                );
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
                $mark = $this->getMark();
                $table = "{$mark}{$table}{$mark}";
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
            $mark = $this->getMark();
            $table = "{$mark}{$table}{$mark}";
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

            if (count($values) != count($values, COUNT_RECURSIVE)) {
                foreach ($values as $value) {
                    $result = array_merge($result, [$this->upsert($table, $value, $uniqueBy, $update)]);
                }
            } else {
                $mark = $this->getMark();
                $table = "{$mark}{$table}{$mark}";
                $uniqueBy = array_intersect_key($values, array_flip(array_filter(is_array($uniqueBy) ? $uniqueBy : [$uniqueBy])));
                $conditions = array_map(function ($field, $value) {
                    return ['condition' => [$field, '=', $value]];
                }, array_keys($uniqueBy), array_values($uniqueBy));

                $fieldsInsert = $this->buildFields($values);

                if (empty($conditions)) {
                    $this->query(
                        $this->sanitizeQuery("INSERT INTO {$table} ({$fieldsInsert}) VALUES ({$this->buildPlaceholder($values)})"),
                        $this->sanitizeBindings(array_values($values))
                    )->execute();
                } else {
                    $builder = $this->buildConditions($conditions);
                    $bindings = array_merge(array_values($values), $builder['bindings']);

                    $fieldsUpdate = $this->buildFields($values, true);
                    $fieldsAlias = $this->buildFields($values, false);
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
                }

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
