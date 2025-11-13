<?php

declare(strict_types=1);

namespace Main;

class StorageMoveController extends BaseController
{
    public function index($storage_uid = null)
    {
        if (($_SERVER['REQUEST_METHOD'] ?? null) !== 'POST') {
            http_response_code(405);
            exit;
        }

        $current_user = $this->auth->isLogged() ? $this->auth->getCurrentUser() : null;

        if (!$current_user) {
            http_response_code(401);
            exit;
        }

        $Storage = new Storage($this->db);
        $storages = $Storage->getAllowedStorages($current_user['id']);

        $lang = $this->language->getCurrentLanguage($current_user['id']);
        $this->load_translation($lang['name'] ?? null);

        $storage = $Storage->getStorage(null, $storage_uid, $current_user['id']);
        $file_table = $storage ? StorageUtil::getFileTable($this->db, $storage['id']) : null;

        if (!$storage || !$file_table || !in_array($storage['permission_name'] ?? null, ['edit', 'full'])) {
            http_response_code(401);
            echo __('No permission to move files.');
            exit;
        }

        $destination_folder_id = ((int) ($_POST['folder_id'] ?? 0)) ?: null;
        $file_ids = filter_input(INPUT_POST, 'ids', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);

        if ($file_ids) {
            $this->db->beginTransaction();
            $file_conflicts = StorageMove::moveFileAndFolders($this->db, $storage['id'], $file_ids, $destination_folder_id);
            $this->db->commit();

            if ($file_conflicts) {
                echo sprintf(__('The file or folder %s already exists in destination folder.'), $file_conflicts[0]['name']);
                http_response_code(409);
                exit;
            }

            $r = $this->db->prepare("select id,folder,name from {$file_table} where id in (" . str_repeat('?,', count($file_ids) - 1) . '?)');
            $r->execute($file_ids);
            $files_to_move = $r->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($this->config['storage_log']) && $files_to_move) {
                $destination_folder = null;

                if ($destination_folder_id) {
                    $r = $this->db->prepare("select id, name from {$file_table} where id = ? and folder is true");
                    $r->execute([$destination_folder_id]);
                    $destination_folder = $r->fetch(\PDO::FETCH_ASSOC);
                }

                StorageLog::move_file(
                    db: $this->db,
                    destination_folder: $destination_folder,
                    files_to_move: $files_to_move,
                    storage: $storage,
                    user: $current_user,
                );
            }

            try {
                (new StorageSearch($this->db, new Cache($this->db, $this->config)))->rebuild_index($storage['id']);
            } catch (\PDOException $e) {
                error_log((string) $e);
            }

            flash(sprintf(_n('File has been moved.', '%d files have been moved.', count($files_to_move)), count($files_to_move)));
        }

        http_response_code(204);
    }
}
