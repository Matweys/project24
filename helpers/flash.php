<?php

declare(strict_types=1);

if (!function_exists('flash')) {
    function flash(string $message, string $category = 'info')
    {
        if (!isset($_SESSION['flash_messages'][$category])) {
            $_SESSION['flash_messages'][$category] = [];
        }

        $_SESSION['flash_messages'][$category][] = $message;
    }
}

if (!function_exists('flash_clear_message')) {
    function flash_clear_message()
    {
        unset($_SESSION['flash_messages']);
    }
}

if (!function_exists('flash_get_messages')) {
    function flash_get_messages(): array
    {
        if (!isset($_SESSION['flash_messages']) || !is_array($_SESSION['flash_messages'])) {
            return [];
        }

        $rv = $_SESSION['flash_messages'];
        unset($_SESSION['flash_messages']);

        return $rv;
    }
}
