<?php

namespace Debva\Nix;

class Datatable
{
    protected $loadtime = 0;

    protected $limit = 10;

    protected $page = 1;

    protected $total = 0;

    protected $totalFiltered = 0;

    protected $data = [];

    public function __construct($data = [])
    {
        $this->loadtime = microtime(true);

        $request = isset($_REQUEST['datatable'], $_REQUEST['datatable']['page'], $_REQUEST['datatable']['limit'])
            ? $_REQUEST['datatable']
            : ['page' => 1, 'limit' => 10];

        $this->limit = (int) $request['limit'];
        $this->offset = ($request['page'] - 1) * $this->limit;

        if ($data instanceof Anonymous && $data->connection() instanceof \PDO) {
            $searchQuery = null;
            if (isset($request['search']) && is_array($request['search'])) {
                $searchQuery = [];
                foreach ($request['search'] as $column => $value) $searchQuery[] = "{$column} LIKE '%{$value}%'";
                $searchQuery = implode(' OR ', $searchQuery);
                $searchQuery = "({$searchQuery})";
            }

            $filterQuery = null;
            if (isset($request['filter']) && is_array($request['filter'])) {
                $filterQuery = [];
                foreach ($request['filter'] as $column => $value) $filterQuery[] = "{$column} LIKE '%{$value}%'";
                $filterQuery = implode(' AND ', $filterQuery);
                $filterQuery = "({$filterQuery})";
            }

            $orderQuery = null;
            if (isset($request['sort']) && is_array($request['sort'])) {
                $orderQuery = [];
                foreach ($request['sort'] as $column => $sort) $orderQuery[] = "{$column} " . strtoupper($sort);
                $orderQuery = implode(', ', $orderQuery);
                $orderQuery = "ORDER BY {$orderQuery}";
            }

            $whereQuery = null;
            if (!empty($searchQuery) || !empty($filterQuery)) {
                $whereQuery = implode(' AND ', array_filter([$searchQuery, $filterQuery]));
                $whereQuery = "WHERE {$whereQuery}";
            }

            $database = $data;

            $data = $database->connection()->prepare("SELECT COUNT(*) FROM ({$database->query()}) AS tbl");
            $data->execute($database->bindings());
            $this->total = (int) $data->fetchColumn();

            if (!empty($whereQuery)) {
                $data = $database->connection()->prepare("SELECT COUNT(*) FROM ({$database->query()}) AS tbl {$whereQuery}");
                $data->execute($database->bindings());
                $this->totalFiltered = (int) $data->fetchColumn();
            }

            $query = trim("({$database->query()}) AS tbl {$whereQuery} {$orderQuery}");
            $data = $database->connection()->prepare("SELECT * FROM {$query} LIMIT {$this->limit} OFFSET {$this->offset}");
            $data->execute($database->bindings());
            $this->data = $data->fetchAll();
        } else {
            $result = [];
            $isFiltered = false;

            $this->total = count($data);
            $this->totalFiltered = count($result);
            $this->data = $isFiltered ? $result : $data;
        }

        $this->loadtime = number_format(microtime(true) - $this->loadtime, 3, ',', '.');
    }

    public function columns($columns = [])
    {
        return $columns;
    }

    public function toArray()
    {
        return array_merge([
            'loadtime'      => $this->loadtime,
            'limit'         => $this->limit,
            'page'          => $this->page,
            'total'         => $this->total,
            'totalFiltered' => $this->totalFiltered,
            'data'          => $this->data
        ], (!empty($this->columns()) ? ['columns' => $this->columns()] : []));
    }
}
