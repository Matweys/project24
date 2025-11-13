<?php

declare(strict_types=1);

namespace Main;

use Helpers\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Yaml\Yaml;

class StorageImportAttributesException extends \Exception
{
}

class StorageImportAttributes
{
    protected $db;

    public function __construct(\PDO $db)
    {
        $this->db = &$db;
    }

    public static function deleteTempFiles($fields)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        foreach ($fields as $v) {
            if (!empty($_FILES[$v]['tmp_name']) && is_string($_FILES[$v]['tmp_name']) && is_file($_FILES[$v]['tmp_name'])) {
                try {
                    unlink($_FILES[$v]['tmp_name']);
                } catch (\Exception $e) {
                }
            }
        }
    }

    public function importAttributes(array $columns_attributes, string $delimiter, string $file_table, string $filename, array $storage, bool $basename_match = false, string $enclosure = '"', ?int $folder_id = null, string $source = '', bool $storage_log = false, ?array $user = null)
    {
        if (is_file($filename)) {
            $mime_type = (new \finfo(FILEINFO_MIME_TYPE))->file($filename);

            if ($mime_type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                try {
                    $reader = IOFactory::createReader(IOFactory::identify($filename));
                    $reader->setLoadAllSheets();
                    $spreadsheet = $reader->load($filename);
                    $worksheet = $spreadsheet->getActiveSheet();

                    $affected_file_count = 0;
                    $errors = 0;
                    $row_count = 0;

                    $attributes_map = array_combine(array_column($storage['attributes'] ?? [], 'id'), $storage['attributes'] ?? []);

                    foreach ($worksheet->getRowIterator() as $row) {
                        $row_count++;
                        $cellIterator = $row->getCellIterator();

                        $row_data = [];

                        foreach ($cellIterator as $cell) {
                            $row_data[] = $cell->getValue();
                        }

                        $file = null;
                        $attributes_to_modify = [];

                        foreach ($columns_attributes as $i => $attribute) {
                            if ($attribute === 'f') {
                                $file = $basename_match ? basename($row_data[$i] ?? '') : $row_data[$i] ?? '';
                            } elseif ((int) $attribute && isset($attributes_map[(int) $attribute]) && isset($row_data[$i])) {
                                $attributes_to_modify[(int) $attribute] = $row_data[$i];
                            }
                        }

                        if ($file) {
                            $r = $this->db->prepare("select id from {$file_table} where name = ?" . ($folder_id ? ' and parent_id = ?' : ' and parent_id is null') . ' and folder is null');
                            $r->execute($folder_id ? [$file, $folder_id] : [$file]);
                            $file_id = $r->fetchColumn();

                            if ($file_id) {
                                $r = $this->db->prepare(
                                    "update {$file_table}
set modified = now()" . array_reduce(
                                        $storage['attributes'] ?? [],
                                        function ($r, $v) {
                                            $r .= sprintf(',a%1$d = :a%1$d', $v['id']);
                                            return $r;
                                        },
                                        ''
                                    ) . ' where id = :id and folder is not true'
                                );

                                $r->execute(array_reduce(
                                    $storage['attributes'] ?? [],
                                    function ($r, $attribute) use ($attributes_to_modify, &$errors) {
                                        $k = ':a' . $attribute['id'];
                                        switch ($attribute['type_name'] ?? '') {
                                            case 'date':
                                                $v = isset($attributes_to_modify[$attribute['id']]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $attributes_to_modify[$attribute['id']]) : null;
                                                $r[$k] = $v instanceof \DateTime ? $v->format('Y-m-d') : null;
                                                if (!empty($attributes_to_modify[$attribute['id']]) && !$r[$k]) {
                                                    $errors++;
                                                }
                                                break;
                                            case 'datetime':
                                                $v = isset($attributes_to_modify[$attribute['id']]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $attributes_to_modify[$attribute['id']]) : null;
                                                $r[$k] = $v instanceof \DateTime ? $v->format('Y-m-d H:i:s') : null;
                                                if (!empty($attributes_to_modify[$attribute['id']]) && !$r[$k]) {
                                                    $errors++;
                                                }
                                                break;
                                            case 'float':
                                                $v = filter_var($attributes_to_modify[$attribute['id']] ?? null, FILTER_VALIDATE_FLOAT);
                                                $r[$k] = $v !== false ? $v : null;
                                                if (!empty($attributes_to_modify[$k]) && $v === false) {
                                                    $errors++;
                                                }
                                                break;
                                            case 'integer':
                                                $v = filter_var($attributes_to_modify[$attribute['id']] ?? null, FILTER_VALIDATE_INT);
                                                $r[$k] = $v !== false ? $v : null;
                                                if (!empty($attributes_to_modify[$k]) && $v === false) {
                                                    $errors++;
                                                }
                                                break;
                                            default:
                                                $r[$k] = $attributes_to_modify[$attribute['id']] ?? null;
                                                break;
                                        }

                                        return $r;
                                    },
                                    [
                                        ':id' => (int) $file_id,
                                    ]
                                ));

                                if ($storage_log) {
                                    Log::message(
                                        db: $this->db,
                                        message: Yaml::dump([
                                            'payload' => array_map(
                                                function ($attribute) use ($attributes_to_modify, $storage) {
                                                    switch ($attribute['type_name'] ?? '') {
                                                        case 'date':
                                                            $v = isset($attributes_to_modify[$attribute['id']]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $attributes_to_modify[$attribute['id']]) : null;
                                                            $v = $v instanceof \DateTime ? $v->format('Y-m-d') : null;
                                                            break;
                                                        case 'datetime':
                                                            $v = isset($attributes_to_modify[$attribute['id']]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $attributes_to_modify[$attribute['id']]) : null;
                                                            $v = $v instanceof \DateTime ? $v->format('Y-m-d H:i:s') : null;
                                                            break;
                                                        case 'float':
                                                            $v = filter_var($attributes_to_modify[$attribute['id']] ?? null, FILTER_VALIDATE_FLOAT);
                                                            $v = false !== $v ? $v : null;
                                                            break;
                                                        case 'integer':
                                                            $v = filter_var($attributes_to_modify[$attribute['id']] ?? null, FILTER_VALIDATE_INT);
                                                            $v = false !== $v ? $v : null;
                                                            break;
                                                        default:
                                                            $v = $attributes_to_modify[$attribute['id']] ?? null;
                                                            break;
                                                    }

                                                    return [
                                                        'id' => $attribute['id'] ?? null,
                                                        'storage_id' => $storage['id'],
                                                        'title' => $attribute['title'] ?? $k,
                                                        'type' => $attribute['type'] ?? null,
                                                        'value' => $v,
                                                    ];
                                                },
                                                $storage['attributes'] ?? []
                                            ),
                                            'source' => $source,
                                            'storage' => [
                                                'id' => $storage['id'],
                                            ],
                                            'type' => 'change_file_attributes',
                                            'user' => [
                                                'id' => $user['id'] ?? null,
                                                'email' => $user['email'] ?? null,
                                            ],
                                        ]),
                                        storage: $storage['id'] ?? null,
                                    );
                                }

                                $affected_file_count += $r->rowCount();
                            }
                        }
                    }

                    return [$row_count, $affected_file_count, $errors];
                } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
                    throw new StorageImportAttributesException($e->getMessage(), $e->getCode(), $e);
                }
            } elseif ($mime_type === 'text/plain') {
                $f = fopen($filename, 'r');

                if ($f !== false) {
                    // Skip BOM
                    rewind($f);
                    if (fgets($f, 4) == "\xEF\xBB\xBF") {
                        fseek($f, 3);
                    } else {
                        fseek($f, 0);
                    }

                    // Detect separator that is explicitly set in the file
                    $line = fgets($f);
                    if ($line !== false && strlen(trim($line, "\r\n")) == 5 && stripos($line, 'sep=') === 0) {
                        $delimiter = substr($line, 4, 1);
                    }

                    rewind($f);
                    if (fgets($f, 4) == "\xEF\xBB\xBF") {
                        fseek($f, 3);
                    } else {
                        fseek($f, 0);
                    }

                    $escapeEnclosures = ['\\' . $enclosure, $enclosure . $enclosure];

                    $affected_file_count = 0;
                    $errors = 0;
                    $row_count = 0;

                    $attributes_map = array_combine(array_column($storage['attributes'] ?? [], 'id'), $storage['attributes'] ?? []);

                    while (($row_data = fgetcsv($f, 10000, $delimiter, $enclosure)) !== false) {
                        $row_count++;

                        $file = null;
                        $attributes_to_modify = [];

                        foreach ($columns_attributes as $i => $attribute) {
                            if ($attribute === 'f') {
                                $file = $basename_match ? basename($row_data[$i] ?? '') : $row_data[$i] ?? '';
                            } elseif ((int) $attribute && isset($attributes_map[(int) $attribute]) && isset($row_data[$i])) {
                                $attributes_to_modify[(int) $attribute] = $row_data[$i];
                            }
                        }

                        if ($file) {
                            $r = $this->db->prepare("select id from {$file_table} where name = ?" . ($folder_id ? ' and parent_id = ?' : ' and parent_id is null') . ' and folder is null');
                            $r->execute($folder_id ? [$file, $folder_id] : [$file]);
                            $file_id = $r->fetchColumn();

                            if ($file_id) {
                                $r = $this->db->prepare(
                                    "update {$file_table}
    set modified = now()" . array_reduce(
                                        $storage['attributes'] ?? [],
                                        function ($r, $v) {
                                            $r .= sprintf(',a%1$d = :a%1$d', $v['id']);
                                            return $r;
                                        },
                                        ''
                                    ) . ' where id = :id and folder is not true'
                                );

                                $r->execute(array_reduce(
                                    $storage['attributes'] ?? [],
                                    function ($r, $attribute) use ($attributes_to_modify, &$errors) {
                                        $k = ':a' . $attribute['id'];
                                        switch ($attribute['type_name'] ?? '') {
                                            case 'date':
                                                $v = !empty($attributes_to_modify[$attribute['id']]) ? static::parse_date($attributes_to_modify[$attribute['id']]) : null;
                                                $r[$k] = $v instanceof \DateTime ? $v->format('Y-m-d') : null;
                                                if (!empty($attributes_to_modify[$attribute['id']]) && !$r[$k]) {
                                                    $errors++;
                                                }
                                                break;
                                            case 'datetime':
                                                $v = !empty($attributes_to_modify[$attribute['id']]) ? static::parse_datetime($attributes_to_modify[$attribute['id']]) : null;
                                                $r[$k] = $v instanceof \DateTime ? $v->format('Y-m-d H:i:s') : null;
                                                if (!empty($attributes_to_modify[$attribute['id']]) && !$r[$k]) {
                                                    $errors++;
                                                }
                                                break;
                                            case 'float':
                                                $v = filter_var($attributes_to_modify[$attribute['id']] ?? null, FILTER_VALIDATE_FLOAT);
                                                $r[$k] = $v !== false ? $v : null;
                                                if (!empty($attributes_to_modify[$k]) && $v === false) {
                                                    $errors++;
                                                }
                                                break;
                                            case 'integer':
                                                $v = filter_var($attributes_to_modify[$attribute['id']] ?? null, FILTER_VALIDATE_INT);
                                                $r[$k] = $v !== false ? $v : null;
                                                if (!empty($attributes_to_modify[$k]) && $v === false) {
                                                    $errors++;
                                                }
                                                break;
                                            default:
                                                $r[$k] = $attributes_to_modify[$attribute['id']] ?? null;
                                                break;
                                        }

                                        return $r;
                                    },
                                    [
                                        ':id' => (int) $file_id,
                                    ]
                                ));

                                if ($storage_log) {
                                    Log::message(
                                        db: $this->db,
                                        message: Yaml::dump([
                                            'payload' => array_map(
                                                function ($attribute) use ($attributes_to_modify, $storage) {
                                                    switch ($attribute['type_name'] ?? '') {
                                                        case 'date':
                                                            $v = !empty($attributes_to_modify[$attribute['id']]) ? static::parse_date($attributes_to_modify[$attribute['id']]) : null;
                                                            $v = $v instanceof \DateTime ? $v->format('Y-m-d') : null;
                                                            break;
                                                        case 'datetime':
                                                            $v = !empty($attributes_to_modify[$attribute['id']]) ? static::parse_datetime($attributes_to_modify[$attribute['id']]) : null;
                                                            $v = $v instanceof \DateTime ? $v->format('Y-m-d H:i:s') : null;
                                                            break;
                                                        case 'float':
                                                            $v = filter_var($attributes_to_modify[$attribute['id']] ?? null, FILTER_VALIDATE_FLOAT);
                                                            $v = false !== $v ? $v : null;
                                                            break;
                                                        case 'integer':
                                                            $v = filter_var($attributes_to_modify[$attribute['id']] ?? null, FILTER_VALIDATE_INT);
                                                            $v = false !== $v ? $v : null;
                                                            break;
                                                        default:
                                                            $v = $attributes_to_modify[$attribute['id']] ?? null;
                                                            break;
                                                    }

                                                    return [
                                                        'id' => $attribute['id'] ?? null,
                                                        'storage_id' => $storage['id'],
                                                        'title' => $attribute['title'] ?? $k,
                                                        'type' => $attribute['type'] ?? null,
                                                        'value' => $v,
                                                    ];
                                                },
                                                $storage['attributes'] ?? []
                                            ),
                                            'source' => $source,
                                            'storage' => [
                                                'id' => $storage['id'],
                                            ],
                                            'type' => 'change_file_attributes',
                                            'user' => [
                                                'id' => $user['id'] ?? null,
                                                'email' => $user['email'] ?? null,
                                            ],
                                        ]),
                                        storage: $storage['id'] ?? null,
                                    );
                                }

                                $affected_file_count += $r->rowCount();
                            }
                        }
                    }

                    fclose($f);

                    return [$row_count, $affected_file_count, $errors];
                }
            }
        }
    }

    public function loadImportData(string $filename, string $delimiter, string $enclosure = '"', ?int $number_of_lines = null)
    {
        if (is_file($filename)) {
            $mime_type = (new \finfo(FILEINFO_MIME_TYPE))->file($filename);

            if ($mime_type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                $reader = IOFactory::createReader(IOFactory::identify($filename));
                $reader->setLoadAllSheets();
                $spreadsheet = $reader->load($filename);
                $worksheet = $spreadsheet->getActiveSheet();

                $data = [];
                $i = 0;

                foreach ($worksheet->getRowIterator() as $row) {
                    if (isset($number_of_lines) && $i >= $number_of_lines) {
                        break;
                    }

                    $cellIterator = $row->getCellIterator();

                    $cell_data = [];

                    foreach ($cellIterator as $cell) {
                        $cell_data[] = $cell->getFormattedValue();
                    }

                    $data[] = $cell_data;

                    $i++;
                }

                return $data;
            }

            if ($mime_type === 'text/plain') {
                $f = fopen($filename, 'r');

                if ($f !== false) {
                    // Skip BOM
                    rewind($f);
                    if (fgets($f, 4) == "\xEF\xBB\xBF") {
                        fseek($f, 3);
                    } else {
                        fseek($f, 0);
                    }

                    // Detect separator that is explicitly set in the file
                    $line = fgets($f);
                    if ($line !== false && strlen(trim($line, "\r\n")) == 5 && stripos($line, 'sep=') === 0) {
                        $delimiter = substr($line, 4, 1);
                    }

                    rewind($f);
                    if (fgets($f, 4) == "\xEF\xBB\xBF") {
                        fseek($f, 3);
                    } else {
                        fseek($f, 0);
                    }

                    $escapeEnclosures = ['\\' . $enclosure, $enclosure . $enclosure];

                    $data = [];
                    $i = 0;

                    while (($row = fgetcsv($f, 10000, $delimiter, $enclosure)) !== false && $i <= $number_of_lines) {
                        $data[] = $row;
                        $i++;
                    }

                    fclose($f);

                    return $data;
                }
            }
        }
    }

    public static function upload(string $name)
    {
        $error = null;
        $filename = null;

        if (!empty($_FILES[$name]['tmp_name']) && is_string($_FILES[$name]['tmp_name'])) {
            if ($_FILES[$name]['error'] !== UPLOAD_ERR_OK) {
                $error = __('File upload error.');
            } else {
                $mime_type = (new \finfo(FILEINFO_MIME_TYPE))->file($_FILES[$name]['tmp_name']);

                if ($mime_type !== 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' && $mime_type !== 'text/plain') {
                    $error = __('Wrong file type. Allowed only CSV and XLSX files.');
                } else {
                    $filename = tempnam(sys_get_temp_dir(), '');

                    if (!move_uploaded_file($_FILES[$name]['tmp_name'], $filename)) {
                        $error = __('File upload error.');
                    } else {
                        return [null, $filename, $mime_type, $_FILES[$name]['name'] ?? ''];
                    }
                }
            }

            if ($error) {
                foreach ([$_FILES[$name]['tmp_name'], $filename] as $v) {
                    if (is_file($v)) {
                        try {
                            unlink($v);
                        } catch (\Exception $e) {
                        }
                    }
                }
            }
        }

        return [$error, null, null, null];
    }

    protected static function parse_date($dt)
    {
        if ($dt && is_string($dt)) {
            foreach ([
                'Y-m-d',
                'Y-m-j',
                'd-m-Y',
                'd.m.Y',
                'd/m/Y',
                'j-m-Y',
                'j.m.Y',
                'j/m/Y',
            ] as $v) {
                $rv = date_create_from_format($v, $dt);
                $e = date_get_last_errors();
                if ($rv && empty($e['error_count']) && empty($e['warning_count'])) {
                    return $rv->setTime(0, 0);
                }
            }
        }
    }

    protected static function parse_datetime($dt)
    {
        if ($dt && is_string($dt)) {
            foreach ([
                DATE_ATOM,
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
