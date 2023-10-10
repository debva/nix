<?php

namespace Debva\Nix;

class Session extends Environment
{
    protected $session;

    protected $sessionPath;

    public function __construct()
    {
        parent::__construct();

        $this->sessionPath = implode(DIRECTORY_SEPARATOR, [$this->rootPath, 'storage', 'sessions']);

        if (!file_exists($this->sessionPath)) {
            mkdir($this->sessionPath, 0755, true);
        }
    }

    public function __invoke($session)
    {
        $this->session = md5($session);
    }

    public function start($name = null)
    {
        $session = !is_null($name) ? md5($name) : md5(microtime(true));
        $sessionFile = implode(DIRECTORY_SEPARATOR, [$this->sessionPath, $session]);
        if (!file_exists($sessionFile)) {
            file_put_contents($sessionFile, serialize([]));
        }

        return $session;
    }

    public function set($key, $value)
    {
        if (!$this->session) {
            return false;
        }

        $sessionFile = implode(DIRECTORY_SEPARATOR, [$this->sessionPath, $this->session]);
        if (!file_exists($sessionFile)) {
            return false;
        }

        $data = unserialize(file_get_contents($sessionFile));
        file_put_contents($sessionFile, serialize(array_merge($data, [$key => $value])));
        return true;
    }

    public function get($key)
    {
        if (!$this->session) {
            return false;
        }

        $sessionFile = implode(DIRECTORY_SEPARATOR, [$this->sessionPath, $this->session]);
        if (!file_exists($sessionFile)) {
            return false;
        }

        $data = unserialize(file_get_contents($sessionFile));
        if (!isset($data[$key])) {
            return false;
        }

        return $data[$key];
    }

    public function delete($key)
    {
        if (!$this->session) {
            return false;
        }

        $sessionFile = implode(DIRECTORY_SEPARATOR, [$this->sessionPath, $this->session]);
        if (!file_exists($sessionFile)) {
            return false;
        }

        $data = unserialize(file_get_contents($sessionFile));
        if (!isset($data[$key])) {
            return false;
        }

        unset($data[$key]);
        file_put_contents($sessionFile, serialize($data));
        return true;
    }

    public function renew($name = null)
    {
        if (!$this->session) {
            return false;
        }

        $sessionFile = implode(DIRECTORY_SEPARATOR, [$this->sessionPath, $this->session]);
        if (!file_exists($sessionFile)) {
            return false;
        }

        $session = !is_null($name) ? md5($name) : md5(microtime(true));
        if (!rename($sessionFile, implode(DIRECTORY_SEPARATOR, [dirname($sessionFile), $session]))) {
            return false;
        }

        return true;
    }

    public function destroy()
    {
        if (!$this->session) {
            return false;
        }

        $sessionFile = implode(DIRECTORY_SEPARATOR, [$this->sessionPath, $this->session]);
        if (!file_exists($sessionFile)) {
            return false;
        }

        unlink($sessionFile);
        return true;
    }

    public function purge()
    {
        $deletedFiles = array_map('unlink', glob(implode(DIRECTORY_SEPARATOR, [$this->sessionPath, '*'])));
        if (in_array(false, $deletedFiles, true)) {
            return false;
        }

        return true;
    }
}
