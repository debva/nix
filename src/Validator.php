<?php

namespace Debva\Nix;

class Validator
{
    protected $request;

    protected $rules;

    protected $messages;

    protected $customRules;

    public function __invoke($rules, $options = [])
    {
        extract($options, EXTR_PREFIX_ALL, 'opts');

        $this->rules = $rules;
        $this->messages = isset($opts_messages) ? $opts_messages : [];
        $this->customRules = isset($opts_rules) ? $opts_rules : [];

        $attributeWithRule = $errors = [];

        $availableRules = array_filter(get_class_methods($this), function ($item) {
            return strpos($item, 'rule') === 0;
        });

        foreach ($this->rules as $attribute => $rules) {
            if (!is_array($rules)) $rules  = explode('|', $rules);
            foreach ($rules as $rule) {
                if (!empty($rule)) {
                    $parameter = explode(':', $rule);
                    $rule = reset($parameter);

                    $parameter = (count($parameter) > 1) ? explode(',', end($parameter)) : null;
                    $method = 'rule' . str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($rule))));

                    if (in_array($method, $availableRules)) {
                        $attributeWithRule[$attribute][] = [
                            'name'      => $rule,
                            'method'    => $method,
                            'parameter' => $parameter,
                        ];
                    } else {
                        if (isset($this->customRules[$rule]) && $this->customRules[$rule] instanceof \Closure) {
                            $attributeWithRule[$attribute][] = [
                                'name'      => $rule,
                                'method'    => $this->customRules[$rule],
                                'parameter' => $parameter,
                            ];
                        } else {
                            throw new \Exception("Rule {$rule} not found!", 500);
                        }
                    }
                } else {
                    $attributeWithRule[$attribute][] = [
                        'name'      => $rule,
                        'method'    => null,
                        'parameter' => null,
                    ];
                }
            }
        }

        foreach ($attributeWithRule as $attribute => $rules) {
            foreach ($rules as $rule) {
                $name = $rule['name'];
                $method = $rule['method'];
                $parameter = $rule['parameter'] ? $rule['parameter'] : [];
                $value = request($attribute);

                $validate = ($method instanceof \Closure)
                    ? $method($value, ...$parameter)
                    : (is_null($method)
                        ? null
                        : ((in_array($name, ['required']) || !empty($value))
                            ? $this->{$method}($value, ...$parameter)
                            : null)
                    );

                if (is_string($validate)) {
                    $message = in_array($name, array_keys($this->messages)) ? $this->messages[$name] : $validate;
                    $message = str_replace(':attribute', $attribute, $message);
                    foreach ($parameter as $index => $value) {
                        $index++;
                        $message = str_replace(":param{$index}", $value, $message);
                    }

                    if (!empty($message) && is_string($message) && $message !== '1' && $message !== '0') {
                        $errors[$attribute][] = $message;
                    }
                }
            }
        }

        if (!empty($errors)) {
            print(response(['validation' => $errors], 400));
            exit(0);
        }

        $request = request(array_keys($attributeWithRule));
        $request = $request ? $request : [];
        return $request;
    }

    public function ruleRequired($value)
    {
        if (is_null($value) || (is_array($value) && empty($value)) || (is_string($value) && strlen(trim($value)) === 0)) {
            return 'The :attribute is required';
        }
    }

    public function ruleDate($value, ...$params)
    {
        $format = reset($params);
        if ($format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) return true;
        } else if (strlen($value) > 1 && strtotime($value) !== false) return true;

        return trim(implode(' ', ['The :attribute is not valid date format', $format]));
    }

    public function ruleEmail($value)
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return 'The :attribute field must be a valid email address';
        }
    }

    public function ruleNumeric($value)
    {
        if (!is_numeric($value)) {
            return 'The :attribute field must be a number';
        }
    }

    public function ruleInteger($value)
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return 'The :attribute field must be an integer';
        }
    }

    public function ruleString($value)
    {
        if (!(is_string($value) && !is_numeric($value))) {
            return 'The :attribute field must be a string';
        }
    }

    public function ruleBoolean($value)
    {
        if (!is_bool($value)) {
            return 'The :attribute field must be a boolean';
        }
    }

    public function ruleArray($value)
    {
        if (!is_array($value)) {
            return 'The :attribute field must be an array';
        }
    }

    public function ruleIn($value, ...$params)
    {
        $in = array_filter($params);
        if (!empty($in) && !in_array($value, $in)) {
            return 'The :attribute field must exist in ' . implode(', ', $params);
        }
    }

    public function ruleNotIn($value, ...$params)
    {
        if (in_array($value, $params)) {
            return 'The :attribute field must not exist in ' . implode(', ', $params);
        }
    }

    public function ruleUnique($value, ...$params)
    {
        $bindings = ['column' => $value];

        $params = array_replace([null, 'id'], $params);
        list($table, $column) = $params;

        $conditions = array_slice($params, 2);

        $table = array_replace([null, null], array_reverse(array_filter(explode('.', $table))));
        list($table, $connection) = $table;

        $column = array_replace([null, null], array_filter(explode('=', $column)));
        list($column, $ignore) = $column;

        foreach ($conditions as $index => $condition) {
            if (preg_match('/(\w+)\s*([=<>!]+)\s*(\w+)/', $condition, $matches)) {
                $value = trim($matches[3]);
                $value = (substr($value, 0, 1) === '{' && substr($value, -1) === '}') ? request(trim($value, '{}'))  : $value;
                $condition = [trim($matches[1]), trim($matches[2]), $value];
            }

            if (!empty($condition) && (count($condition) < 3 || count($condition) > 3)) {
                throw new \Exception('Unique condition must have a column, operator, and value!');
            }

            $bindings = array_merge($bindings, ["condition_{$index}" => end($condition)]);
            array_splice($condition, 2, 1, ":condition_{$index}");
            $conditions[] = $condition;
        }

        $conditions = implode(' AND ', array_map(function ($condition) {
            return trim(implode(' ', $condition));
        }, array_values(array_filter($conditions, 'is_array'))));

        if (substr($ignore, 0, 1) === '{' && substr($ignore, -1) === '}') {
            $ignore = request(trim($ignore, '{}'));
        }

        if (!is_null($ignore)) {
            $bindings = array_merge($bindings, ['ignore' => $ignore]);
            $ignore = "{$column} != :ignore";
        }

        $conditions = implode(' AND ', array_filter(["{$column} = :column", $ignore, $conditions]));
        $where = "WHERE {$conditions}";

        $exists = db($connection)->query("SELECT {$column} FROM {$table} {$where} LIMIT 1", $bindings)->column();

        if ($exists) {
            return 'The :attribute has already been taken';
        }
    }

    public function ruleFile($value)
    {
        if (!(is_array($value) && empty(array_filter(array_diff(array_keys($value), ['name', 'mimeType', 'type', 'path', 'error', 'size']))))) {
            return 'The :attribute field must be a file';
        }
    }

    public function ruleBase64($value)
    {
        if (!secret()->isBase64($value)) {
            return 'The :attribute field must be of type base64';
        }
    }

    public function ruleMimes($value, ...$params)
    {
        if (true) {
            return 'The :attribute field must be a file of type: ' . implode(', ', $params);
        }
    }

    public function ruleMin($value, ...$params)
    {
        if (true) {
            return 'The :attribute field must have at least ' . implode(', ', $params);
        }
    }

    public function ruleMax($value, ...$params)
    {
        if (true) {
            return 'The :attribute field must not be greater than ' . implode(', ', $params);
        }
    }
}
