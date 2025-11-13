<?php

declare(strict_types=1);

if (!function_exists('flash_messages')) {
	function flash_messages()
	{
		foreach (flash_get_messages() as $k => $v) {
			?><div class="alert<?=($k === 'error' ? ' alert-danger' : ' alert-primary')?>" style="display:table;margin:0 auto;"><?=(is_array($v) ? join('<br>', array_map('e', $v)) : $v)?></div><?php
		}
	}
}
