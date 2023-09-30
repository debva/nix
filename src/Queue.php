<?php

namespace Debva\Nix;

class Queue
{
    protected $queueFolderName = 'queue';

    protected $queues = [];

    protected $db;

    public function __construct()
    {
        if (!defined('QUEUE_ALL')) {
            define('QUEUE_ALL', true);
        }

        foreach (['PENDING', 'RUNNING', 'SUCCESS', 'FAILURE'] as $key => $status) {
            if (!defined("QUEUE_{$status}")) {
                define("QUEUE_{$status}", $key);
            }
        }

        $this->db = new Database;
    }

    public function __invoke()
    {
        $queuePath = implode(DIRECTORY_SEPARATOR, [getcwd(), '..', 'app', $this->queueFolderName]);
        $this->queues = array_filter(glob(implode(DIRECTORY_SEPARATOR, [$queuePath, '*.php'])), 'file_exists');

        // array_map('basename', $this->queues);
    }

    private function getPendingQueue()
    {
        $queue = $this->getQueue();

        $sql = "{$queue->query()} WHERE ";

        // return $this->db->query();
    }

    private function import()
    {
        try {
            $sql = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'sql', 'queue.sql']));
            if (!file_exists($sql)) {
                throw new \Exception('Queue SQL file not found!');
            }

            $pdo = $this->db->getConnection();
            $pdo->exec($sql);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
    }

    public function addQueue()
    {
    }

    public function getQueue($status = QUEUE_ALL)
    {
        if (!in_array($status, range(0, 5))) {
            throw new \Exception("Status not found!", 500);
        }

        $sql = '';
        $bindings = [];

        if (in_array($status, range(0, 4))) {
            $sql = 'WHERE `status` = :status';
            $bindings = ['status' => $status];
        }

        return $this->db->raw(
            trim(implode(' ', ['SELECT * FROM `queue`', $sql])),
            $bindings
        );
    }

    public function updateQueue()
    {
    }

    public function removeQueue()
    {
    }
}
