<?php

namespace Debva\Nix;

class Storage
{
    protected $basePath;

    protected $storagePath;

    public function __construct()
    {
        if (!$this->basePath) {
            $scriptPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']);
            $this->basePath = startsWith($scriptPath, getcwd()) ? $scriptPath : implode(DIRECTORY_SEPARATOR, [getcwd(), $scriptPath]);
            $this->basePath = realpath(implode(DIRECTORY_SEPARATOR, [dirname($this->basePath), '..']));
            $this->storagePath = implode(DIRECTORY_SEPARATOR, [$this->basePath(), 'storage']);
        }
    }

    public function basePath($path = null, $make = false)
    {
        if (!is_null($path)) {
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($path, '/\\'));
        }

        $path = implode(DIRECTORY_SEPARATOR, array_filter([$this->basePath, $path]));
        if ($make && !realpath($path)) mkdir($path, 0755, true);
        return realpath($path);
    }

    public function exists($filepath)
    {
        $filepath = implode(DIRECTORY_SEPARATOR, [
            $this->storagePath,
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($filepath, '\\/'))
        ]);

        return file_exists($filepath);
    }

    public function save($filepath, $content, $force = false)
    {
        $filepath = implode(DIRECTORY_SEPARATOR, [
            $this->storagePath,
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($filepath, '\\/'))
        ]);

        if (file_exists($filepath) && !$force) {
            throw new \Exception('File already exists!');
        }

        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        return file_put_contents($filepath, $content);
    }

    public function get($filepath)
    {
        $filepath = implode(DIRECTORY_SEPARATOR, [
            $this->storagePath,
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($filepath, '\\/'))
        ]);

        if (!file_exists($filepath)) {
            throw new \Exception('File not found!');
        }

        return file_get_contents($filepath);
    }

    public function delete($filepath)
    {
        $filepath = implode(DIRECTORY_SEPARATOR, [
            $this->storagePath,
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($filepath, '\\/'))
        ]);

        if (!file_exists($filepath)) {
            throw new \Exception('File not found!');
        }

        return unlink($filepath);
    }

    public function scan($filepath, $suffix = '*')
    {
        $filepath = implode(DIRECTORY_SEPARATOR, [
            $this->storagePath,
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($filepath, '\\/'))
        ]);

        return array_filter(glob(implode(DIRECTORY_SEPARATOR, [$filepath, $suffix])), 'file_exists');
    }
}
