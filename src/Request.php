<?php

namespace Debva\Nix;

class Request
{
    public function __invoke($keys = null, $default = null)
    {
        $request = $_REQUEST;

        if (!empty($_FILES)) {
            $request = array_merge_recursive($request, $_FILES);
            // $files = array_map(function ($file) {
            //     $files = [];
            //     foreach ($file['name'] as $key => $name) {
            //         $files[$key] = [
            //             'name'      => $name,
            //             'type'      => $file['type'][$key],
            //             'tmp_name'  => $file['tmp_name'][$key],
            //             'error'     => $file['error'][$key],
            //             'size'      => $file['size'][$key]
            //         ];
            //     }
            //     return $files;
            // }, $_FILES);

            // $request = array_replace_recursive($request, $files);

            // $recursiveKSort = function (&$array) use (&$recursiveKSort) {
            //     ksort($array);
            //     foreach ($array as &$value) {
            //         if (is_array($value)) {
            //             $recursiveKSort($value);
            //         }
            //     }
            // };

            // $recursiveKSort($request);
        }

        if (!empty($body = file_get_contents("php://input")) and !is_null(json_decode($body, true))) {
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
}
