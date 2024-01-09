<?php

namespace Debva\Nix;

class Session
{
    protected $lifetime = 86400;

    public function __call($method, $arguments)
    {
        if (!method_exists($this, $method)) {
            throw new \Exception("Method [$method] does not exist.");
        }

        $session = isset($_SESSION) ? $_SESSION : null;

        if (!isset($session) || (!isset($session['__expires_at']) && $session['__expires_at'] < time())) {
            throw new \Exception('Session not found!', 404);
        }

        return $this->{$method}(...$arguments);
    }

    public function toSessionName($name)
    {
        $name = is_null($name) ? time() : $name;
        return preg_match('/^[a-f0-9]{32}$/', $name) ? $name : md5($name);
    }

    public function start($name = null)
    {
        $name = $this->toSessionName($name);
        $lifetime = env('SESSION_LIFETIME');

        $this->lifetime = isset($this->lifetime)
            ? $this->lifetime : ((isset($lifetime) && is_int((int) $lifetime)) ? $lifetime : 3600);

        if (!isset($_SESSION, $_SESSION['__name'], $_SESSION['__expires_at'])) $_SESSION = ['__name' => $name, '__expires_at' => $this->getExpires()];
        else if (!$this->compare($this->getName(), $name)) $_SESSION = array_merge($_SESSION, ['__name' => $name, '__expires_at' => $this->getExpires()]);
        return $_SESSION;
    }

    public function compare($currentSession, $newSession)
    {
        return $currentSession === $newSession;
    }

    public function getName()
    {
        return isset($_SESSION['__name']) ? $_SESSION['__name'] : null;
    }

    protected function load($session)
    {
        if (!is_array($session) || !isset($session['__name'], $session['__expires_at'])) {
            throw new \Exception('Session not valid!', 400);
        }

        $_SESSION = $session;

        return $_SESSION;
    }

    protected function getLifetime()
    {
        return $this->lifetime;
    }

    protected function getExpires()
    {
        return time() + $this->lifetime;
    }

    protected function setLifetime($lifetime)
    {
        $this->lifetime = (int) $lifetime;
        return $this;
    }

    protected function set($key, $value = null)
    {
        if (is_array($key)) foreach ($key as $key => $value) $_SESSION = array_merge($_SESSION, [$key => $value]);
        else $_SESSION = array_merge($_SESSION, [$key => $value]);
        return true;
    }

    protected function remove($key)
    {
        if (!isset($_SESSION[$key])) return false;
        unset($_SESSION[$key]);
        return true;
    }

    protected function destroy()
    {
        unset($_SESSION);
        return true;
    }

    protected function serialize($data = null)
    {
        return serialize(isset($data) ? $data : $_SESSION);
    }

    protected function unserialize($data = null)
    {
        return unserialize(isset($data) ? $data : $this->serialize());
    }
}
