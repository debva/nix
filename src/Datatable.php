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

    protected $editColumns = [];

    public function __construct($data = [])
    {
        $this->loadtime = microtime(true);

        $request = request('datatable');
        $request = array_merge(['page' => 1, 'limit' => 10], is_null($request) ? [] : $request);
        $request['search'] = isset($request['search']) ? $request['search'] : [];
        $request['filter'] = isset($request['filter']) ? $request['filter'] : [];
        $request['sort'] = isset($request['sort']) ? $request['sort'] : [];

        $this->limit = (int) $request['limit'];
        $this->page = (int) $request['page'];
        $this->offset = ($this->page - 1) * $this->limit;

        if ($data instanceof Anonymous && $data->database() instanceof \PDO) {
            $db = $data;

            $bindings = $data->bindings();
            $whereClause = $data->whereClause();

            $searchQuery = null;
            if (!empty($request['search']) && is_array($request['search'])) {
                $searchQuery = [];
                foreach ($request['search'] as $column => $value) {
                    $sanitizeColumn = preg_replace('/[\s-]+/', '_', $column);
                    $binding = strtoupper("_NIX_DT_SEARCH_{$sanitizeColumn}");
                    $bindings = array_merge($bindings, [$binding => "%{$value}%"]);
                    $searchQuery[] = "tbl.{$column} {$whereClause} :{$binding}";
                }
                $searchQuery = implode(' OR ', $searchQuery);
                $searchQuery = "({$searchQuery})";
            }

            $filterQuery = null;
            if (!empty($request['filter']) && is_array($request['filter'])) {
                $filterQuery = [];
                foreach ($request['filter'] as $column => $value) {
                    $sanitizeColumn = preg_replace('/[\s\-.]+/', '_', $column);
                    $binding = strtoupper("_NIX_DT_FILTER_{$sanitizeColumn}");
                    $bindings = array_merge($bindings, [$binding => "%{$value}%"]);
                    $filterQuery[] = "tbl.{$column} {$whereClause} :{$binding}";
                }
                $filterQuery = implode(' AND ', $filterQuery);
                $filterQuery = "({$filterQuery})";
            }

            $orderQuery = null;
            if (!empty($request['sort']) && is_array($request['sort'])) {
                $orderQuery = [];
                foreach ($request['sort'] as $column => $sort) {
                    if (in_array(strtoupper($sort), ['ASC', 'DESC'])) {
                        $orderQuery[] = "tbl.{$column} " . strtoupper($sort);
                    }
                }

                if (empty($orderQuery)) {
                    $orderQuery = null;
                } else {
                    $orderQuery = implode(', ', $orderQuery);
                    $orderQuery = "ORDER BY {$orderQuery}";
                }
            }

            $whereQuery = null;
            if (!empty($searchQuery) || !empty($filterQuery)) {
                $whereQuery = implode(' AND ', array_filter([$searchQuery, $filterQuery]));
                $whereQuery = "WHERE {$whereQuery}";
            }

            $data = $db->database()->prepare("SELECT COUNT(*) FROM ({$db->query()}) AS tbl");
            $data->execute($db->bindings());
            $this->total = (int) $data->fetchColumn();

            if (!empty($whereQuery)) {
                $data = $db->database()->prepare(trim("SELECT COUNT(*) FROM ({$db->query()}) AS tbl {$whereQuery}"));
                $data->execute($bindings);
                $this->totalFiltered = (int) $data->fetchColumn();
            }

            $query = trim("({$db->query()}) AS tbl {$whereQuery} {$orderQuery}");
            $data = $db->database()->prepare(trim("SELECT * FROM {$query} LIMIT {$this->limit} OFFSET {$this->offset}"));
            $data->execute($bindings);
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
                        $result = array_filter($result, function ($item) use ($column, $value) {
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

    public function editColumn($column, \Closure $value)
    {
        $this->editColumns[$column] = $value;
        return $this;
    }

    public function response($code = 200, $gzip = false, $sanitize = false, $except_sanitize = [])
    {
        $this->editColumns = array_filter($this->editColumns);

        $customValue = function ($data) {
            foreach (array_keys($this->editColumns) as $column) {
                if (in_array($column, array_keys($data))) {
                    $data[$column] = $this->editColumns[$column]($data);
                }
            }

            return $data;
        };

        $data = array_map($customValue, $this->data);

        $data = array_merge([
            'loadtime'      => number_format(microtime(true) - $this->loadtime, 3, ',', '.'),
            'limit'         => $this->limit,
            'page'          => $this->page,
            'total'         => $this->total,
            'totalFiltered' => $this->totalFiltered,
            'data'          => $data
        ], (!empty($this->columns) ? ['columns' => $this->columns] : []));

        return response($data, $code, $gzip, $sanitize, $except_sanitize);
    }
}
