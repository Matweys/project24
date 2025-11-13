<?php

declare(strict_types=1);

namespace Helpers;

class Log
{
    public static function message(\PDO $db, string $message, ?int $storage = null)
    {
        try {
            $r = $db->prepare('insert into log (message, storage_id) values (?, ?)');
            $r->execute([$message, $storage]);
        } catch (PDOException $e) {
            error_log((string) $e);
        }
    }
}
