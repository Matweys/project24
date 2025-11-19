<?php

declare(strict_types=1);

namespace Main;

use Main\BaseController;
use Main\Storage;

class StorageDownloadController extends BaseController
{
    protected $storage;

    public function index($storage_uid = null, $folder_id = null)
    {
        $this->current_user = $this->auth->isLogged() ? $this->auth->getCurrentUser() : null;

        if (!$this->current_user) {
            http_response_code(401);
            return;
        }

        $this->Storage = new Storage($this->db);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $storage = $this->Storage->getStorage(null, $storage_uid, $this->current_user['id']);
        $file_table = $storage ? $this->Storage->getFileTable($storage['id']) : null;

        if (!$storage || !$file_table) {
            http_response_code(404);
            return;
        }

        $folder = null;

        if ((int) $folder_id) {
            $r = $this->db->prepare("SELECT * FROM $file_table WHERE id = ? AND folder IS TRUE");
            $r->execute([(int) $folder_id]);
            $folder = $r->fetch(\PDO::FETCH_ASSOC);

            if (!$folder) {
                http_response_code(400);
                return;
            }
        }

        $upload_config = array_merge($this->config['upload'], [
            'nginx_zip_location' => rtrim($this->config['upload']['nginx_zip_location'], '/') . '/' . $storage['uid'] . '/',
            'path' => rtrim($this->config['upload']['path'], '/') . '/' . $storage['uid'],
            'url' => rtrim($this->config['upload']['url'], '/') . '/' . $storage['uid'] . '/',
        ]);

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
            $ids = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);

            if (!$ids) {
                http_response_code(400);
                return;
            }

            if ($folder && $ids) {
                $r = $this->db->prepare(
                    "SELECT
    file.id, file.crc, file.file, file.name, file.parent_id, file.size, path.path
FROM
    $file_table file
JOIN $file_table src ON src.id IN (" . implode(',', array_map(function ($k) {
                        return ':id' . $k;
                    }, array_keys($ids))) . ")
LEFT JOIN $file_table folder on folder.id = file.parent_id
LEFT JOIN (
    SELECT
        file.id, string_agg(parent.name, '/' order by parent.lft) path
    FROM
        $file_table file, $file_table parent
    JOIN $file_table src ON src.id = :folder_id
    WHERE
        file.folder IS TRUE
        AND file.lft BETWEEN parent.lft AND parent.rgt
        AND parent.lft > src.lft
        AND parent.lft < src.rgt
    GROUP BY file.id
) path on path.id = file.parent_id
WHERE
    (folder.lft between src.lft AND src.rgt
        OR file.parent_id = src.id
        OR file.id = src.id)
    AND file.folder IS NOT TRUE
ORDER BY folder.lft, file.name"
                );

                $r->bindValue(':folder_id', $folder['id'], \PDO::PARAM_INT);

                foreach ($ids as $i => $v) {
                    $r->bindValue(':id' . $i, $v, \PDO::PARAM_INT);
                }

                $r->execute();
            } elseif ($ids) {
                $r = $this->db->prepare(
                    "SELECT
    file.id, file.crc, file.file, file.name, file.parent_id, file.size, folder.path
FROM
    $file_table file
JOIN $file_table src ON src.id IN (" . implode(',', array_map(function ($k) {
                        return ':id' . $k;
                    }, array_keys($ids))) . ")
LEFT JOIN (
    SELECT
        file.id, file.lft, string_agg(parent.name, '/' order by parent.lft) path
    FROM
        $file_table file, $file_table parent
    WHERE
        file.folder IS TRUE
        AND file.lft BETWEEN parent.lft AND parent.rgt
    GROUP BY file.id
) folder on folder.id = file.parent_id
WHERE
    (folder.lft between src.lft AND src.rgt
        OR file.parent_id = src.id
        OR file.id = src.id)
    AND file.folder IS NOT TRUE
ORDER BY folder.lft, file.name"
                );

                foreach ($ids as $i => $v) {
                    $r->bindValue(':id' . $i, $v, \PDO::PARAM_INT);
                }

                $r->execute();
            }
        } else {
            if ($folder) {
                $r = $this->db->prepare(
                    "SELECT
    file.id, file.crc, file.file, file.name, file.parent_id, file.size, path.path
FROM
    $file_table file
JOIN $file_table src ON src.id = :folder_id
LEFT JOIN $file_table folder on folder.id = file.parent_id
LEFT JOIN (
    SELECT
        file.id, string_agg(parent.name, '/' order by parent.lft) path
    FROM
        $file_table file, $file_table parent
    JOIN $file_table src ON src.id = :folder_id
    WHERE
        file.folder IS TRUE
        AND file.lft BETWEEN parent.lft AND parent.rgt
        AND parent.lft > src.lft
        AND parent.lft < src.rgt
    GROUP BY file.id
) path on path.id = file.parent_id
WHERE
    folder.lft between src.lft AND src.rgt
    AND file.folder IS NOT TRUE
ORDER BY folder.lft, file.name"
                );

                $r->execute([':folder_id' => $folder['id']]);
            } else {
                $r = $this->db->prepare(
                    "SELECT
    file.id, file.crc, file.file, file.name, file.parent_id, file.size, folder.path
FROM
    $file_table file
LEFT JOIN (
    SELECT
        file.id, file.lft, string_agg(parent.name, '/' order by parent.lft) path
    FROM
        $file_table file, $file_table parent
    WHERE
        file.folder IS TRUE
        AND file.lft BETWEEN parent.lft AND parent.rgt
    GROUP BY file.id
) folder on folder.id = file.parent_id
WHERE
    file.folder IS NOT TRUE
ORDER BY folder.lft, file.name"
                );

                $r->execute();
            }
        }

        $filename = ($storage['title'] . ($folder ? '_' . $folder['name'] : '') . '.zip');

        $to_underscore = "\"\\#*;:|<>/?";
        $safe_filename = strtr($filename, $to_underscore, str_repeat('_', strlen($to_underscore)));

        if (strpos(($_SERVER['HTTP_USER_AGENT'] ?? ''), 'MSIE') !== false || strpos(($_SERVER['HTTP_USER_AGENT'] ?? ''), 'Trident') !== false) {
            header('Content-Disposition: attachment; filename="' . rawurlencode($safe_filename) . '"');
        } else {
            header("Content-Disposition: attachment; filename=\"$safe_filename\"" . ($safe_filename === $filename ? '' : "; filename*=UTF-8''" . rawurlencode($filename)));
        }

        header('Content-Type: application/zip');
        header('Cache-Control: cache, must-revalidate');
        header('Cache-Control: max-age=0');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header('Pragma: public');

        // Собираем все файлы в массив
        $files = [];
        while ($row = $r->fetch(\PDO::FETCH_ASSOC)) {
            $files[] = $row;
        }

        // Проверяем, доступен ли ZipArchive
        if (!class_exists('ZipArchive')) {
            http_response_code(500);
            echo 'ZIP extension is not available. Please install php-zip extension.';
            error_log('ZIP extension is not available');
            return;
        }

        // Используем PHP ZipArchive для создания ZIP (более надежно, чем nginx mod_zip)
        // Если нужно использовать nginx mod_zip, установите в конфиге 'use_nginx_mod_zip' => true
        $use_nginx_mod_zip = !empty($this->config['upload']['use_nginx_mod_zip']) && empty($this->config['debug']);

        if ($use_nginx_mod_zip) {
            // Используем nginx mod_zip (требует установки и настройки модуля)
            header('X-Archive-Files: zip');
            foreach ($files as $row) {
                echo($row['crc'] ? dechex($row['crc']) : '-') . " ". $row['size'] . " " . $upload_config['nginx_zip_location'] . $row['file'] . " " . ($row['path'] ? $row['path'] . '/' : '') . $row['name'] . "\n";
            }
        } else {
            // Используем PHP ZipArchive для создания ZIP
            $tmp_zip = tempnam(sys_get_temp_dir(), 'zip_');
            $zip = new \ZipArchive();
            
            if ($zip->open($tmp_zip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                http_response_code(500);
                echo 'Failed to create ZIP archive';
                error_log('Failed to create ZIP archive');
                return;
            }

            // Определяем абсолютный путь к файлам
            $base_path = $upload_config['path'];
            if (strpos($base_path, '/') !== 0) {
                // Относительный путь - преобразуем в абсолютный
                if (!empty($_SERVER['DOCUMENT_ROOT'])) {
                    $base_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $base_path;
                } else {
                    // Fallback: используем корень проекта
                    $project_root = dirname(__DIR__);
                    $base_path = $project_root . '/public/' . $base_path;
                }
            }

            foreach ($files as $row) {
                $file_path = rtrim($base_path, '/') . '/' . $row['file'];
                $archive_path = ($row['path'] ? $row['path'] . '/' : '') . $row['name'];
                
                if (file_exists($file_path) && is_readable($file_path)) {
                    $zip->addFile($file_path, $archive_path);
                } else {
                    error_log("File not found or not readable: {$file_path} (archive path: {$archive_path})");
                }
            }

            $zip->close();

            // Отправляем ZIP файл
            header('Content-Length: ' . filesize($tmp_zip));
            readfile($tmp_zip);
            unlink($tmp_zip);
        }
    }
}
