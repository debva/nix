<?php

namespace Debva\Nix;

class Datatable
{
    protected $total = 0;

    protected $totalFiltered = 0;

    protected $data = [];

    protected $loadtime = 0;

    public function __construct($data = [])
    {
        if (!empty($data)) {
            if (count($data) === 3 && $data[0] instanceof \PDO) {
                $this->loadtime = microtime(true);

                $request = isset($_REQUEST['datatable'], $_REQUEST['datatable']['page'], $_REQUEST['datatable']['limit'])
                    ? $_REQUEST['datatable']
                    : ['page' => 1, 'limit' => 10];

                $limit = $request['limit'];
                $offset = ($request['page'] - 1) * $limit;

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

                list($database, $query, $bindings) = $data;

                $data = $database->prepare("SELECT COUNT(*) FROM ({$query}) AS tbl");
                $data->execute($bindings);
                $this->total = (int) $data->fetchColumn();

                if (!empty($whereQuery)) {
                    $data = $database->prepare("SELECT COUNT(*) FROM ({$query}) AS tbl {$whereQuery}");
                    $data->execute($bindings);
                    $this->totalFiltered = (int) $data->fetchColumn();
                }

                $query = trim("({$query}) AS tbl {$whereQuery} {$orderQuery}");
                $data = $database->prepare("SELECT * FROM {$query} LIMIT {$limit} OFFSET {$offset}");
                $data->execute($bindings);
                $this->data = $data->fetchAll();

                $this->loadtime = number_format(microtime(true) - $this->loadtime, 3, ',', '.');
            } else {
                $this->total = count($data);
                $this->data = $data;
            }
        }
    }

    public function response()
    {
        return [
            'loadtime'      => $this->loadtime,
            'total'         => $this->total,
            'totalFiltered' => $this->totalFiltered,
            'data'          => $this->data
        ];
    }
}
