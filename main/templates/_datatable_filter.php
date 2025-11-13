<?php

if (!function_exists('datatable_filter_field')) {
    function datatable_filter_field($args, $errors = [])
    {
        if (!empty($args['textarea'])) {
            $params = array_reduce(['class', 'cols', 'disabled', 'id', 'name', 'placeholder', 'rows', 'type'], function ($r, $k) use ($args) { if (isset($args[$k])) { $r[$k] = $args[$k]; } return $r; }, []);

            if (!empty($args['required'])) {
                $params['required'] = null;
            }

            if (!empty($args['id']) && !empty($errors[$args['id']])) {
                $params['class'] = !empty($params['class']) ? $params['class'] . ' is-invalid' : 'is-invalid';
            }

            $rv = sprintf("<textarea %s>%s</textarea>\n", html_params($params), e($args['value'] ?? ''));
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
                'type',
                'value',
            ], function ($r, $k) use ($args) { if (isset($args[$k])) {
                $r[$k] = $args[$k];
            } return $r; }, []);

            if (!empty($args['required'])) {
                $params['required'] = null;
            }

            if (!empty($args['value'])) {
                $args['value'] = e($args['value']);
            }

            if (!empty($args['id']) && !empty($errors[$args['id']])) {
                $params['class'] = !empty($params['class']) ? $params['class'] . ' is-invalid' : 'is-invalid';
            }

            $rv = sprintf("<input %s>\n", html_params($params));
        }

        if (!empty($args['id']) && !empty($errors[$args['id']])) {
            $rv .= '<div class="invalid-feedback js-error">' . (is_array($errors[$args['id']]) ? join('<br>', $errors[$args['id']]) : $errors[$args['id']]) . '</div>';
        }

        return $rv;
    }
}

$filter_fields = [datatable_filter_field([
    'class' => 'form-control datatable-filter-control datatable-filter-control--search',
    'name' => 'filter_name',
    'type' => 'text',
    'value' => $_GET['filter_name'] ?? null,
], $filter_form_errors)];

if (!empty($storage['attributes'])) {
    foreach ($storage['attributes'] as $i => $v) {
        if (!empty($v['filter'])) {
            $field_name = 'filter_a' . $v['id'];

            switch ($v['type_name'] ?? '') {
                case 'date':
                case 'datetime':
                case 'float':
                case 'integer':
                    $filter_fields[] = datatable_filter_field([
                        'class' => 'form-control datatable-filter-control',
                        'id' => $field_name . '_0',
                        'name' => $field_name . '[0]',
                        'placeholder' => __('from'),
                        'type' => 'text',
                        'value' => $_GET[$field_name][0] ?? null,
                    ], $filter_form_errors) . datatable_filter_field([
                        'class' => 'form-control datatable-filter-control',
                        'id' => $field_name . '_1',
                        'name' => $field_name . '[1]',
                        'placeholder' => __('to'),
                        'type' => 'text',
                        'value' => $_GET[$field_name][1] ?? null,
                    ], $filter_form_errors);

                    break;
                default:
                    $filter_fields[] = datatable_filter_field([
                        'class' => 'form-control datatable-filter-control datatable-filter-control--search',
                        'name' => $field_name,
                        'type' => 'text',
                        'value' => $_GET[$field_name] ?? null,
                    ], $filter_form_errors);

                    break;
            }
        } else {
            $filter_fields[] = '';
        }
    }
}

if (array_filter($filter_fields)) {
    ob_start(); ?>
<a class="btn btn-secondary datatable-filter-button" id="datatable-filter-reset" href="<?php echo $clear_filter_url; ?>"<?php if (!$show_filter) { ?> style="display: none;"<?php } ?>><?php echo __('Clear'); ?></a>
<?php
        for ($i = count($filter_fields) - 1; $i >= 0; $i--) {
            if ($filter_fields[$i]) {
                $filter_fields[$i] .= ob_get_clean();
                break;
            }
        }

    echo '<tr id="datatable-filter">' . ($edit_permission ? '<td></td>' : '') . '<td class="datatable__column_filter">' . implode('</td><td class="datatable__column_filter">', $filter_fields) . '</td></tr>';
    unset($filter_fields);
}
