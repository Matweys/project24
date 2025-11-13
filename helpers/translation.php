<?php

use POMO\MO;

if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        global $l10n;
        return isset($l10n[$domain]) ? $l10n[$domain]->translate($text) : $text;
    }
}

if (!function_exists('_n')) {
    function _n($single, $plural, $number, $domain = 'default')
    {
        global $l10n;
        return isset($l10n[$domain]) ? $l10n[$domain]->translate_plural($single, $plural, $number) : ($number > 1 ? $plural : $single);
    }
}

if (!function_exists('_nx')) {
    function _nx($single, $plural, $number, $context, $domain = 'default')
    {
        global $l10n;
        return isset($l10n[$domain]) ? $l10n[$domain]->translate_plural($single, $plural, $number, $context) : ($number > 1 ? $plural : $single);
    }
}

if (!function_exists('_x')) {
    function _x($text, $context, $domain = 'default')
    {
        global $l10n;
        return isset($l10n[$domain]) ? $l10n[$domain]->translate($text, $context) : $text;
    }
}

if (!function_exists('load_textdomain')) {
    function load_textdomain($domain, $mofile)
    {
        global $l10n;

        if (is_readable($mofile)) {
            $mo = new MO();
            if ($mo->import_from_file($mofile)) {
                if (isset($l10n[$domain])) {
                    $mo->merge_with($l10n[$domain]);
                }
                $l10n[$domain] = &$mo;
            }
        }
    }
}

if (!function_exists('unload_textdomain')) {
    function unload_textdomain($domain)
    {
        global $l10n;

        if (isset($l10n[$domain])) {
            unset($l10n[$domain]);
        }
    }
}
