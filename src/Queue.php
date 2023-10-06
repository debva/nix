<?php

namespace Debva\Nix;

class Queue
{
    protected $queueFolderName = 'queue';

    protected $db;

    protected $queueFiles = [];

    public function __construct()
    {
        if (!defined('QUEUE_ALL')) {
            define('QUEUE_ALL', true);
        }

        if (!defined('QUEUE_PENDING')) {
            define('QUEUE_PENDING', 'PENDING');
        }

        if (!defined('QUEUE_RUNNING')) {
            define('QUEUE_RUNNING', 'RUNNING');
        }

        if (!defined('QUEUE_SUCCESS')) {
            define('QUEUE_SUCCESS', 'SUCCESS');
        }

        if (!defined('QUEUE_FAILURE')) {
            define('QUEUE_FAILURE', 'FAILURE');
        }

        // $this->db = new Database;
    }

    public function __invoke()
    {
        $queuePath = implode(DIRECTORY_SEPARATOR, [getcwd(), '..', 'app', $this->queueFolderName]);
        $queues = $this->getPendingQueue();
        $this->queueFiles = array_filter(glob(implode(DIRECTORY_SEPARATOR, [$queuePath, '*.php'])), 'file_exists');

        return $this->runQueue($queues, $queuePath);
    }

    private function runQueue($queues, $queuePath)
    {
        $queue = array_shift($queues);

        if (
            in_array("{$queue['class']}.php", array_map('basename', $this->queueFiles)) &&
            (is_null($queue['run_at']) || strtotime($queue['run_at']) <= time()) &&
            !empty($queue['class']) && !empty($queue['method']) &&
            $queue['status'] == QUEUE_PENDING
        ) {
            try {
                $this->updateQueue($queue['id'], function ($query) {
                    $query->status(QUEUE_RUNNING);
                });

                $file = implode(DIRECTORY_SEPARATOR, [$queuePath, "{$queue['class']}.php"]);
                require_once($file);

                $class = new $queue['class'];
                $reflection = new \ReflectionMethod($class, $queue['method']);

                $parameter = (is_null($queue['parameter']) && !json_decode($queue['parameter'])) ? [] : json_decode($queue['parameter']);
                $parameters = $reflection->getParameters();

                $arguments = [];
                foreach ($parameters as $index => $param) {
                    $arguments[$index] = isset($parameter[$index]) ? $arguments[$index] = $parameter[$index] : null;
                    if (empty($arguments[$index]) && $param->isDefaultValueAvailable()) $arguments[$index] = $param->getDefaultValue();
                }


                $result = $class->{$queue['method']}(...$arguments);
                if (!$result) {
                    throw new \Exception('Queue failed to run!');
                }

                $this->updateQueue($queue['id'], function ($query) {
                    $query->status(QUEUE_SUCCESS);
                });
            } catch (\Exception $e) {
                $loop = (int) $queue['loop'];
                $this->updateQueue($queue['id'], function ($query) use ($loop) {
                    $query->status($loop > 0 ? QUEUE_PENDING : QUEUE_FAILURE)
                        ->loop($loop > 0 ? --$loop : 0);
                });
            }

            return $this->runQueue($queues, $queuePath);
        }

        return $this->getPendingQueue();
    }

    private function getPendingQueue()
    {
        try {
            $queue = $this->getQueue(QUEUE_PENDING);
            return $this->db->query($queue->query(), $queue->bindings())->fetchAll();
        } catch (\Exception $e) {
            return $this->import($e->getMessage(), function () {
                return $this->getPendingQueue();
            });
        }
    }

    private function import($message = null, \Closure $callback)
    {
        try {
            $table = $this->db->query("SHOW TABLES LIKE ?", ['schedulers'])->fetchColumn();

            if (!$table) {
                $sql = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'sql', 'schedulers.sql']);
                if (!file_exists($sql)) {
                    throw new \Exception('Schedulers SQL file not found!');
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

    public function addQueue(\Closure $callback)
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
                "INSERT INTO `schedulers` (title, class, method, parameter, `loop`, run_at) VALUE (?, ?, ?, ?, ?, ?)",
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
                return $this->addQueue($callback);
            });
        }
    }

    public function getQueue($status = QUEUE_ALL)
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
            trim(implode(' ', ['SELECT * FROM `schedulers`', $sql, 'ORDER by id ASC'])),
            $bindings
        );
    }

    public function updateQueue($queueId, \Closure $callback)
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

        $query = "UPDATE `schedulers` SET";
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
                array_merge($bindings, [$queueId])
            );

            return true;
        } catch (\Exception $e) {
            return $this->import($e->getMessage(), function () use ($queueId, $callback) {
                return $this->updateQueue($queueId, $callback);
            });
        }
    }

    public function removeQueue($queueId)
    {
        try {
            $this->db->query('DELETE FROM queue WHERE id = ?', [$queueId]);
            return true;
        } catch (\Exception $e) {
            return $this->import($e->getMessage(), function () use ($queueId) {
                return $this->removeQueue($queueId);
            });
        }
    }
}
