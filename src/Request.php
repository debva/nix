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
                    $results[array_keys($result)[0]] = array_values($result)[0];
                }

                return $results;
            }

            $result = [];
            $arrayRef = &$result;

            foreach (explode('.', $keys) as $key) {
                $arrayRef = &$arrayRef[$key];
                $request = isset($request[$key])
                    ? $request[$key]
                    : (!is_null($default) ? $default : null);
            }

            $arrayRef = $request;
            return $result;
        }

        return $request;
    }
}
