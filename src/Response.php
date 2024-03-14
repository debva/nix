<?php

namespace Debva\Nix;

class Response
{
    public function __invoke($data, $code = 200, $gzip = true, $sanitize = false, $except_sanitize = [])
    {
        if ($sanitize) {
            foreach ($data as $key => $value) {
                if (!in_array($key, $except_sanitize)) {
                    $data[$key] = preg_replace(['/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s', '/<!--(.|\s)*?-->/'], ['>', '<', '\\1', ''], $value);
                }
            }
        }

        if (is_int($code)) http_response_code($code);

        if ($gzip) ini_set('zlib.output_compression', 'on');

        $origin = !empty(getenv('ACCESS_CONTROL_ALLOW_ORIGIN')) ? getenv('ACCESS_CONTROL_ALLOW_ORIGIN') : '*';
        $methods = !empty(getenv('ACCESS_CONTROL_ALLOW_METHODS')) ?  getenv('ACCESS_CONTROL_ALLOW_METHODS') : 'GET, POST, PATCH, DELETE, OPTIONS';
        $headers = !empty(getenv('ACCESS_CONTROL_ALLOW_HEADERS')) ?  getenv('ACCESS_CONTROL_ALLOW_HEADERS') : '*';
        $contentType = is_array($data) || $data instanceof \stdClass ? 'application/json' : 'text/html';
        $charset = !empty(getenv('CHARSET')) ?  getenv('CHARSET') : 'UTF-8';

        header("Access-Control-Allow-Origin: {$origin}");
        header("Access-Control-Allow-Methods: {$methods}");
        header("Access-Control-Allow-Headers: {$headers}");
        header("Content-Type: {$contentType}; charset={$charset}");

        $class = new \stdClass;
        $class->buffer = is_array($data) || $data instanceof \stdClass
            ? json_encode($data)
            : $data;

        return $class;
    }
}
