<?php

namespace Debva\Nix;

class Datatable
{
    protected $limit = 10;

    protected $page = 1;

    protected $offset = 0;

    protected $total = 0;

    protected $totalFiltered = false;

    protected $limited = true;

    protected $data = [];

    protected $columns = [];

    protected $editColumns = [];

    protected $tableNameAlias = 'tbl';

    protected $namingSearchBindings = 'NIX_DT_SEARCH';

    protected $namingFilterBindings = 'NIX_DT_FILTER';

    protected $namingLimitBindings = 'NIX_DT_LIMIT';

    protected $namingOffsetBindings = 'NIX_DT_OFFSET';

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    public function builder($data = [])
    {
        $request = request('datatable');
        $request = array_merge(['page' => 1, 'limit' => 10], (is_null($request) || !is_array($request)) ? [] : $request);
        $request['search'] = isset($request['search']) ? $request['search'] : [];
        $request['filter'] = isset($request['filter']) ? $request['filter'] : [];
        $request['sort'] = isset($request['sort']) ? $request['sort'] : [];

        $this->limit = (int) $request['limit'];
        $this->page = (int) $request['page'];
        $this->offset = ($this->page - 1) * $this->limit;

        if ($data instanceof \Debva\Nix\Database\Base) {
            $db = $data;
            $query = $db->getQuery();
            $bindings = $originalBindings = $db->getBindings();

            $whereOperator = $db->getWhereOperator();
            $indexBinding = count($originalBindings);
            $table = $db->buildQuotationMark($this->tableNameAlias);

            $isNamedBindingType = empty($bindings) ? false : array_reduce(array_keys($bindings), function ($carry, $key) {
                return $carry && is_string($key);
            }, true);

            $searchQuery = null;
            if (!empty($request['search']) && is_array($request['search'])) {
                $searchQuery = [];
                foreach ($request['search'] as $column => $value) {
                    $column = $db->buildQuotationMark($this->sanitizeColumn($column));
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            if (count($searchQuery) > 0) $searchQuery[] = 'OR';
                            $searchQuery[] = [$db->cast("{$table}.{$column}", $db->getDataType('string')), $whereOperator, "%{$v}%"];
                        }
                    } else {
                        if (count($searchQuery) > 0) $searchQuery[] = 'OR';
                        $searchQuery[] = [$db->cast("{$table}.{$column}", $db->getDataType('string')), $whereOperator, "%{$value}%"];
                    }
                }
                $searchQuery = $db->buildConditions(
                    $searchQuery,
                    $indexBinding,
                    false,
                    $isNamedBindingType
                        ? $this->namingFilterBindings : null
                );
                $bindings = array_merge($bindings, $searchQuery['bindings']);
                $searchQuery = $searchQuery['query'];
            }

            $filterQuery = null;
            if (!empty($request['filter']) && is_array($request['filter'])) {
                $filterQuery = [];
                foreach ($request['filter'] as $column => $value) {
                    $column = $db->buildQuotationMark($this->sanitizeColumn($column));
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            if (count($filterQuery) > 0) $filterQuery[] = 'AND';
                            $filterQuery[] = [$db->cast("{$table}.{$column}", $db->getDataType('string')), $whereOperator, "%{$v}%"];
                        }
                    } else {
                        if (count($filterQuery) > 0) $filterQuery[] = 'AND';
                        $filterQuery[] = [$db->cast("{$table}.{$column}", $db->getDataType('string')), $whereOperator, "%{$value}%"];
                    }
                }
                $filterQuery = $db->buildConditions(
                    $filterQuery,
                    $indexBinding,
                    false,
                    $isNamedBindingType
                        ? $this->namingFilterBindings : null
                );
                $bindings = array_merge($bindings, $filterQuery['bindings']);
                $filterQuery = $filterQuery['query'];
            }

            $orderQuery = null;
            if (!empty($request['sort']) && is_array($request['sort'])) {
                $orderQuery = [];
                foreach ($request['sort'] as $column => $sort) {
                    if (in_array(strtoupper($sort), ['ASC', 'DESC'])) {
                        $column = $db->buildQuotationMark($this->sanitizeColumn($column));
                        $orderQuery[] = "{$table}.{$column} " . strtoupper($sort);
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

            $data = $db->query(trim("SELECT COUNT(*) FROM ({$query}) AS {$table}"), $originalBindings);
            $this->total = (int) $data->column();

            if (!empty($whereQuery)) {
                $data = $db->query(trim("SELECT COUNT(*) FROM ({$query}) AS {$table} {$whereQuery}"), $bindings);
                $this->totalFiltered = (int) $data->column();
            }

            $limit = '';

            if ($this->limited) {
                $placeholderLimit = $db->buildPlaceholder($indexBinding, [], $isNamedBindingType ? $this->namingLimitBindings : null);
                $namingLimitBindings = "{$this->namingLimitBindings}_{$indexBinding}";
    
                $placeholderOffset = $db->buildPlaceholder($indexBinding, [], $isNamedBindingType ? $this->namingOffsetBindings : null);
                $namingOffsetBindings = "{$this->namingOffsetBindings}_{$indexBinding}";
    
                $bindings = array_merge(
                    $bindings,
                    $isNamedBindingType ? [$namingLimitBindings => $this->limit] : [$this->limit],
                    $isNamedBindingType ? [$namingOffsetBindings => $this->offset] : [$this->offset],
                );

                $limit = "LIMIT {$placeholderLimit} OFFSET {$placeholderOffset}";
            }

            $query = trim("({$query}) AS {$table} {$whereQuery} {$orderQuery}");
            $data = $db->query(trim("SELECT * FROM {$query} {$limit}"), $bindings);
            $this->data = $data->get();
            
            $this->offset = $this->limited ? $this->offset : 0;
            $this->limit = $this->limited ? $this->limit : $this->total;
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
            $this->data = array_values($this->limited ? array_slice($result, $this->offset, $this->limit) : $result);

            $this->offset = $this->limited ? $this->offset : 0;
            $this->limit = $this->limited ? $this->limit : $this->total;
        }
    }

    public function sanitizeColumn($column)
    {
        return preg_replace('/[\s\-.]+/', '_', $column);
    }

    public function noLimit()
    {
        $this->limited = false;
        return $this;
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

    public function getData()
    {
        $this->builder($this->data);

        $this->editColumns = array_filter($this->editColumns);

        return array_map(function ($data) {
            foreach (array_keys($this->editColumns) as $column) {
                $data[$column] = $this->editColumns[$column]($data);
            }

            return $data;
        }, $this->data);
    }

    public function response()
    {
        $this->builder($this->data);

        $this->editColumns = array_filter($this->editColumns);

        $data = array_map(function ($data) {
            foreach (array_keys($this->editColumns) as $column) {
                $data[$column] = $this->editColumns[$column]($data);
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
            'loadtime'      => number_format(microtime(true) - NIX_START, 3, ',', '.'),
            'limit'         => $this->limit,
            'page'          => $this->page,
            'total'         => $this->total,
            'totalFiltered' => $this->totalFiltered,
            'data'          => $data
        ], (!empty($this->columns) ? ['columns' => array_values($columns)] : []));

        return $data;
    }
}
