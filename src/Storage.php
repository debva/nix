<?php

namespace Debva\Nix;

class Storage
{
    protected $basePath;

    protected $storagePath;

    public function __construct()
    {
        if (!$this->basePath) {
            $this->basePath = trim(realpath(implode(DIRECTORY_SEPARATOR, [getcwd(), '..'])));
            $this->storagePath = implode(DIRECTORY_SEPARATOR, [$this->basePath, 'storage']);
        }
    }

    public function basePath($path = null, $recursive = false)
    {
        if (!is_null($path)) {
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim(str_replace($this->basePath, '', $path), '/\\'));
        }

        $path = implode(DIRECTORY_SEPARATOR, array_filter([$this->basePath, $path]));
        if ($recursive && !realpath($path)) mkdir($path, 0755, true);
        return $path;
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

        if (file_exists($filepath) && !$force) return false;

        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        return file_put_contents($filepath, $content);
    }

    public function put($filepath, $content, $force = false)
    {
        $filepath = implode(DIRECTORY_SEPARATOR, [
            $this->storagePath,
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($filepath, '\\/'))
        ]);

        if (file_exists($filepath) && !$force) return false;

        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        return file_put_contents($filepath, $content);
    }

    public function append($filepath, $content)
    {
        $filepath = implode(DIRECTORY_SEPARATOR, [
            $this->storagePath,
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($filepath, '\\/'))
        ]);

        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        return file_put_contents($filepath, $content, FILE_APPEND);
    }

    public function rename($src, $dst)
    {
        $src = implode(DIRECTORY_SEPARATOR, [
            $this->storagePath,
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($src, '\\/'))
        ]);

        $dst = implode(DIRECTORY_SEPARATOR, [
            $this->storagePath,
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($dst, '\\/'))
        ]);

        if (!file_exists(dirname($dst))) {
            mkdir(dirname($dst), 0755, true);
        }

        return rename($src, $dst);
    }

    public function get($filepath)
    {
        $filepath = implode(DIRECTORY_SEPARATOR, [
            $this->storagePath,
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($filepath, '\\/'))
        ]);

        if (!file_exists($filepath)) return false;

        return file_get_contents($filepath);
    }

    public function delete($filepath)
    {
        $filepath = implode(DIRECTORY_SEPARATOR, [
            $this->storagePath,
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($filepath, '\\/'))
        ]);

        if (!file_exists($filepath)) return false;

        return unlink($filepath);
    }

    public function scan($path, $recursive = true, $suffix = '*')
    {
        $files = [];
        $paths = glob(implode(DIRECTORY_SEPARATOR, [$this->basePath($path), $suffix]));

        foreach ($paths as $path) {
            if (is_dir($path) && $recursive) $files = array_merge($files, $this->scan($path, $recursive, $suffix));
            else $files[] = $path;
        }

        return $files;
    }
}
