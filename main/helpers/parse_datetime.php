<?php

if (!function_exists('parse_date')) {
    function parse_date($dt)
    {
        if ($dt && is_string($dt)) {
            foreach ([
                'd-m-Y',
                'd.m.Y',
                'd/m/Y',
                'j-m-Y',
                'j.m.Y',
                'j/m/Y',
                'Y-m-d',
                'Y-m-j',
            ] as $v) {
                $rv = date_create_from_format($v, $dt);
                $e = date_get_last_errors();
                if ($rv && empty($e['error_count']) && empty($e['warning_count'])) {
                    return $rv->setTime(0, 0);
                }
            }
        }
    }
}

if (!function_exists('parse_datetime')) {
    function parse_datetime($dt)
    {
        if ($dt && is_string($dt)) {
            foreach ([
                'd-m-Y G:i:s',
                'd.m.Y G:i:s',
                'd/m/Y G:i:s',
                'j-m-Y G:i:s',
                'j.m.Y G:i:s',
                'j/m/Y G:i:s',
                'Y-m-d G:i:s',
                'Y-m-j G:i:s',
                'd-m-Y H:i:s',
                'd.m.Y H:i:s',
                'd/m/Y H:i:s',
                'j-m-Y H:i:s',
                'j.m.Y H:i:s',
                'j/m/Y H:i:s',
                'Y-m-d H:i:s',
                'Y-m-j H:i:s',
                'd-m-Y G:i',
                'd.m.Y G:i',
                'd/m/Y G:i',
                'j-m-Y G:i',
                'j.m.Y G:i',
                'j/m/Y G:i',
                'Y-m-d G:i',
                'Y-m-j G:i',
                'd-m-Y H:i',
                'd.m.Y H:i',
                'd/m/Y H:i',
                'j-m-Y H:i',
                'j.m.Y H:i',
                'j/m/Y H:i',
                'Y-m-d H:i',
                'Y-m-j H:i',
                'd-m-Y',
                'd.m.Y',
                'd/m/Y',
                'j-m-Y',
                'j.m.Y',
                'j/m/Y',
                'Y-m-d',
                'Y-m-j',
                'Y-m-d\TH:i',
                'Y-m-d\TH:i.u',
                'Y-m-d\TH:i.uP',
                'Y-m-d\TH:i:s',
                'Y-m-d\TH:i:s.u',
                'Y-m-d\TH:i:s.uP',
                'Y-m-d\TH:i:sP',
                'Y-m-d\TH:iP',
                DATE_ATOM,
            ] as $v) {
                $rv = date_create_from_format($v, $dt);
                $e = date_get_last_errors();
                if ($rv && empty($e['error_count']) && empty($e['warning_count'])) {
                    return $rv;
                }
            }
        }
    }
}
