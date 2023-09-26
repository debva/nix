<?php

namespace Debva\Nix;

class Environment
{
    public function __construct()
    {
        if (empty($_ENV)) {
            $envpath = implode(DIRECTORY_SEPARATOR, [getcwd(), '..', '.env']);
    
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
