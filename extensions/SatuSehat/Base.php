<?php

namespace Debva\Nix\Extension\SatuSehat;

class Base
{
    protected function mapping($data, $column = null, $closure = null)
    {
        if (is_null($closure) || $closure === true) {
            $data = is_null($column) ? $data : (isset($data[$column]) ? $data[$column] : null);
            if ($closure === true && is_null($data)) throw new \Exception("Column {$column} is required!");
            return is_array($data) ? array_filter($data) : $data;
        }

        $data = is_null($column) ? $data : (isset($data[$column]) ? $data[$column] : []);
        if (is_array($data)) return array_values(array_filter(array_map($closure, $data)));
        return $data;
    }

    protected function getDataType($dataType, $data, ...$parameters)
    {
        $dataType = "Debva\\Nix\\Extension\\SatuSehat\\DataType\\{$dataType}";
        $dataType = class_exists($dataType) ? new $dataType : null;

        if (is_null($dataType)) {
            throw new \Exception("DataType not found!");
        }

        return $dataType($data, ...$parameters);
    }

    protected function getValueSet($valueSet, $code, $key = null)
    {
        $valueSet = "Debva\\Nix\\Extension\\SatuSehat\\ValueSet\\{$valueSet}";
        $valueSet = class_exists($valueSet) ? new $valueSet : null;

        if (is_null($valueSet)) {
            throw new \Exception("ValueSet not found!");
        }

        $valueSet = $valueSet();

        if (!isset($valueSet[$code])) {
            throw new \Exception("Invalid value sets {$code}!");
        }

        $valueSet = $valueSet[$code];
        return is_null($key) ? $code : (isset($valueSet[$key]) ? $valueSet[$key] : null);
    }

    protected function getResponse($data, $key, $search = false, $column = false)
    {
        foreach (explode('.', $key) as $key) {
            $data = isset($data[$key]) ? $data[$key] : null;
        }

        if (is_array($data) && $search && is_array($search) && $column) {
            foreach ($search as $key => $value) {
                $index = array_search($value, array_column($data, $key));
                $data = $index !== false ? $data[$index] : [];
            }

            return isset($data[$column]) ? $data[$column] : null;
        }

        return $data;
    }
}
