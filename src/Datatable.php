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

    protected $columns = [];

    protected $editColumns = [];

    protected $tableNameAlias = 'tbl';

    protected $namingSearchBindings = '_NIX_DT_SEARCH_';

    protected $namingFilterBindings = '_NIX_DT_FILTER_';

    public function __construct($data = [])
    {
        $this->loadtime = microtime(true);

        $request = request('datatable');
        $request = array_merge(['page' => 1, 'limit' => 10], (is_null($request) || !is_array($request)) ? [] : $request);
        $request['search'] = isset($request['search']) ? $request['search'] : [];
        $request['filter'] = isset($request['filter']) ? $request['filter'] : [];
        $request['sort'] = isset($request['sort']) ? $request['sort'] : [];

        $this->limit = (int) $request['limit'];
        $this->page = (int) $request['page'];
        $this->offset = ($this->page - 1) * $this->limit;

        if ($data instanceof Database) {
            $db = $data;
            $query = $db->getQuery();
            $whereClause = $db->getWhereClause();
            $mark = $db->getMark();
            $bindings = $originalBindings = $db->getBindings();
            $isNamedBindingType = array_reduce(array_keys($bindings), function ($carry, $key) {
                return $carry && is_string($key);
            }, true);

            $searchQuery = null;
            if (!empty($request['search']) && is_array($request['search'])) {
                $searchQuery = [];
                foreach ($request['search'] as $column => $value) {
                    $binding = strtoupper("{$this->namingSearchBindings}{$this->sanitizeColumn($column)}");
                    $bindings = array_merge($bindings, $isNamedBindingType ? [$binding => "%{$value}%"] : ["%{$value}%"]);
                    $searchQuery[] = "{$this->tableNameAlias}.{$mark}{$this->sanitizeColumn($column)}{$mark} {$whereClause} " . ($isNamedBindingType ? ":{$binding}" : '?');
                }
                $searchQuery = implode(' OR ', $searchQuery);
                $searchQuery = "({$searchQuery})";
            }

            $filterQuery = null;
            if (!empty($request['filter']) && is_array($request['filter'])) {
                $filterQuery = [];
                foreach ($request['filter'] as $column => $value) {
                    $binding = strtoupper("{$this->namingFilterBindings}{$this->sanitizeColumn($column)}");
                    $bindings = array_merge($bindings, $isNamedBindingType ? [$binding => "%{$value}%"] : ["%{$value}%"]);
                    $filterQuery[] = "{$this->tableNameAlias}.{$mark}{$this->sanitizeColumn($column)}{$mark} {$whereClause} " . ($isNamedBindingType ? ":{$binding}" : '?');
                }
                $filterQuery = implode(' AND ', $filterQuery);
                $filterQuery = "({$filterQuery})";
            }

            $orderQuery = null;
            if (!empty($request['sort']) && is_array($request['sort'])) {
                $orderQuery = [];
                foreach ($request['sort'] as $column => $sort) {
                    if (in_array(strtoupper($sort), ['ASC', 'DESC'])) {
                        $orderQuery[] = "{$this->tableNameAlias}.{$mark}{$this->sanitizeColumn($column)}{$mark} " . strtoupper($sort);
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

            $data = $db->query("SELECT COUNT(*) FROM ({$query}) AS {$this->tableNameAlias}", $originalBindings);
            $this->total = (int) $data->column();

            if (!empty($whereQuery)) {
                $data = $db->query(trim("SELECT COUNT(*) FROM ({$query}) AS {$this->tableNameAlias} {$whereQuery}"), $bindings);
                $this->totalFiltered = (int) $data->column();
            }

            $query = trim("({$query}) AS {$this->tableNameAlias} {$whereQuery} {$orderQuery}");
            $data = $db->query(trim("SELECT * FROM {$query} LIMIT {$this->limit} OFFSET {$this->offset}"), $bindings);
            $this->data = $data->get();
        } else {
            $data = (empty($data) || !is_array($data)) ? [] : $data;
            $data = is_array(end($data)) ? $data : (empty($data) ? [] : [$data]);

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

    public function sanitizeColumn($column)
    {
        return preg_replace('/[\s\-.]+/', '_', $column);
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

    public function response()
    {
        $this->editColumns = array_filter($this->editColumns);

        $data = array_map(function ($data) {
            foreach (array_keys($this->editColumns) as $column) {
                if (in_array($column, array_keys($data))) {
                    $data[$column] = $this->editColumns[$column]($data);
                }
            }

            return $data;
        }, $this->data);

        $request = request('datatable.columns');
        $columns = array_filter($this->columns, function ($column) use ($request) {
            if (!empty($request)) {
                foreach ($request as $key => $value) {
                    $value = is_array($value) ? $value : [$value];
                    if (isset($column[$key]) && in_array($column[$key], $value)) {
                        return $column;
                    }
                }
                return null;
            }
            return $column;
        });

        $data = array_merge([
            'loadtime'      => number_format(microtime(true) - $this->loadtime, 3, ',', '.'),
            'limit'         => $this->limit,
            'page'          => $this->page,
            'total'         => $this->total,
            'totalFiltered' => $this->totalFiltered,
            'data'          => $data
        ], (!empty($this->columns) ? ['columns' => array_values($columns)] : []));

        return $data;
    }
}
