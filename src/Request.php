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
                $result = array_intersect_key($request, array_flip($keys));
                return empty($result) ? $default : $result;
            }

            return isset($request[$keys])
                ? $request[$keys]
                : (!is_null($default) ? $default : null);
        }

        return $request;
    }
}
