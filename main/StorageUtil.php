<?php

declare(strict_types=1);

namespace Main;

class StorageUtil
{
    public static function getFileTable(\PDO $db, int $storage_id): ?string
    {
        $table = 'file_' . $storage_id;

        $r = $db->prepare('select to_regclass(?)');
        $r->execute([$table]);
        if ($r->fetchColumn()) {
            return $table;
        }

        return null;
    }
}
