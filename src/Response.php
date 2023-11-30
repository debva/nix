<?php

namespace Debva\Nix;

class Response
{
    public function __invoke($data, $code = 200, $gzip = true, $sanitize = false, $except_sanitize = [])
    {
        $origin = empty(getenv('ACCESS_CONTROL_ALLOW_ORIGIN')) ? getenv('ACCESS_CONTROL_ALLOW_ORIGIN') : '*';
        $methods = empty(getenv('ACCESS_CONTROL_ALLOW_METHODS')) ?  getenv('ACCESS_CONTROL_ALLOW_METHODS') : 'GET, POST, DELETE, OPTIONS';
        $headers = empty(getenv('ACCESS_CONTROL_ALLOW_HEADERS')) ?  getenv('ACCESS_CONTROL_ALLOW_HEADERS') : '*';

        if (is_int($code)) http_response_code($code);
        header("Access-Control-Allow-Origin: {$origin}");
        header("Access-Control-Allow-Methods: {$methods}");
        header("Access-Control-Allow-Headers: {$headers}");
        header('Content-Type: application/json; charset=utf-8');

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == "OPTIONS") {
            header("HTTP/1.1 200 OK");
            die();
        }

        if ($sanitize) {
            foreach ($data as $key => $value) {
                if (!in_array($key, $except_sanitize)) {
                    $data[$key] = preg_replace(['/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s', '/<!--(.|\s)*?-->/'], ['>', '<', '\\1', ''], $value);
                }
            }
        }

        if ($gzip) ini_set('zlib.output_compression', 'on');

        print(json_encode($data));
        exit(1);
    }
}
