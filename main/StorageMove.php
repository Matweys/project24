<?php

declare(strict_types=1);

namespace Main;

class StorageMove
{
    public static function moveFileAndFolders(\PDO $db, int $storage_id, array $file_ids, ?int $destination_folder_id): ?array
    {
        $file_ids = array_filter(filter_var($file_ids, FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY));

        $r = $db->prepare('select * from storage where id = ?');
        $r->execute([$storage_id]);
        $storage = $r->fetch(\PDO::FETCH_ASSOC);

        if ($storage && $file_ids) {
            $file_table = StorageUtil::getFileTable($db, $storage_id);

            if ($file_table) {
                // Check if files with the same name exist in the destination folder.

                $_destination_folder_id = null;

                if ($destination_folder_id) {
                    $r = $db->prepare("select id from {$file_table} where id = ? and folder is true");
                    $r->execute([$destination_folder_id]);
                    $_destination_folder_id = $r->fetchColumn();
                }

                if ($_destination_folder_id) {
                    $r = $db->prepare("select source.id,source.name from {$file_table} source join {$file_table} destination on destination.parent_id = ? where source.id in (" . str_repeat('?,', count($file_ids) - 1) . '?) and source.name = destination.name');
                    $r->execute(array_merge([$destination_folder_id], $file_ids));
                } else {
                    $r = $db->prepare("select source.id,source.name from {$file_table} source join {$file_table} destination on destination.parent_id is null where source.id in (" . str_repeat('?,', count($file_ids) - 1) . '?) and source.name = destination.name');
                    $r->execute($file_ids);
                }

                $file_conflicts = $r->fetchAll(\PDO::FETCH_ASSOC);

                if ($file_conflicts) {
                    return $file_conflicts;
                }

                $r = $db->prepare("select id from {$file_table} where id in (" . str_repeat('?,', count($file_ids) - 1) . '?) and folder is true');
                $r->execute($file_ids);

                while ($row = $r->fetch(\PDO::FETCH_ASSOC)) {
                    $sfm = $db->prepare('call storage_folder_move_to(:storage_id, :folder_id, :destination_folder_id)');
                    $sfm->execute([
                        ':folder_id' => $row['id'],
                        ':storage_id' => $storage['id'],
                        ':destination_folder_id' => $destination_folder_id,
                    ]);
                }

                $r = $db->prepare("update {$file_table} set parent_id = ? where id in (" . str_repeat('?,', count($file_ids) - 1) . '?) and folder is not true');
                $r->execute(array_merge([$destination_folder_id], $file_ids));
            }
        }

        return null;
    }
}
