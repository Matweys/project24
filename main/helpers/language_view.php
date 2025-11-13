<?php declare(strict_types=1);

if (!function_exists('language_switch')) {
    function language_switch($current_language, $languages)
    {
        printf(
            '<select %s>%s</select>',
            html_params([
                'class' => 'form-select js-language-switch language-switch',
            ]),
            implode('', array_map(function ($v) use ($current_language) {
                return sprintf('<option value="%s"%s>%s</option>', $v['name'], ($current_language === $v['name'] ? ' selected' : ''), $v['title']);
            }, $languages))
        );
    }
}
