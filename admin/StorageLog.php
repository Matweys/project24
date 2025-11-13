<?php

declare(strict_types=1);

namespace Admin;

use Helpers\Log;
use Symfony\Component\Yaml\Yaml;

class StorageLog
{
    public static function change_file_attributes(\PDO $db, array $user, array $storage, array $input_data, array $model_data)
    {
        $log_data = !empty($storage['attributes']) ? array_reduce(
            $storage['attributes'],
            function ($r, $v) use ($input_data) {
                $k = 'a'.$v['id'];

                switch ($v['type_name'] ?? '') {
                    case 'date':
                        $r['a'.$v['id']] = !empty($input_data[$k]) ? ($input_data[$k] instanceof \DateTime ? $input_data[$k]->format('Y-m-d') : null) : null;
                        break;
                    case 'datetime':
                        $r['a'.$v['id']] = !empty($input_data[$k]) ? ($input_data[$k] instanceof \DateTime ? $input_data[$k]->format('Y-m-d H:i:s') : null) : null;
                        break;
                    default:
                        $r['a'.$v['id']] = ($input_data[$k] ?? null) ?: null;
                        break;
                }

                return $r;
            },
            [
                'name' => $input_data['name'],
            ]
        ) : [
            'name' => $input_data['name'],
        ];

        $log_data_diff = array_diff($log_data, array_filter($model_data, function ($k) {
            return 'name' === $k || preg_match('/^a\d+$/', $k);
        }, ARRAY_FILTER_USE_KEY));

        if ($log_data_diff) {
            Log::message(
                db: $db,
                message: Yaml::dump([
                    'payload' => array_map(
                        function ($k) use ($log_data_diff, $storage) {
                            $attr_idx = array_search(preg_replace('#^a(\d+)$#', '$1', $k), array_column(($storage['attributes'] ?? []) ?: [], 'id'));
                            $attribute = false !== $attr_idx ? $storage['attributes'][$attr_idx] : null;

                            return !empty($attribute['id']) ?
                                [
                                    'id' => $attribute['id'] ?? null,
                                    'storage_id' => $storage['id'],
                                    'title' => $attribute['title'] ?? $k,
                                    'type' => 'attribute',
                                    'value' => $log_data_diff[$k] ?? null,
                                ] :
                                [
                                    'name' => $k,
                                    'type' => 'field',
                                    'value' => $log_data_diff[$k] ?? null,
                                ];
                        },
                        array_keys($log_data_diff),
                    ),
                    'storage' => [
                        'id' => $storage['id'],
                    ],
                    'type' => 'change_file_attributes',
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                    ],
                ]),
                storage: $storage['id'],
            );
        }
    }

    public static function delete_file(\PDO $db, array $user, array $storage, array $files)
    {
        Log::message(
            db: $db,
            message: Yaml::dump([
                'payload' => array_map(function ($v) use ($storage) {
                    return array_filter([
                        'folder' => $v['folder_id'] ?
                          [
                              'id' => $v['folder_id'],
                              'name' => $v['folder_name'],
                          ] :
                             null,
                        'id' => $v['id'],
                        'name' => $v['name'],
                        'storage_id' => $storage['id'],
                        'type' => 'file',
                    ]);
                }, $files),
                'storage' => [
                    'id' => $storage['id'],
                ],
                'type' => 'delete_file',
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                ],
            ]),
            storage: $storage['id'],
        );
    }

    public static function file_upload(\PDO $db, array $user, array $storage, array $files)
    {
        Log::message(
            db: $db,
            message: Yaml::dump(
                [
                    'payload' => array_map(
                        function ($v) use ($storage) {
                            return array_filter([
                                'folder' => $v['folder_id'] ?
                                    [
                                        'id' => $v['folder_id'],
                                        'name' => $v['folder_name'],
                                    ] :
                                    null,
                                'id' => $v['id'],
                                'name' => $v['name'],
                                'storage_id' => $storage['id'],
                                'type' => 'file',
                            ]);
                        },
                        $files
                    ),
                    'storage' => [
                        'id' => $storage['id'],
                    ],
                    'type' => 'file_upload',
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                    ],
                ]
            ),
            storage: $storage['id'],
        );
    }

    public static function storage_config(\PDO $db, array $user, int $storage_id)
    {
        $Storage = new Storage($db);

        $storage = $Storage->getStorage($storage_id);

        if ($storage) {
            $storage['permissions'] = $Storage->getUserPermissions($storage_id);
        }

        Log::message(
            db: $db,
            message: Yaml::dump([
                'payload' => array_filter([
                    'attributes' => $storage['attributes'] ? array_map(function ($v) {
                        return [
                            'id' => $v['id'],
                            'title' => $v['title'],
                            'type' => $v['type_name'],
                        ];
                    }, $storage['attributes']) : null,
                    'description' => $storage['description'],
                    'id' => $storage['id'],
                    'permissions' => $storage['permissions'] ? array_map(function ($v) {
                        return [
                            'id' => $v['id'],
                            'name' => $v['name'],
                            'user' => [
                                'email' => $v['email'],
                            ],
                        ];
                    }, $storage['permissions']) : null,
                    'title' => $storage['title'],
                    'uid' => $storage['uid'],
                ]),
                'type' => 'storage_config',
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                ],
            ]),
            storage: $storage['id'],
        );
    }
}
