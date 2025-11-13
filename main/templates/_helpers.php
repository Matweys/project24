<?php declare(strict_types=1);

if (! function_exists('column_sort')) {
    function column_sort($column_idx, $column_title, $link_title, $sort_idx, $sort_desc, $sort_url)
    {
        echo '<a href="'
        . call_user_func($sort_url, $column_idx, ($column_idx === $sort_idx)) . '" title="' . $link_title . '">'
        . $column_title
        . ($column_idx === $sort_idx ? '<img class="sort-icon" src="' . rtrim($GLOBALS['APP']->config['static_url'], '/') . '/assets/img/icon_chevron_' . ($sort_desc ? 'down' : 'up') . '.svg">' : '')
        . '</a>';
    }
}

if (! function_exists('error_message')) {
    function error_message($v)
    {
        if ($v) {
            ?><div class="alert alert-danger alert--small"><?=is_array($v) ? join('<br>', array_map('e', iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($v)), false))) : e($v)?></div><?php
        }
    }
}

if (! function_exists('icon_bool_formater')) {
    function icon_bool_formater($v)
    {
        echo $v ? '<img src="' . rtrim($GLOBALS['APP']->config['static_url'], '/') . '/assets/img/icon_ok.svg">' : '';
    }
}

if (! function_exists('pager')) {
    function pager($current_page, $count, $page_size, $generator)
    {
        if ($page_size <= 0) {
            return;
        }
        $num_pages = ceil($count / $page_size);

        if ($num_pages < 2) {
            return;
        }
        echo '<div class="pager">';

        $current_page = min(max(0, $current_page), $num_pages - 1);

        $min = $current_page - 4;
        $max = $current_page + 4 + 1;

        if ($min < 0) {
            $max = $max - $min;
        }

        if ($max >= $num_pages) {
            $min = $min - $max + $num_pages;
        }

        if ($min < 0) {
            $min = 0;
        }

        if ($max > $num_pages) {
            $max = $num_pages;
        }

        if ($current_page > 0) {
            echo '<a class="pager__item" href="' . call_user_func($generator, $current_page - 1) . '">' . _x('Previous', 'pager') . '</a>';
        }

        for ($i = $min; $i < $max; $i++) {
            if ($i == $current_page) {
                echo '<span class="pager__item active">' . ($i + 1) . '</span>';
            } else {
                echo '<a class="pager__item" href="' . call_user_func($generator, $i) . '">' . ($i + 1) . '</a>';
            }
        }

        if ($current_page + 1 < $num_pages) {
            echo '<a class="pager__item" href="' . call_user_func($generator, $current_page + 1) . '">' . _x('Next', 'pager') . '</a>';
        }

        echo '</div>';
    }
}

if (! function_exists('pager_compact')) {
    function pager_compact($current_page, $count, $page_size, $generator)
    {
        if ($count <= 0 || $page_size <= 0) {
            return;
        }
        $num_pages = ceil($count / $page_size);
        $current_page = min(max(0, $current_page), $num_pages - 1);
        $offset = (int) max(0, min($current_page, $num_pages - 1) * $page_size);

        if ($num_pages > 1) {
            printf('<div class="pager-compact"><div class="pager-compact__info">' . _x('%d–%d of %d', 'pager') . '</div>', $offset + 1, min($offset + $page_size, $count), $count);
            echo($current_page > 0 ? '<a class="pager-compact__item" href="' . call_user_func($generator, $current_page - 1) . '">❮</a>' : '<span class="pager-compact__item pager-compact__item--disabled">❮</span>');
            echo($current_page + 1 < $num_pages ? '<a class="pager-compact__item" href="' . call_user_func($generator, $current_page + 1) . '">❯</a>' : '<span class="pager-compact__item pager-compact__item--disabled">❯</span>');
            echo '</div>';
        }
    }
}

if (! function_exists('render_checkbox')) {
    function render_checkbox($args)
    {
        $params = array_reduce([
            'checked',
            'class',
            'disabled',
            'id',
            'name',
            'type',
            'value',
        ], function ($r, $k) use ($args) {
            if (isset($args[$k])) {
                $r[$k] = $args[$k];
            }
            return $r;
        }, []);

        $input_field = (!empty($args['field']) ? $args['field'] : sprintf('<input %s>', html_params($params))); ?><div<?php if (!empty($args['group_class'])) { ?> class="<?=$args['group_class']?>"<?php } ?><?php if (!empty($args['group_style'])) { ?> style="<?=$args['group_style']?>"<?php } ?>><?=$input_field?><label for="<?=e($args['id'] ?? '')?>"><?=e($args['label'] ?? '')?></label><?php

        if (!empty($args['description'])) {
            ?><small class="form-text text-muted"><?=e($args['description'])?></small><?php
        } ?></div><?php
    }
}

if (! function_exists('render_field')) {
    function render_field($args, $errors = [])
    {
        ?><div<?php if (!empty($args['group_class'])) { ?> class="<?=$args['group_class']?>"<?php } ?><?php if (!empty($args['group_style'])) { ?> style="<?=$args['group_style']?>"<?php } ?>><?php

        if (!empty($args['label'])) {
            ?><label for="<?=e($args['id'] ?? '')?>"<?=!empty($args['label_class']) ? sprintf(' class="%s"', $args['label_class']) : ''?>><?=e($args['label'] ?? '')?></label><?php
        }

        if (!empty($args['field'])) {
            echo $args['field'];
        } else {
            if (!empty($args['textarea'])) {
                $params = array_reduce([
                    'class',
                    'cols',
                    'disabled',
                    'id',
                    'name',
                    'placeholder',
                    'rows',
                    'style',
                    'type',
                ], function ($r, $k) use ($args) {
                    if (isset($args[$k])) {
                        $r[$k] = $args[$k];
                    }
                    return $r;
                }, []);

                if (!empty($args['autofocus'])) {
                    $params['autofocus'] = null;
                }

                if (!empty($args['required'])) {
                    $params['required'] = null;
                }

                if (!empty($args['id']) && !empty($errors[$args['id']])) {
                    $params['class'] = !empty($params['class']) ? $params['class'] . ' is-invalid' : 'is-invalid';
                }

                printf("<textarea %s>%s</textarea>\n", html_params($params), e($args['value'] ?? ''));
            } else {
                $params = array_reduce([
                    'checked',
                    'class',
                    'disabled',
                    'id',
                    'maxlength',
                    'minlength',
                    'name',
                    'pattern',
                    'placeholder',
                    'size',
                    'style',
                    'type',
                    'value',
                ], function ($r, $k) use ($args) {
                    if (isset($args[$k])) {
                        $r[$k] = $args[$k];
                    }
                    return $r;
                }, []);

                if (!empty($args['autofocus'])) {
                    $params['autofocus'] = null;
                }

                if (!empty($args['required'])) {
                    $params['required'] = null;
                }

                if (!empty($args['value'])) {
                    $args['value'] = e($args['value']);
                }

                if (!empty($args['id']) && !empty($errors[$args['id']])) {
                    $params['class'] = !empty($params['class']) ? $params['class'] . ' is-invalid' : 'is-invalid';
                }

                printf("<input %s>\n", html_params($params));
            }
        }
        if (!empty($args['id']) && !empty($errors[$args['id']])) {
            ?><div class="invalid-feedback"><?=is_array($errors[$args['id']]) ? join('<br>', $errors[$args['id']]) : $errors[$args['id']]?></div><?php
        }

        if (!empty($args['description'])) {
            ?><small class="form-text"><?=$args['description']?></small><?php
        } ?></div><?php
    }
}

if (! function_exists('reverce_pager')) {
    function reverce_pager($current_page, $num_pages, $generator)
    {
        if ($num_pages < 2) {
            return;
        }
        echo '<div class="pager">';

        if ($current_page == 0) {
            $current_page = $num_pages;
        }

        $min = $current_page - 3;
        $max = $current_page + 3;

        if ($min < 1) {
            $max = $max - $min;
        }

        if ($max >= $num_pages) {
            $min = $min - $max + $num_pages;
        }

        if ($min < 1) {
            $min = 1;
        }

        if ($max > $num_pages) {
            $max = $num_pages;
        }

        if ($min > 1) {
            echo '<a href="' . call_user_func($generator, 1) . '">&laquo;</a>';
        }

        if ($current_page > 1) {
            echo '<a href="' . call_user_func($generator, $current_page - 1) . '">&lt;</a>';
        }

        for ($i = $min; $i <= $max; $i++) {
            if ($i == $current_page) {
                echo '<span>' . $i . '</span>';
            } else {
                echo '<a href="' . call_user_func($generator, ($i < $num_pages ? $i : 0)) . '">' . $i . '</a>';
            }
        }

        if ($current_page + 1 < $num_pages) {
            echo '<a href="' . call_user_func($generator, $current_page + 1) . '">&gt;</a>';
        }

        if ($max < $num_pages) {
            echo '<a href="' . call_user_func($generator, 0) . '">&raquo;</a>';
        }

        echo '</div>';
    }
}
