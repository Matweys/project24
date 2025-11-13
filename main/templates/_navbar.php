<?php

declare(strict_types=1);

if (!function_exists('navbar_item_active')) {
	function navbar_item_active(?array $config, string $addr, string $active_item)
	{
		if (($pos = strpos($addr, '?')) !== false) {
			$addr = substr($addr, 0, $pos);
		}

		$addr = trim($addr, '/');

		$uri = $_SERVER['REQUEST_URI'] ?? '';

		if (($pos = strpos($uri, '?')) !== false) {
			$uri = substr($uri, 0, $pos);
		}

		$base_url = trim($config['base_url'], '/');

		return $addr === trim(($base_url ? $base_url . '/' : '') . ($active_item ? trim($active_item, '/') : ''), '/');
	}
}
