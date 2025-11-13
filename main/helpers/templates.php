<?php

declare(strict_types=1);

if (!function_exists('dateformat')) {
    function dateformat($dt, $format = 'd MMMM yyyy', $locale = null)
    {
        if (is_string($dt)) {
            $dt = date_create($dt);
        }

        if ($dt instanceof \DateTime) {
            $fmt = datefmt_create(($locale ? $locale : $GLOBALS['APP']->lang ?? 'ru'), IntlDateFormatter::NONE, IntlDateFormatter::NONE);
            datefmt_set_pattern($fmt, $format);
            return datefmt_format($fmt, $dt);
        }
    }
}

if (!function_exists('daterange_format')) {
    function daterange_format($start, $end, array $format = [], $locale = null)
    {
        if (is_string($start)) {
            $start = date_create($start);
        }

        if (is_string($end)) {
            $end = date_create($end);
        }

        $default_format = ['d MMMM yyyy', 'd MMMM', 'MMMM', '%1$d–%2$d %3$s %4$s', '%1$d–%2$d %3$s', '%s — %s %s', '%s — %s'];

        if ($start instanceof \DateTime && $end instanceof \DateTime) {
            $now = date_create();
            $l = ($locale ? $locale : $GLOBALS['APP']->lang ?? 'ru');

            if ($start->format('Y') !== $end->format('Y')) {
                $fmt = datefmt_create($l, IntlDateFormatter::NONE, IntlDateFormatter::NONE);
                datefmt_set_pattern($fmt, (isset($format[0]) ? $format[0] : $default_format[0]));
                return sprintf((isset($format[6]) ? $format[6] : $default_format[6]), datefmt_format($fmt, $start), datefmt_format($fmt, $start));
            } elseif ($start->format('M') !== $end->format('M')) {
                $fmt = datefmt_create($l, IntlDateFormatter::NONE, IntlDateFormatter::NONE);
                datefmt_set_pattern($fmt, (isset($format[1]) ? $format[1] : $default_format[1]));

                if ($start->format('Y') !== $now->format('Y')) {
                    return sprintf((isset($format[5]) ? $format[5] : $default_format[5]), datefmt_format($fmt, $start), datefmt_format($fmt, $end), $start->format('Y'));
                } else {
                    return sprintf((isset($format[6]) ? $format[6] : $default_format[6]), datefmt_format($fmt, $start), datefmt_format($fmt, $end));
                }
            } elseif ($start->format('d') === $end->format('d')) {
                $fmt = datefmt_create($l, IntlDateFormatter::NONE, IntlDateFormatter::NONE);

                if ($start->format('Y') !== $now->format('Y')) {
                    datefmt_set_pattern($fmt, (isset($format[0]) ? $format[0] : $default_format[0]));
                    return datefmt_format($fmt, $start);
                } else {
                    datefmt_set_pattern($fmt, (isset($format[1]) ? $format[1] : $default_format[1]));
                    return datefmt_format($fmt, $start);
                }
            }

            $fmt = datefmt_create($l, IntlDateFormatter::NONE, IntlDateFormatter::NONE);
            datefmt_set_pattern($fmt, (isset($format[2]) ? $format[2] : $default_format[2]));

            if ($start->format('Y') !== $now->format('Y')) {
                return sprintf((isset($format[3]) ? $format[3] : $default_format[3]), $start->format('j'), $end->format('j'), datefmt_format($fmt, $start), $start->format('Y'));
            } else {
                return sprintf((isset($format[4]) ? $format[4] : $default_format[4]), $start->format('j'), $end->format('j'), datefmt_format($fmt, $start));
            }
        }
    }
}

if (!function_exists('datetime_format')) {
    function datetime_format($dt, $format = 'd.MM.yyyy H:mm', $locale = null)
    {
        if (is_string($dt)) {
            $dt = date_create($dt);
        }

        if ($dt instanceof \DateTime) {
            $fmt = datefmt_create(($locale ? $locale : $GLOBALS['APP']->lang ?? 'ru'), IntlDateFormatter::NONE, IntlDateFormatter::NONE);
            datefmt_set_pattern($fmt, $format);
            return datefmt_format($fmt, $dt);
        }
    }
}

if (!function_exists('datetime_short_format')) {
    function datetime_short_format($dt)
    {
        if (is_string($dt)) {
            $dt = date_create($dt);
        }

        if ($dt instanceof \DateTime) {
            $l = $GLOBALS['APP']->lang ?? 'ru';
            $now = date_create();

            if ($dt->format('Y') !== $now->format('Y')) {
                $fmt = datefmt_create($l, IntlDateFormatter::NONE, IntlDateFormatter::NONE);
                datefmt_set_pattern($fmt, 'd MMMM yyyy');
                return datefmt_format($fmt, $dt);
            } elseif ($dt->format('M') === $now->format('M') && $dt->format('d') === $now->format('d')) {
                return $dt->format('H:i');
            } elseif (abs((new DateTime())->getTimestamp() - $dt->getTimestamp()) < 7 * 86400) {
                $fmt = datefmt_create($l, IntlDateFormatter::NONE, IntlDateFormatter::NONE);
                datefmt_set_pattern($fmt, 'd MMMM H:mm');
                return datefmt_format($fmt, $dt);
            }

            $fmt = datefmt_create($l, IntlDateFormatter::NONE, IntlDateFormatter::NONE);
            datefmt_set_pattern($fmt, 'd MMMM');

            return datefmt_format($fmt, $dt);
        }
    }
}

if (!function_exists('e')) {
    function e($str)
    {
        return $str ? htmlspecialchars((string) $str, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8') : '';
    }
}

if (!function_exists('file_size_format')) {
    function file_size_format($size)
    {
        $size = (int) $size;

        if ($size < 1000 * 1000) {
            return round($size / 1000) . ' K';
        } elseif ($size < 1000 * 1000 * 1000) {
            return sprintf('%.1f M', ($size / (1000 * 1000)));
        } elseif ($size < 1000 * 1000 * 1000 * 1000) {
            return sprintf('%.1f G', ($size / (1000 * 1000 * 1000)));
        } else {
            return $size;
        }
    }
}

if (!function_exists('html_params')) {
    function html_params(array $args)
    {
        ksort($args);
        $rv = [];

        foreach ($args as $k => $v) {
            if (is_null($v)) {
                $rv[] = $k;
            } elseif (is_bool($v)) {
                if ($v) {
                    $rv[] = $k;
                }
            } else {
                $rv[] = sprintf('%s="%s"', $k, htmlspecialchars((string) $v, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8'));
            }
        }

        return implode(' ', $rv);
    }
}

if (!function_exists('nl2p')) {
    function nl2p($v)
    {
        $rv = '';

        foreach (preg_split('/(?:(?:\r\n){2,}|(?:\n\r){2,}|\r{2,}|\n{2,})/', trim($v)) as $p) {
            $rv .= '<p>' . nl2br($p, false) . "</p>\n\n";
        }

        return trim($rv);
    }
}

if (!function_exists('st')) {
    function st($v)
    {
        return strip_tags($v);
    }
}
