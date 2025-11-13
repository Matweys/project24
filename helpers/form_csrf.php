<?php

declare(strict_types=1);

if (!function_exists('form_csrf_field')) {
    function form_csrf_field(?array $config, ?string $form_id = null)
    {
        echo '<input name="csrf_token" type="hidden" value="' . (form_csrf_token($config, $form_id) ?: '') . '">';
    }
}

if (!function_exists('form_csrf_token')) {
    function form_csrf_token(?array $config, ?string $form_id = null)
    {
        try {
            return (new \URLSafeTimedSerializer\URLSafeTimedSerializer($config['site_key'], (int) ($config['csrf_expire'] ?? 1800)))->generate($form_id ?: true, false);
        } catch (Exception $e) {
            error_log((string) $e);
        }
    }
}

if (!function_exists('form_csrf_validate')) {
    function form_csrf_validate(?array $config, ?string $form_id = null)
    {
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['csrf_token'])) {
            $v = null;

            try {
                $v = (new \URLSafeTimedSerializer\URLSafeTimedSerializer($config['site_key'], (int) ($config['csrf_expire'] ?? 1800)))->load($_POST['csrf_token'], false);
            } catch (Exception $e) {
                error_log((string) $e);
            }

            return $form_id ? (is_string($form_id) && is_string($v) && hash_equals($form_id, $v)) : (bool) $v;
        }
    }
}
