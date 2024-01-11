<?php

namespace Debva\Nix;

abstract class Environment
{
    public function __construct()
    {
        if (!isset($_ENV['_NIX_VERSION'])) {
            $envPath = (new Storage)->basePath() . DIRECTORY_SEPARATOR . '.env';

            if (!file_exists($envPath)) {
                $defaultEnvPath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'stubs', 'env.stub']);

                if (!copy($defaultEnvPath, $envPath)) {
                    throw new \Exception('Environment file cannot be created!');
                }
            }

            if (!defined('FRAMEWORK_VERSION')) {
                define('FRAMEWORK_VERSION', '1.5.0');
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
