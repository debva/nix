<?php

namespace Debva\Nix;

class Environment
{
    public $rootPath;

    public function __construct()
    {
        if (!$this->rootPath) {
            $scriptPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']);
            $this->rootPath = _startsWith($scriptPath, getcwd()) ? $scriptPath : implode(DIRECTORY_SEPARATOR, [getcwd(), $scriptPath]);
            $this->rootPath = realpath(implode(DIRECTORY_SEPARATOR, [dirname($this->rootPath), '..']));
        }

        if (empty($_ENV)) {
            $envpath = implode(DIRECTORY_SEPARATOR, [$this->rootPath, '.env']);

            if (!file_exists($envpath)) {
                $defaultenvpath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '.env.example']);
                copy($defaultenvpath, $envpath);
            }

            $env = file_get_contents($envpath);
            $lines = preg_split('/\r\n|\r|\n/', $env);

            foreach ($lines as $line) {
                $line = trim($line);

                if (!$line || strpos($line, '#') === 0) continue;
                list($name, $value) = explode('=', $line, 2);

                $name = trim($name);
                $value = trim($value, "\"");

                if (!array_key_exists($name, $_ENV) && !array_key_exists($name, $_SERVER)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                }
            }
        }
    }
}
