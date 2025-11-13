<?php

declare(strict_types=1);

namespace Main;

class StorageDeleteController extends BaseController
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
            echo __('No permission to delete files.');
            exit;
        }

        $ids = filter_input(INPUT_POST, 'ids', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);

        if ($ids) {
            $affected_files = null;

            $this->db->beginTransaction();
            $affected_files = $Storage->deleteFileAndFolders($this->config['upload'], $storage['id'], $ids, (int) ($this->config['max_files_to_delete'] ?? 0));
            $this->db->commit();

            if ($affected_files) {
                if (!empty($this->config['storage_log'])) {
                    StorageLog::delete_file(
                        db: $this->db,
                        files: $affected_files,
                        storage: $storage,
                        user: $current_user,
                    );
                }

                try {
                    (new StorageSearch($this->db, new Cache($this->db, $this->config)))->rebuild_index($storage['id']);
                } catch (\PDOException $e) {
                    error_log((string) $e);
                }

                flash(sprintf(_n('File has been deleted.', '%d files have been deleted.', count($affected_files)), count($affected_files)));
            }
        }

        http_response_code(204);
    }
}
