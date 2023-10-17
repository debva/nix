<?php

namespace Debva\Nix;

class Request
{
    public function __invoke($keys = null, $default = null)
    {
        $request = $_REQUEST;

        if (!empty($_FILES)) {
            $request = array_merge($request, $_FILES);
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
            $result = [];
            $arrayRef = &$result;

            foreach ($keys as $key) {
                $arrayRef = &$arrayRef[$key];
                $request = isset($request[$key])
                    ? $request[$key]
                    : (!is_null($default) ? $default : null);
            }

            $arrayRef = $request;
            return $request;
        }

        return $request;
    }
}
