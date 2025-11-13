<?php

declare(strict_types=1);

if (!function_exists('datatable_sort_link')) {
	function datatable_sort_link(?array $config, int $column_idx, string $column_title, string $link_title, ?int $sort_idx, bool $sort_desc, callable $sort_url)
	{
		echo '<a href="'
		. call_user_func($sort_url, $column_idx, ($column_idx === $sort_idx)) . '" title="' . $link_title . '">'
		. $column_title
		. ($column_idx === $sort_idx ? '<svg style="height:20px;width:20px;"><use xlink:href="#chevron-'. ($sort_desc ? 'down' : 'up') .'"></use></svg>' : '')
		. '</a>';
	}
}
