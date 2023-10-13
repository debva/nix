<?php

namespace Debva\Nix;

class Task
{
    protected $db;

    protected $taskFiles = [];

    public function __construct()
    {
        if (!defined('TASK_ALL')) {
            define('TASK_ALL', true);
        }

        if (!defined('TASK_PENDING')) {
            define('TASK_PENDING', 'PENDING');
        }

        if (!defined('TASK_RUNNING')) {
            define('TASK_RUNNING', 'RUNNING');
        }

        if (!defined('TASK_SUCCESS')) {
            define('TASK_SUCCESS', 'SUCCESS');
        }

        if (!defined('TASK_FAILURE')) {
            define('TASK_FAILURE', 'FAILURE');
        }

        $this->db = nix('db');
    }

    public function __invoke()
    {
        $taskPath = implode(DIRECTORY_SEPARATOR, [getcwd(), '..', 'app', 'tasks']);
        $tasks = $this->getPendingTask();
        $this->taskFiles = array_filter(glob(implode(DIRECTORY_SEPARATOR, [$taskPath, '*.php'])), 'file_exists');

        return $this->runTask($tasks, $taskPath);
    }

    private function runTask($tasks, $taskPath)
    {
        $task = array_shift($tasks);

        if (
            in_array("{$task['class']}.php", array_map('basename', $this->taskFiles)) &&
            (is_null($task['run_at']) || strtotime($task['run_at']) <= time()) &&
            !empty($task['class']) && !empty($task['method']) &&
            $task['status'] == TASK_PENDING
        ) {
            try {
                $this->updateTask($task['id'], function ($query) {
                    $query->status(TASK_RUNNING);
                });

                $file = implode(DIRECTORY_SEPARATOR, [$taskPath, "{$task['class']}.php"]);
                require_once($file);

                $class = new $task['class'];
                $reflection = new \ReflectionMethod($class, $task['method']);

                $parameter = (is_null($task['parameter']) && !json_decode($task['parameter'])) ? [] : json_decode($task['parameter']);
                $parameters = $reflection->getParameters();

                $arguments = [];
                foreach ($parameters as $index => $param) {
                    $arguments[$index] = isset($parameter[$index]) ? $arguments[$index] = $parameter[$index] : null;
                    if (empty($arguments[$index]) && $param->isDefaultValueAvailable()) $arguments[$index] = $param->getDefaultValue();
                }


                $result = $class->{$task['method']}(...$arguments);
                if (!$result) {
                    throw new \Exception('Task failed to run!');
                }

                $this->updateTask($task['id'], function ($query) {
                    $query->status(TASK_SUCCESS);
                });
            } catch (\Exception $e) {
                $loop = (int) $task['loop'];
                $this->updateTask($task['id'], function ($query) use ($loop) {
                    $query->status($loop > 0 ? TASK_PENDING : TASK_FAILURE)
                        ->loop($loop > 0 ? --$loop : 0);
                });
            }

            return $this->runTask($tasks, $taskPath);
        }

        return $this->getPendingTask();
    }

    private function getPendingTask()
    {
        try {
            $task = $this->getTask(TASK_PENDING);
            return $this->db->query($task->query(), $task->bindings())->fetchAll();
        } catch (\Exception $e) {
            return $this->import($e->getMessage(), function () {
                return $this->getPendingTask();
            });
        }
    }

    private function import($message = null, \Closure $callback)
    {
        try {
            $table = $this->db->query("SHOW TABLES LIKE ?", ['tasks'])->fetchColumn();

            if (!$table) {
                $sql = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'sql', 'tasks.sql']);
                if (!file_exists($sql)) {
                    throw new \Exception('Task SQL file not found!');
                }

                $pdo = $this->db->getConnection();
                $pdo->exec(file_get_contents($sql));

                return $callback();
            }

            throw new \Exception($message, 500);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function addTask(\Closure $callback)
    {
        $class = new Anonymous;
        $macros = [
            'title'         => null,
            'class'         => null,
            'method'        => null,
            'parameter'     => [],
            'loop'          => 0,
            'runAt'         => null
        ];

        $arguments = [];

        foreach ($macros as $macro => $defaultValue) {
            $arguments[$macro] = $defaultValue;
            $class->macro($macro, function ($self, $value) use (&$arguments, $macro) {
                $arguments[$macro] = $value;
                return $self;
            });
        }

        $callback($class);

        foreach (array_slice($arguments, 0, 3) as $method => $value) {
            if (is_null($value)) {
                throw new \Exception("The {$method} is required", 500);
            }
        }

        if (!is_array($arguments['parameter'])) {
            throw new \Exception('The parameter argument must be an array', 500);
        }

        if (!is_int($arguments['loop'])) {
            throw new \Exception('The loop argument must be an integer', 500);
        }

        try {
            $this->db->query(
                "INSERT INTO `tasks` (title, class, method, parameter, `loop`, run_at) VALUE (?, ?, ?, ?, ?, ?)",
                [
                    $arguments['title'],
                    $arguments['class'],
                    $arguments['method'],
                    json_encode($arguments['parameter']),
                    $arguments['loop'],
                    $arguments['runAt']
                ]
            );

            return true;
        } catch (\Exception $e) {
            return $this->import($e->getMessage(), function () use ($callback) {
                return $this->addTask($callback);
            });
        }
    }

    public function getTask($status = TASK_ALL)
    {
        if (!in_array($status, range(0, 5))) {
            throw new \Exception("Status not found!", 500);
        }

        $sql = '';
        $bindings = [];

        if ($status !== true && in_array($status, range(0, 4))) {
            $sql = 'WHERE `status` = :status';
            $bindings = ['status' => $status];
        }

        return $this->db->raw(
            trim(implode(' ', ['SELECT * FROM `tasks`', $sql, 'ORDER by id ASC'])),
            $bindings
        );
    }

    public function updateTask($taskId, \Closure $callback)
    {
        $class = new Anonymous;
        $macros = [
            'title',
            'class',
            'method',
            'parameter',
            'status',
            'loop',
            'runAt'
        ];

        $arguments = [];

        foreach ($macros as $macro) {
            $class->macro($macro, function ($self, $value) use (&$arguments, $macro) {
                $arguments[$macro] = $value;
                return $self;
            });
        }

        $callback($class);

        $query = "UPDATE `tasks` SET";
        $queries = $bindings = [];

        foreach ($arguments as $method => $value) {
            switch ($method) {
                case 'parameter':
                    $queries[] = "{$method} = ?";
                    $bindings[] = json_encode($value);
                    break;

                case 'loop':
                    $queries[] = "`{$method}` = ?";
                    $bindings[] = $value;
                    break;

                case 'runAt':
                    $queries[] = 'run_at = ?';
                    $bindings[] = $value;
                    break;

                default:
                    $queries[] = "{$method} = ?";
                    $bindings[] = $value;
                    break;
            }
        }

        if (isset($arguments['parameter']) && !is_array($arguments['parameter'])) {
            throw new \Exception('The parameter argument must be an array', 500);
        }

        if (isset($arguments['loop']) && !is_int($arguments['loop'])) {
            throw new \Exception("The 'loop' argument must be an integer", 500);
        }

        try {
            $this->db->query(
                implode(' ', [$query, implode(', ', $queries), 'WHERE id = ?']),
                array_merge($bindings, [$taskId])
            );

            return true;
        } catch (\Exception $e) {
            return $this->import($e->getMessage(), function () use ($taskId, $callback) {
                return $this->updateTask($taskId, $callback);
            });
        }
    }

    public function deleteTask($taskId)
    {
        try {
            $this->db->query('DELETE FROM `tasks` WHERE id = ?', [$taskId]);
            return true;
        } catch (\Exception $e) {
            return $this->import($e->getMessage(), function () use ($taskId) {
                return $this->deleteTask($taskId);
            });
        }
    }
}
