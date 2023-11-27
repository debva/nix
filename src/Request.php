<?php

namespace Debva\Nix;

class Request
{
    public function __invoke($keys = null, $default = null)
    {
        $request = $_REQUEST;

        if (!empty($_FILES)) {
            $files = array_map(function ($file) {
                return $this->getFiles($file['name'], $file['type'], $file['tmp_name'], $file['error'], $file['size']);
            }, $_FILES);

            $request = array_merge_recursive($request, $files);
        }

        if (!empty($body = file_get_contents("php://input")) && !is_null(json_decode($body, true))) {
            $request = array_merge($request, json_decode($body, true));
        }

        if (!is_null($keys)) {
            if (is_array($keys)) {
                $results = [];

                foreach ($keys as $key) {
                    $result = [];
                    $req = $request;
                    $arrayRef = &$result;

                    foreach (explode('.', $key) as $k) {
                        $arrayRef = &$arrayRef[$k];
                        $req = isset($req[$k])
                            ? $req[$k]
                            : (!is_null($default) ? $default : null);
                    }

                    $arrayRef = $req;

                    $key = array_keys($result);
                    $key = reset($key);

                    $value = array_values($result);
                    $value = reset($value);

                    $results[$key] = (array_key_exists($key, $results) && is_array($results[$key]) && is_array($value))
                        ? array_merge($results[$key], $value) : $value;
                }

                return $results;
            }

            $keys = explode('.', $keys);
            foreach ($keys as $key) {
                $request = isset($request[$key])
                    ? $request[$key]
                    : (!is_null($default) ? $default : null);
            }
        }

        return $request;
    }

    protected function getFiles($name, $type, $tmpName, $error, $size)
    {
        if (empty($tmpName)) return null;

        if (is_string($tmpName)) return [
            'name'      => $name,
            'mimeType'  => $type,
            'type'      => 'file',
            'path'      => $tmpName,
            'error'     => $error,
            'size'      => $size
        ];

        array_walk($tmpName, function ($path, $key) use (&$result, $name, $type, $error, $size) {
            if (is_array($path)) {
                $result[$key] = $this->getFiles($name[$key], $type[$key], $path, $error[$key], $size[$key]);
            } else {
                if (empty($path)) $result[$key] = null;
                else $result[$key] = [
                    'name'      => $name[$key],
                    'mimeType'  => $type[$key],
                    'type'      => 'file',
                    'path'      => $path,
                    'error'     => $error[$key],
                    'size'      => $size[$key]
                ];
            }
        });

        return array_filter($result);
    }
}
