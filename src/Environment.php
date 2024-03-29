<?php

namespace Debva\Nix;

abstract class Environment
{
    public function __construct()
    {
        if (!isset($_ENV['_NIX_VERSION'])) {
            $envPath = basePath('.env');

            if (!file_exists($envPath)) {
                $defaultEnvPath = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'stubs', 'env.stub']));

                if ($defaultEnvPath && !copy($defaultEnvPath, $envPath)) {
                    throw new \Exception('Environment file cannot be created!');
                }
            }

            $_ENV['_NIX_VERSION'] = FRAMEWORK_VERSION;
            $lines = preg_split('/\r\n|\r|\n/', file_get_contents($envPath));

            foreach ($lines as $line) {
                $line = trim($line);

                if (!$line || strpos($line, '#') === 0) continue;
                list($key, $value) = explode('=', $line, 2);

                $key = trim($key);
                $value = trim($value, "\"");

                if (!array_key_exists($key, $_ENV)) {
                    putenv(sprintf('%s=%s', $key, $value));
                    $_ENV[$key] = $value;
                }
            }
        }
    }
}
