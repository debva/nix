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

    public function start($name = null, $lifetime = null)
    {
        $this->name = is_null($name) ? $this->name : md5($name);

        $this->expiresAt = is_null($lifetime) ? env('SESSION_LIFETIME', null) : $lifetime;

        if (!is_null($this->expiresAt) && !is_int($this->expiresAt)) {
            throw new \Exception('Session lifetime must be an integer!');
        }

        $fields = implode(', ', $this->fields);

        db()->query(
            "INSERT INTO {$this->table} ($fields)
            SELECT * FROM (SELECT :a AS `{$this->fields[0]}`, :b AS `{$this->fields[1]}`, :c AS `{$this->fields[2]}`) AS temp 
            WHERE NOT EXISTS (SELECT `{$this->fields[0]}` FROM {$this->table} WHERE `{$this->fields[0]}` = :a)",
            [
                'a' => $this->name,
                'b' => serialize($this->data),
                'c' => is_null($this->expiresAt) ? null : date('Y-m-d H:i:s', ($this->expiresAt + time())),
            ]
        )->execute();

        return $this->name;
    }

    public function renew($name = null, $lifetime = null)
    {
        $session = db()->query(
            "SELECT {$this->fields[1]}, {$this->fields[2]} FROM {$this->table} WHERE {$this->fields[0]} = :a",
            ['a' => $this->name]
        )->first();

        if (!$session || (!is_null($session['expires_at']) && strtotime($session['expires_at']) < time())) {
            throw new \Exception('Session not found!', 404);
        }

        $this->expiresAt = is_null($lifetime) ? env('SESSION_LIFETIME', null) : $lifetime;

        $name = is_null($name) ? $this->name : md5($name);

        db()->query(
            "UPDATE {$this->table} SET {$this->fields[0]} = :d, {$this->fields[2]} = :c WHERE {$this->fields[0]} = :a",
            [
                'a' => $this->name,
                'c' => is_null($this->expiresAt) ? null : date('Y-m-d H:i:s', ($this->expiresAt + time())),
                'd' => $name
            ]
        )->execute();

        return $this->name = $name;
    }

    public function set($key, $value = null)
    {
        $session = db()->query(
            "SELECT {$this->fields[1]}, {$this->fields[2]} FROM {$this->table} WHERE {$this->fields[0]} = :a",
            ['a' => $this->name]
        )->first();

        if (!$session || (!is_null($session['expires_at']) && strtotime($session['expires_at']) < time())) {
            throw new \Exception('Session not found!', 404);
        }

        $data = unserialize($session['data']);

        if (is_array($key)) {
            foreach ($key as $key => $value) $data = array_merge($data, [$key => $value]);
        } else {
            $data = array_merge($data, [$key => $value]);
        }

        db()->query(
            "UPDATE {$this->table} SET {$this->fields[1]} = :b WHERE {$this->fields[0]} = :a",
            ['a' => $this->name, 'b' => serialize($data)]
        )->execute();

        return true;
    }

    public function get($key = null)
    {
        $session = db()->query(
            "SELECT {$this->fields[1]}, {$this->fields[2]} FROM {$this->table} WHERE {$this->fields[0]} = :a",
            ['a' => $this->name]
        )->first();

        if (!$session || (!is_null($session['expires_at']) && strtotime($session['expires_at']) < time())) {
            throw new \Exception('Session not found!', 404);
        }

        $data = unserialize($session['data']);

        return is_null($key) ? $data : (isset($data[$key]) ? $data[$key] : false);
    }

    public function delete($key)
    {
        $session = db()->query(
            "SELECT {$this->fields[1]}, {$this->fields[2]} FROM {$this->table} WHERE {$this->fields[0]} = :a",
            ['a' => $this->name]
        )->first();

        if (!$session || (!is_null($session['expires_at']) && strtotime($session['expires_at']) < time())) {
            throw new \Exception('Session not found!', 404);
        }

        $data = unserialize($session['data']);
        unset($data[$key]);

        db()->query(
            "UPDATE {$this->table} SET {$this->fields[1]} = :b WHERE {$this->fields[0]} = :a",
            ['a' => $this->name, 'b' => serialize($data)]
        )->execute();

        return true;
    }

    public function destroy()
    {
        db()->query(
            "DELETE FROM {$this->table} WHERE {$this->fields[0]} = :a",
            ['a' => $this->name]
        )->execute();

        return true;
    }

    public function purge()
    {
        db()->query("DELETE FROM {$this->table} WHERE {$this->fields[2]} < NOW()")->execute();

        return true;
    }
}
