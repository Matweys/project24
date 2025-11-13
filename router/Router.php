<?php

declare(strict_types=1);

namespace Router;

class Router
{
    public static function start(?array $config, array $routes)
    {
        $base_url = !empty($config['base_url']) ? trim($config['base_url'], '/') : '';
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        $pos = strpos($uri, '?');

        if ($pos !== false) {
            $uri = substr($uri, 0, $pos);
        }

        $trailing_slash = strrpos($uri, '/') === strlen($uri) - 1;

        $uri = static::normpath($uri) . ($trailing_slash && $uri !== '/' ? '/' : '');

        $addr = trim($uri, '/');

        if ($base_url) {
            if ($addr === $base_url) {
                $addr = '';
            } elseif (strncmp($addr, $base_url . '/', strlen($base_url) + 1) === 0) {
                $addr = substr($addr, strlen($base_url) + 1);
            } else {
                // запрос вообще не в нашей подпапке
                http_response_code(404);
                exit;
            }
        }

        foreach ($routes as $route) {
            if (!is_array($route) || empty($route[0]) || !is_string($route[0]) || empty($route[1]) || !is_string($route[1]) || empty($route[2]) || !is_string($route[2])) {
                continue;
            }

            $args = [];
            $match = false;

            $route_addr = trim($route[0], '/');
            $route_max_params = !empty($route[3]) ? (int) $route[3] : 0;

            $route_slash = $route_max_params || (strrpos($route[0], '/') === strlen($route[0]) - 1);

            if (!$route_addr && $addr === '') {
                $match = true;
            } elseif ($route_addr && $route_addr === $addr) {
                $match = true;
            } elseif ($route_addr && $route_max_params && substr_count($addr, '/') <= (substr_count($route_addr, '/') + $route_max_params) && preg_match("@^{$route_addr}\b@i", $addr)) {
                $args = array_filter(explode('/', preg_replace("@^{$route_addr}\b/?@i", '', $addr)));
                $match = true;
            }

            if ($match) {
                if ('' !== $addr && ($trailing_slash || $args) && !$route_slash) {
                    continue;
                } elseif (!$trailing_slash && !$args && ($route_slash || $addr === '')) {
                    $qs = http_build_query($_GET, '', '&');
                    http_response_code(301);
                    header('Location: /' . trim(($base_url ? $base_url . '/' : '') . $addr, '/') . '/' . ($qs ? '?' . $qs : ''));
                    exit;
                }

                if (class_exists($route[1])) {
                    $controller = new $route[1]($config, $route[4] ?? null);

                    if (is_callable([$controller, $route[2]])) {
                        $controller->{$route[2]}(...$args);
                        exit;
                    }
                }

                break;
            }
        }

        http_response_code(404);
        exit;
    }

    public static function normpath(string $path): string
    {
        if (!$path) {
            return '';
        }

        $initial_slashes = (int)(strpos($path, '/') === 0);

        if ($initial_slashes && strpos($path, '//') === 0 && strpos($path, '///') === false) {
            $initial_slashes = 2;
        }

        $comps = explode('/', $path);
        $new_comps = [];

        foreach ($comps as $comp) {
            if ($comp === '' || $comp === '.') {
                continue;
            }
            if (($comp !== '..') || (!$initial_slashes && !$new_comps) || ($new_comps && (end($new_comps) === '..'))) {
                array_push($new_comps, $comp);
            } elseif ($new_comps) {
                array_pop($new_comps);
            }
        }

        $comps = $new_comps;
        $path = implode('/', $comps);

        if ($initial_slashes) {
            $path = str_repeat('/', $initial_slashes) . $path;
        }

        return $path;
    }
}
