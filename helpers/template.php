<?php declare(strict_types=1);

if (!function_exists('render_template')) {
    function render_template(string $file, ?array $args = null, ?string $template_path = null)
    {
        if (is_array($args)) {
            extract($args);
        }

        include($template_path ?: __DIR__ . '/../templates/') . $file . '.php';
    }
}
