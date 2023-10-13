<?php

namespace Debva\Nix;

class Session
{
    protected $name;

    protected $data = [];

    protected $expiresAt;

    protected $table;

    protected $fields;

    public function __construct($name = null)
    {
        $table = env('SESSION_TABLE', 'sessions:name,data,expires_at');
        $table = array_filter(explode(':', $table));

        if (count($table) < 2) {
            throw new \Exception('Session table not valid!', 500);
        }

        list($table, $fields) = $table;
        $fields = array_filter(explode(',', $fields));

        if (count($fields) < 3) {
            throw new \Exception('Session table not valid!', 500);
        }

        $this->name = is_null($name)
            ? (isset($headers['Authorization'])
                ? md5($headers['Authorization'])
                : md5(microtime(true)))
            : $name;

        $this->table = $table;

        $this->fields = $fields;
    }

    public function start($name = null)
    {
        $this->name = is_null($name) ? $this->name : md5($name);

        $this->expiresAt = env('SESSION_LIFETIME', null);

        if (!is_null($this->expiresAt) && !is_int($this->expiresAt)) {
            throw new \Exception('Session lifetime must be an integer!');
        }

        $fields = implode(', ', $this->fields);

        query(
            "INSERT INTO {$this->table} ($fields)
            SELECT * FROM (SELECT :a AS `{$this->fields[0]}`, :b AS `{$this->fields[1]}`, :c AS `{$this->fields[2]}`) AS temp 
            WHERE NOT EXISTS (SELECT `{$this->fields[0]}` FROM {$this->table} WHERE `{$this->fields[0]}` = :a)",
            [
                'a' => $this->name,
                'b' => serialize($this->data),
                'c' => is_null($this->expiresAt) ? null : date('Y-m-d H:i:s', ($this->expiresAt + time())),
            ]
        );

        return $this->name;
    }

    public function renew($name = null)
    {
        $session = query(
            "SELECT {$this->fields[1]}, {$this->fields[2]} FROM {$this->table} WHERE {$this->fields[0]} = :a",
            ['a' => $this->name]
        )->fetchObject();

        if (!$session || (!is_null($session->expires_at) && $session->expires_at < time())) {
            throw new \Exception('Session not found!', 404);
        }

        $this->expiresAt = env('SESSION_LIFETIME', null);

        query(
            "UPDATE {$this->table} SET {$this->fields[0]} = :d, {$this->fields[2]} = :c WHERE {$this->fields[0]} = :a",
            [
                'a' => $this->name,
                'c' => is_null($this->expiresAt) ? null : date('Y-m-d H:i:s', ($this->expiresAt + time())),
                'd' => is_null($name) ? $this->name : md5($name)
            ]
        );

        return $this->name;
    }

    public function put($key, $value)
    {
        $session = query(
            "SELECT {$this->fields[1]}, {$this->fields[2]} FROM {$this->table} WHERE {$this->fields[0]} = :a",
            ['a' => $this->name]
        )->fetchObject();

        if (!$session || (!is_null($session->expires_at) && $session->expires_at < time())) {
            throw new \Exception('Session not found!', 404);
        }

        $data = serialize(array_merge(unserialize($session->data), [$key => $value]));

        query(
            "UPDATE {$this->table} SET {$this->fields[1]} = :b WHERE {$this->fields[0]} = :a",
            ['a' => $this->name, 'b' => $data]
        );

        return true;
    }

    public function get($key = null)
    {
        $session = query(
            "SELECT {$this->fields[1]}, {$this->fields[2]} FROM {$this->table} WHERE {$this->fields[0]} = :a",
            ['a' => $this->name]
        )->fetchObject();

        if (!$session || (!is_null($session->expires_at) && $session->expires_at < time())) {
            throw new \Exception('Session not found!', 404);
        }

        $data = unserialize($session->data);

        return is_null($key) ? $data : (isset($data[$key]) ? $data[$key] : false);
    }

    public function delete($key)
    {
        $session = query(
            "SELECT {$this->fields[1]}, {$this->fields[2]} FROM {$this->table} WHERE {$this->fields[0]} = :a",
            ['a' => $this->name]
        )->fetchObject();

        if (!$session || (!is_null($session->expires_at) && $session->expires_at < time())) {
            throw new \Exception('Session not found!', 404);
        }

        $data = unserialize($session->data);
        unset($data[$key]);

        query(
            "UPDATE {$this->table} SET {$this->fields[1]} = :b WHERE {$this->fields[0]} = :a",
            ['a' => $this->name, 'b' => serialize($data)]
        );

        return true;
    }

    public function destroy()
    {
        query(
            "DELETE FROM {$this->table} WHERE {$this->fields[0]} = :a",
            ['a' => $this->name]
        );

        return true;
    }

    public function purge()
    {
        query("DELETE FROM {$this->table} WHERE {$this->fields[2]} < NOW()");

        return true;
    }
}
