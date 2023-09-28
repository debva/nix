<?php

namespace Debva\Nix;

class Console
{
    private $command;

    private $argument = [];

    public function __construct()
    {
        $args = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];
        $this->command = isset($args[1]) ? $args[1] : null;
        $this->argument = isset($args[2]) ? array_slice($args, 2) : [];
    }

    public function __invoke()
    {
    }

    protected function write($text, $color = null, $background = null)
    {
        $palette = [
            'reset-color'   => 0,
            'text-black'    => 30,
            'text-red'      => 31,
            'text-green'    => 32,
            'text-yellow'   => 33,
            'text-blue'     => 34,
            'text-magenta'  => 35,
            'text-cyan'     => 36,
            'text-white'    => 37,
            'bg-black'      => 40,
            'bg-red'        => 41,
            'bg-green'      => 42,
            'bg-yellow'     => 43,
            'bg-blue'       => 44,
            'bg-magenta'    => 45,
            'bg-cyan'       => 46,
            'bg-white'      => 47,
        ];

        if (is_null($color) and is_null($background)) {
            return print("{$text}\033[{$palette['reset-color']}m");
        }

        if ((!is_null($color) xor !is_null($background)) and (isset($palette[$color]) or isset($palette[$background]))) {
            $color = isset($palette[$color]) ? $palette[$color] : $palette[$background];
            return print("\033[{$color}m{$text}\033[{$palette['reset-color']}m");
        }

        if ((!is_null($color) and !is_null($background)) and (isset($palette[$color]) and isset($palette[$background]))) {
            return print("\033[{$palette[$color]};{$palette[$background]}m{$text}\033[{$palette['reset-color']}m");
        }

        return print($text);
    }

    protected function success($text)
    {
        $this->write("\n Success ", 'text-black', 'bg-green');
        $this->write(" {$text}\n", 'text-white');
    }

    protected function warning($text)
    {
        $this->write("\n Warning ", 'text-black', 'bg-yellow');
        $this->write(" {$text}\n", 'text-white');
    }

    protected function error($text)
    {
        $this->write("\n Error ", 'text-white', 'bg-red');
        $this->write(" {$text}\n", 'text-white');
    }

    protected function info($text)
    {
        $this->write("\n Info ", 'text-black', 'bg-cyan');
        $this->write(" {$text}\n", 'text-white');
    }

    protected function input($text, $color = null, $background = null)
    {
        $this->write($text, $color, $background);
        $command = trim(fgets(STDIN));
        return explode(' ', $command);
    }

    protected function argument(...$keys)
    {
        $keys = is_string($keys) ? [$keys] : flattenArray($keys);

        if (count($keys) === 1) {
            return $this->argument[end($keys)];
        }

        if (count($keys) > 1) {
            return array_map(function ($key) {
                return $this->argument[$key];
            }, $keys);
        }

        return $this->argument;
    }

    protected function generateFile($title, $stub, $folder, $filepath, $search = [], $replace = [])
    {
        $stubpath = join(DIRECTORY_SEPARATOR, [getcwd(), '..', 'stubs', $stub . '.stub']);
        if (!file_exists($stubpath)) $stubpath = join(DIRECTORY_SEPARATOR, [__DIR__, '..', 'stubs', $stub . '.stub']);

        if (file_exists($stubpath)) {
            $stub = file_get_contents($stubpath);
            $stub = str_replace($search, $replace, $stub);

            $filepath = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $filepath), '\/');
            $filename = implode(DIRECTORY_SEPARATOR, [dirname($filepath), pathinfo($filepath, PATHINFO_FILENAME)]);

            if (file_exists($filepath = join(DIRECTORY_SEPARATOR, [getcwd(), '..', 'server', $folder, $filepath]))) {
                $this->error("{$title} {$filename} exists");
                exit;
            }

            if (!file_exists($path = dirname($filepath))) {
                mkdir($path, 0755, true);
            }

            file_put_contents($filepath, $stub);
            $this->success("{$title} {$filename} created successfully");
            exit;
        }

        $this->error("Stub {$stub} not found!");
    }

    private function commands()
    {
    }
}
