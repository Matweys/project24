<?php

if (!function_exists('get_redirect_target')) {
    function get_redirect_target($param_name = 'url')
    {
        $url = $_REQUEST[$param_name] ?? '';
        return ($url && is_safe_url($url)) ? $url : null;
    }
}

if (!function_exists('is_safe_url')) {
    function is_safe_url($url)
    {
        $url = parse_url($url);

        $scheme = strtolower($url['scheme'] ?? '');

        return (! $scheme || $scheme === 'http' || $scheme === 'https')
        && (!isset($url['host']) || strtolower(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '') === strtolower($url['host']))
        && (!isset($url['port']) || strtolower(isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '') === strtolower($url['port']));
    }
}
