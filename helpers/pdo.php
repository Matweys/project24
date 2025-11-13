<?php declare(strict_types=1);

if (!function_exists('pdo_bind')) {
    function pdo_bind($stmt, array $args)
    {
        $is_assoc = $args && array_keys($args) !== range(0, count($args) - 1);

        foreach ($args as $k => $v) {
            switch (true) {
                case is_int($v):
                    $type = \PDO::PARAM_INT;
                    break;
                case is_bool($v):
                    $type = \PDO::PARAM_BOOL;
                    break;
                case is_null($v):
                    $type = \PDO::PARAM_NULL;
                    break;
                default:
                    $type = \PDO::PARAM_STR;
            }
            $stmt->bindValue($is_assoc ? $k : $k + 1, $v, $type);
        }
    }
}

if (!function_exists('pdo_bind_param')) {
    function pdo_bind_param($stmt, $name, &$value)
    {
        switch (true) {
            case is_int($value):
                $type = \PDO::PARAM_INT;
                break;
            case is_bool($value):
                $type = \PDO::PARAM_BOOL;
                break;
            case is_null($value):
                $type = \PDO::PARAM_NULL;
                break;
            default:
                $type = \PDO::PARAM_STR;
        }
        $stmt->bindParam($name, $value, $type);
    }
}
