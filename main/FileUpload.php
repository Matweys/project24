<?php

declare(strict_types=1);

namespace Main;

use enshrined\svgSanitize\Sanitizer;

class FileUpload
{
    public static $image_file_types = [
        IMAGETYPE_GIF => '.gif',
        IMAGETYPE_JPEG => '.jpg',
        IMAGETYPE_JPEG2000 => '.jpg',
        IMAGETYPE_PNG => '.png',
        IMAGETYPE_PSD => '.psd',
        IMAGETYPE_TIFF_II => '.tiff',
        IMAGETYPE_TIFF_II => '.tiff',
        IMAGETYPE_WEBP => '.webp',
    ];

    protected static function catchWarning($errno, $errstr, $errfile, $errline)
    {
        throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
    }

    public static function deleteFiles(?array &$config, $files)
    {
        if (!is_array($files)) {
            $files = [$files];
        }

        set_error_handler('static::catchWarning');

        foreach ($files as $v) {
            if (is_string($v) && $v) {
                $f = rtrim($config['path'], '/') . '/' . $v;
                if (is_file($f)) {
                    try {
                        unlink($f);
                    } catch (\Exception $e) {
                        error_log((string) $e);
                    }
                }
            }
        }

        restore_error_handler();
    }

    public static function deleteTempFiles($fields)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        set_error_handler('static::catchWarning');

        foreach ($fields as $v) {
            if (!empty($_FILES[$v]['tmp_name']) && is_string($_FILES[$v]['tmp_name']) && is_file($_FILES[$v]['tmp_name'])) {
                try {
                    unlink($_FILES[$v]['tmp_name']);
                } catch (\Exception $e) {
                    error_log((string) $e);
                }
            }
        }

        restore_error_handler();
    }

    public static function mkstemp($path, $suffix = '', $prefix = '', $length = 5)
    {
        $prefix = preg_replace(sprintf('/_[A-Za-z0-9]{%d}$/', $length), '', $prefix);
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $char_length = strlen($characters) - 1;
        $rv = [null, null];

        set_error_handler('static::catchWarning');

        for ($i = 0; $i < 10; $i++) {
            $filename = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $prefix . (($prefix && $i > 0) ? '_' : '');

            if (!$prefix || $i > 0) {
                for ($j = 0; $j < $length; $j++) {
                    $filename .= $characters[mt_rand(0, $char_length)];
                }
            }
            $filename .= $suffix;

            try {
                $rv = [fopen($filename, 'x+'), $filename];
                break;
            } catch (\Exception $e) {
                error_log((string) $e);
                continue;
            }
        }

        restore_error_handler();

        return $rv;
    }

    public static function secure_filename($v, $max_len = 30)
    {
        if (!$v || !is_string($v)) {
            return '';
        }

        if (strpos($v, '.') === 0) {
            $v = '_' . $v;
        }

        $v = preg_replace(['#[\x00-\x1f]#', '#[^\p{L}\p{N}_.]|[/\:*?"<>|]#u', '#_{2,}#u'], ['', '_', '_'], trim($v));

        return mb_substr($v, 0, $max_len);
    }

    public static function upload(?array &$config, $name, $next_file_id = null)
    {
        set_error_handler('static::catchWarning');

        $error = null;
        $filename = null;

        if (!empty($_FILES[$name]['tmp_name']) && is_string($_FILES[$name]['tmp_name'])) {
            if ($_FILES[$name]['error'] !== UPLOAD_ERR_OK) {
                $error = __('File upload error');
            } elseif (!empty($config['max_size']) && $_FILES[$name]['size'] > $config['max_size']) {
                $error = __('File is too big');
            } else {
                $file_type = null;
                $image_data = null;
                $image_format = null;
                $image_geometry = null;

                if (extension_loaded('imagick')) {
                    try {
                        $im = new \Imagick();
                        $im->pingImage($_FILES[$name]['tmp_name']);
                        $image_format = $im->getImageFormat();
                        $image_geometry = $im->getImageGeometry();
                    } catch (\ImagickException $e) {
                        error_log((string) $e);
                    }

                    if ($image_format === 'PDF' || ($_FILES[$name]['type'] ?? null) === 'application/pdf') {
                        $file_type = 1;
                    } elseif (in_array($image_format, ['GIF', 'JPEG', 'PNG', 'PSD', 'SVG', 'TIFF', 'WEBP'])) {
                        $file_type = 2;
                    }
                } else {
                    $r = null;

                    try {
                        $r = getimagesize($_FILES[$name]['tmp_name']);
                    } catch (\Exception $e) {
                        error_log((string) $e);
                    }

                    $image_format = $r[2] ?? (($_FILES[$name]['type'] ?? null) === 'application/pdf' ? 1 : null);
                    $image_geometry = ['height' => $r[1] ?? null, 'width' => $r[0] ?? null];

                    if (array_key_exists($image_format, static::$image_file_types)) {
                        $file_type = 2;
                    }
                }

                if ($_FILES[$name]['type'] === 'image/svg+xml') {
                    $image_data = null;

                    try {
                        $image_data = (new Sanitizer())->sanitize(file_get_contents($_FILES[$name]['tmp_name']));
                    } catch (\Exception $e) {
                        error_log((string) $e);
                    }

                    if ($image_data) {
                        $file_type = 2;
                    }
                }

                $filename = (!empty($_FILES[$name]['name']) && is_string($_FILES[$name]['name'])) ? $_FILES[$name]['name'] : '';
                $ext = pathinfo($filename, PATHINFO_EXTENSION);

                if (!$file_type) {
                    if (preg_match('/pdf/i', $ext)) {
                        $file_type = 1;
                    } elseif (preg_match('/avi|mkv|mp4|mpe?g|ogg|ogm|ogv|webm/i', $ext)) {
                        $file_type = 3;
                    }
                }

                $relative_path = $next_file_id && !empty($config['number_files_per_directory']) ? round($next_file_id / $config['number_files_per_directory']) + 1 : null;
                $path = rtrim($config['path'], '/') . (isset($relative_path) ? '/' . $relative_path : '');

                $umask = null;

                if (($config['umask'] ?? null) !== null) {
                    $umask = umask($config['umask']);
                }

                if (!is_dir($path) && !mkdir($path, ($config['path_permission'] ?? null) !== null ? $config['path_permission'] : 0777, true)) {
                    $error = __('File upload error');
                } else {
                    $suffix = '';

                    if ((extension_loaded('imagick') && $image_format === 'SVG') || $_FILES[$name]['type'] === 'image/svg+xml') {
                        $suffix = '.svg';
                    } elseif ($image_format && extension_loaded('imagick')) {
                        $suffix = $image_format === 'JPEG' ? '.jpg' : ($image_format === 'TIFF' ? '.tif' : '.' . strtolower($image_format));
                    } elseif ($image_format && array_key_exists($image_format, static::$image_file_types)) {
                        $suffix = static::$image_file_types[$image_format];
                    } elseif ($ext) {
                        $suffix = '.' . $ext;
                    }

                    $prefix = !empty($_FILES[$name]['name']) && is_string($_FILES[$name]['name']) ? static::secure_filename(pathinfo($_FILES[$name]['name'])['filename']) : '';

                    list($_, $filename) = static::mkstemp($path, $suffix, $prefix);

                    if ((extension_loaded('imagick') && $image_format === 'SVG') || $_FILES[$name]['type'] === 'image/svg+xml' && $image_data) {
                        if (file_put_contents($filename, $image_data) === false) {
                            $error = __('File upload error');
                        } else {
                            return [
                                null,
                                [
                                    'filename' => (isset($relative_path) ? $relative_path . '/' : '') . basename($filename),
                                    'mime_type' => $_FILES[$name]['type'] ?? null,
                                    'name' => !empty($_FILES[$name]['name']) && is_string($_FILES[$name]['name']) ? mb_substr(basename($_FILES[$name]['name']), 0, 3000) : null,
                                    'type' => $file_type,
                                ],
                            ];
                        }
                    } else {
                        if (!move_uploaded_file($_FILES[$name]['tmp_name'], $filename)) {
                            $error = __('File upload error');
                        } elseif (($config['file_permission'] ?? null) !== null && !chmod($filename, $config['file_permission'])) {
                            $error = __('File upload error');
                        } else {
                            try {
                                $size = filesize($filename);
                            } catch (\Exception $e) {
                                error_log((string) $e);
                            }

                            return [
                                null,
                                [
                                    'filename' => (isset($relative_path) ? $relative_path . '/' : '') . basename($filename),
                                    'image_height' => $file_type === 2 ? $image_geometry['height'] ?? null : null,
                                    'image_width' => $file_type === 2 ? $image_geometry['width'] ?? null : null,
                                    'mime_type' => $_FILES[$name]['type'] ?? null,
                                    'name' => !empty($_FILES[$name]['name']) && is_string($_FILES[$name]['name']) ? mb_substr(basename($_FILES[$name]['name']), 0, 3000) : null,
                                    'size' => $size,
                                    'type' => $file_type,
                                ],
                            ];
                        }
                    }
                }

                if (($config['umask'] ?? null) !== null && $umask !== null) {
                    umask($umask);
                }
            }

            if ($error) {
                foreach ([$_FILES[$name]['tmp_name'], $filename] as $v) {
                    if ($v && is_file($v)) {
                        try {
                            unlink($v);
                        } catch (\Exception $e) {
                            error_log((string) $e);
                        }
                    }
                }
            }

            restore_error_handler();
        }

        return [$error, null];
    }
}
