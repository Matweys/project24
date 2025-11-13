<?php declare(strict_types=1);

namespace Helpers;

class Form
{
    public static function filterInlineFormInput(string $id, array $filter_args, int $method = INPUT_POST)
    {
        $rv = [];
        foreach (static::getInlineFormIndices($id) as $i) {
            $data = [];

            foreach ($filter_args as $k => $v) {
                if (is_array($v) && isset($v[0])) {
                    $filter = [$v[0], ['options' => $v[1] ?? 'default', 'flags' => $v[2] ?? 0]];
                } elseif (is_int($v)) {
                    $filter = [$v, 0];
                } else {
                    $filter = [FILTER_UNSAFE_RAW, 0];
                }

                $data[$k] = filter_input($method, sprintf('%s-%d-%s', $id, $i, $k), $filter[0], $filter[1]);
            }

            $rv[] = $data;
        }
        return $rv;
    }

    public static function filterInput(array $filter_args, $method = INPUT_POST)
    {
        $rv = filter_input_array($method, array_reduce(
            array_keys($filter_args),
            function ($r, $k) use ($filter_args) {
                $v = $filter_args[$k];

                if (is_array($v) && isset($v[0])) {
                    $r[$k] = ['filter' => $v[0], 'options' => $v[1] ?? 'default', 'flags' => $v[2] ?? null];
                } elseif (is_int($v)) {
                    $r[$k] = $v;
                } else {
                    $r[$k] = ['filter' => FILTER_UNSAFE_RAW];
                }

                return $r;
            },
            []
        ));

        if ($rv) {
            $rv = array_map(function ($x) {
                return is_string($x) ? trim($x) : $x;
            }, $rv);
        }

        return $rv;
    }

    public static function getFormData(array $fields, array $data = [])
    {
        $rv = [];

        foreach ($fields as $v) {
            $rv[$v] = $_POST ? $_POST[$v] ?? null : $data[$v] ?? null;
        }

        return $rv;
    }

    public static function getInlineFormData($id, array $fields, array $data = [], $pk_name = 'id')
    {
        $i = 0;
        $rv = [];

        foreach (static::getInlineFormIndices($id) as $index) {
            $form_data = [];
            foreach ($fields as $v) {
                $k = sprintf('%s-%d-%s', $id, $index, $v);
                $form_data[$v] = $_POST ? $_POST[$k] ?? null : $data[$id][$i][$v] ?? null;
            }
            if ($pk_name) {
                $form_data[$pk_name] = $data[$id][$i][$pk_name] ?? null;
            }
            $rv[] = $form_data;
            $i++;
        }

        if (isset($data[$id]) && is_array($data[$id])) {
            for (; $i < count($data[$id]); $i++) {
                $form_data = [];
                foreach ($fields as $v) {
                    $form_data[$v] = $data[$id][$i][$v] ?? null;
                }
                if ($pk_name) {
                    $form_data[$pk_name] = $data[$id][$i][$pk_name] ?? null;
                }
                $rv[] = $form_data;
            }
        }

        return $rv;
    }

    public static function getInlineFormIndices(string $prefix)
    {
        $offset = strlen($prefix) + 1;
        $rv = [];

        foreach ($_POST as $k => $v) {
            if (strpos($k, $prefix) === 0) {
                $k = explode('-', substr($k, $offset), 2)[0];
                if (ctype_digit($k)) {
                    $rv[] = $k;
                }
            }
        }

        $rv = array_unique($rv);
        sort($rv);

        return $rv;
    }

    public static function validateFormFields(array $fields, array $data, array &$errors)
    {
        foreach ($fields as $k => $v) {
            if (($v['filter'] ?? null) === FILTER_VALIDATE_BOOLEAN || ($v['filter'][0] ?? null) === FILTER_VALIDATE_BOOLEAN || empty($_POST[$k]) && !empty($v['optional'])) {
                continue;
            }

            if (!isset($data[$k]) || is_null($data[$k]) || $data[$k] === false) {
                $errors[$k] = !empty($v['error']) ? $v['error'] : 'Неверное значение';
            }
        }
    }
}
