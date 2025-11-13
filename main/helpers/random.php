<?php declare(strict_types=1);

if (!function_exists('generate_random_string')) {
    function generate_random_string($length = 32)
    {
        $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digits = '0123456789';
        $punctuation = '-_.';

        $chars = str_repeat($letters, 10) . str_repeat($digits, 3) . $punctuation;
        $char_length = strlen($chars) - 1;

        $rv = '';
        $prev = null;

        for ($i = 0; $i < $length; $i++) {
            $c = ($rv ? $chars[random_int(0, $char_length)] : $letters[random_int(0, strlen($letters) - 1)]);

            while (($prev && strtolower($prev) == strtolower($c)) || ($prev && strpos($punctuation, $prev) && strpos($punctuation, $c))) {
                $c = $chars[random_int(0, $char_length)];
            }

            $rv .= $c;
            $prev = $c;
        }

        return $rv;
    }
}
