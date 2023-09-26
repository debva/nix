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
                $result = [];
                foreach ($keys as $key) {
                    $req = $request;
                    foreach (explode('.', $key) as $k) $req = isset($req[$k])
                        ? $req[$k] : (!is_null($default) ? $default : null);

                    $result[$key] = $req;
                }

                return $result;
            }

            foreach (explode('.', $keys) as $key) $request = isset($request[$key])
                ? $request[$key]
                : (!is_null($default) ? $default : null);
        }

        return $request;
    }
}
