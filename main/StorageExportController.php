<?php

declare(strict_types=1);

namespace Main;

use Main\BaseController;
use Main\Storage;

class StorageExportController extends BaseController
{
    public function index($storage_uid = null, $folder_id = null)
    {
        $this->current_user = $this->auth->isLogged() ? $this->auth->getCurrentUser() : null;

        if (! $this->current_user) {
            http_response_code(401);
            return;
        }

        $this->Storage = new Storage($this->db);
        $storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);

        $storage = $this->Storage->getStorage(null, $storage_uid, $this->current_user['id']);
        $file_table = $storage ? $this->Storage->getFileTable($storage['id']) : null;

        if (! $storage || ! $file_table) {
            http_response_code(404);
            return;
        }

        $folder = null;

        if ((int) $folder_id) {
            $r = $this->db->prepare("SELECT * FROM $file_table WHERE id = ? AND folder IS TRUE");
            $r->execute([(int) $folder_id]);
            $folder = $r->fetch(\PDO::FETCH_ASSOC);

            if (! $folder) {
                http_response_code(400);
                return;
            }
        }

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
            $ids = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);

            if (! $ids) {
                http_response_code(400);
                return;
            }

            if ($folder && $ids) {
                $r = $this->db->prepare(
                    'SELECT
    file.id, file.crc, file.file, file.name, file.parent_id, file.size, path.path
' . (!empty($storage['attributes']) ? array_reduce($storage['attributes'], function ($r, $v) {
                        $r .= ',file.a' . $v['id'];
                        return $r;
                    }, '') : '') . "
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
                    'SELECT
    file.id, file.crc, file.file, file.name, file.parent_id, file.size, folder.path
' . (!empty($storage['attributes']) ? array_reduce($storage['attributes'], function ($r, $v) {
                        $r .= ',file.a' . $v['id'];
                        return $r;
                    }, '') : '') . "
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
                    'SELECT
    file.id, file.crc, file.file, file.name, file.parent_id, file.size, path.path
' . (!empty($storage['attributes']) ? array_reduce($storage['attributes'], function ($r, $v) {
                        $r .= ',file.a' . $v['id'];
                        return $r;
                    }, '') : '') . "
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
                    'SELECT
    file.id, file.crc, file.file, file.name, file.parent_id, file.size, folder.path
' . (!empty($storage['attributes']) ? array_reduce($storage['attributes'], function ($r, $v) {
                        $r .= ',file.a' . $v['id'];
                        return $r;
                    }, '') : '') . "
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

        $attributes_column_strings = [];

        if (!empty($storage['attributes'])) {
            foreach ($storage['attributes'] as $i => $v) {
                $attributes_column_strings[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
            }
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()->setTitle($storage['title'] . ($folder ? ' ' . $folder['name'] : ''));

        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValueExplicit('A1', __('Filename'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

        if (!empty($storage['attributes'])) {
            foreach ($storage['attributes'] as $i => $v) {
                if (!empty($attributes_column_strings[$i])) {
                    $spreadsheet->getActiveSheet()->setCellValueExplicit($attributes_column_strings[$i] . '1', $v['title'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
        }

        $i = 2;

        while ($row = $r->fetch(\PDO::FETCH_ASSOC)) {
            $spreadsheet->getActiveSheet()->setCellValueExplicit('A' . $i, ($row['path'] ? $row['path'] . '/' : '') . $row['name'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

            foreach ($storage['attributes'] as $j => $attribute) {
                switch ($attribute['type_name'] ?? '') {
                    case 'date':
                        $spreadsheet->getActiveSheet()->setCellValue($attributes_column_strings[$j] . $i, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($row['a' . $attribute['id']] ?? '') ?: '');
                        $spreadsheet->getActiveSheet()->getStyle($attributes_column_strings[$j] . $i)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DMYSLASH);
                        break;

                    case 'datetime':
                        $spreadsheet->getActiveSheet()->setCellValue($attributes_column_strings[$j] . $i, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(DateTime::createFromFormat('Y-m-d H:i:sP', $row['a' . $attribute['id']] ?? '')) ?: '');
                        $spreadsheet->getActiveSheet()->getStyle($attributes_column_strings[$j] . $i)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DATETIME);
                        break;

                    default:
                        $spreadsheet->getActiveSheet()->setCellValueExplicit($attributes_column_strings[$j] . $i, $row['a' . $attribute['id']] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                        break;
                }
            }

            $i++;
        }

        $filename = ($storage['title'] . ($folder ? '_' . $folder['name'] : '') . '.xlsx');

        $to_underscore = '"\\#*;:|<>/?';
        $safe_filename = strtr($filename, $to_underscore, str_repeat('_', strlen($to_underscore)));

        if (strpos(($_SERVER['HTTP_USER_AGENT'] ?? ''), 'MSIE') !== false || strpos(($_SERVER['HTTP_USER_AGENT'] ?? ''), 'Trident') !== false) {
            header('Content-Disposition: attachment; filename="' . rawurlencode($safe_filename) . '"');
        } else {
            header("Content-Disposition: attachment; filename=\"$safe_filename\"" . ($safe_filename === $filename ? '' : "; filename*=UTF-8''" . rawurlencode($filename)));
        }

        header('Cache-Control: cache, must-revalidate');
        header('Cache-Control: max-age=0');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Pragma: public');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
    }
}
