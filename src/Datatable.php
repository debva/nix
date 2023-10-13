<?php

namespace Debva\Nix;

class Datatable
{
    protected $loadtime = 0;

    protected $limit = 10;

    protected $page = 1;

    protected $total = 0;

    protected $totalFiltered = false;

    protected $data = [];

    protected $columns;

    public function __construct($data = [])
    {
        $this->loadtime = microtime(true);

        $request = request();
        $request = isset($request['datatable'], $request['datatable']['page'], $request['datatable']['limit'])
            ? $request['datatable']
            : ['page' => 1, 'limit' => 10];

        $this->limit = (int) $request['limit'];
        $this->page = (int) $request['page'];
        $this->offset = ($this->page - 1) * $this->limit;

        if ($data instanceof Anonymous && $data->connection() instanceof \PDO) {
            $searchQuery = null;
            if (!empty($request['search']) && is_array($request['search'])) {
                $searchQuery = [];
                foreach ($request['search'] as $column => $value) $searchQuery[] = '`' . implode('`.`', explode('.', $column)) . "` LIKE '%{$value}%'";
                $searchQuery = implode(' OR ', $searchQuery);
                $searchQuery = "({$searchQuery})";
            }

            $filterQuery = null;
            if (!empty($request['filter']) && is_array($request['filter'])) {
                $filterQuery = [];
                foreach ($request['filter'] as $column => $value) $filterQuery[] = '`' . implode('`.`', explode('.', $column)) . "` LIKE '%{$value}%'";
                $filterQuery = implode(' AND ', $filterQuery);
                $filterQuery = "({$filterQuery})";
            }

            $orderQuery = null;
            if (!empty($request['sort']) && is_array($request['sort'])) {
                $orderQuery = [];
                foreach ($request['sort'] as $column => $sort) $orderQuery[] = '`' . implode('`.`', explode('.', $column)) . "` " . strtoupper($sort);
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
            $result = $data;
            $isFiltered = false;

            if (!empty($request['search']) && is_array($request['search'])) {
                $isFiltered = true;
                $search = array_filter($request['search'], 'strlen');

                if (!empty($search)) {
                    $result = array_filter($result, function ($item) use ($search) {
                        $results = [];
                        foreach ($search as $column => $value) {
                            if (isset($item[$column]) && strpos(strtolower($item[$column]), strtolower($value)) !== false) {
                                $results[] = $value;
                            }
                        }

                        if (!empty($results)) return $item;
                    });
                }
            }

            if (!empty($request['filter']) && is_array($request['filter'])) {
                $isFiltered = true;
                $filter = array_filter($request['filter'], 'strlen');

                if (!empty($filter)) {
                    foreach ($filter as $column => $value) {
                        $result = array_filter($result, function ($item) use ($column, $value, $result) {
                            if (isset($item[$column]) && strpos(strtolower($item[$column]), strtolower($value)) !== false) {
                                return $item;
                            }
                        });
                    }
                }
            }

            if (!empty($request['sort']) && is_array($request['sort'])) {
                $sort = array_filter($request['sort'], 'strlen');

                if (!empty($sort)) {
                    $args = [];
                    $order = [
                        'ASC'   => SORT_ASC,
                        'DESC'  => SORT_DESC,
                    ];

                    foreach ($sort as $column => $sortBy) {
                        $args[] = array_column($result, $column);
                        $args[] = $order[strtoupper($sortBy)];
                    }

                    $args[] = &$result;
                    array_multisort(...$args);
                }
            }

            $this->total = count($data);
            $this->totalFiltered = $isFiltered ? count($result) : false;
            $this->data = array_slice($result, $this->offset, $this->limit);
        }
    }

    public function columns($columns = [])
    {
        $this->columns = $columns;
        return $this;
    }

    public function response($code = 200, $gzip = false, $sanitize = false, $except_sanitize = [])
    {
        $this->loadtime = number_format(microtime(true) - $this->loadtime, 3, ',', '.');

        $data = array_merge([
            'loadtime'      => $this->loadtime,
            'limit'         => $this->limit,
            'page'          => $this->page,
            'total'         => $this->total,
            'totalFiltered' => $this->totalFiltered,
            'data'          => $this->data
        ], (!empty($this->columns) ? ['columns' => $this->columns] : []));

        return response($data, $code, $gzip, $sanitize, $except_sanitize);
    }
}
