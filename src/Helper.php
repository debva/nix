<?php

if (!function_exists('dd')) {
    function dd(...$vars)
    {
        var_dump(...$vars);
        exit;
    }
}

if (!function_exists('_startsWith')) {
    function _startsWith($haystack, $needle, $caseInsensitive = false)
    {
        if ($caseInsensitive) {
            $haystack = strtolower($haystack);
            $needle = strtolower($needle);
        }

        return substr($haystack, 0, strlen($needle)) === $needle;
    }
}

if (!function_exists('_endsWith')) {
    function _endsWith($haystack, $needle, $caseInsensitive = false)
    {
        if ($caseInsensitive) {
            $haystack = strtolower($haystack);
            $needle = strtolower($needle);
        }

        return substr($haystack, -strlen($needle)) === $needle;
    }
}
