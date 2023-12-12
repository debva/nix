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

        if (is_int($code)) {
            http_response_code($code);
        }

        if ($gzip) {
            ini_set('zlib.output_compression', 'on');
        }

        return json_encode($data);
    }
}
