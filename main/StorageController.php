<?php

declare(strict_types=1);

namespace Main;

use Helpers\Form;

class StorageController extends BaseController
{
    protected $storage;

    public function edit($storage_uid = null)
    {
        $this->current_user = $this->auth->loginRequired();
        $this->Storage = new Storage($this->db);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null);

        $storage = $this->Storage->getStorage(null, $storage_uid, $this->current_user['id']);
        $file_table = $storage ? $this->Storage->getFileTable($storage['id']) : null;

        if (!$storage || !$file_table || !in_array($storage['permission_name'] ?? null, ['edit', 'full'])) {
            http_response_code(404);
            return;
        }

        $id = (int) ($_GET['id'] ?? 0);
        $return_url = get_redirect_target() ?: $this->config['base_url'].'/storage/'.$storage['uid'].'/';
        $search_query = ($_GET['q'] ?? null) === null ? null : (string) $_GET['q'];
        $sort_desc = ($_GET['desc'] ?? null) === null ? null : (int) $_GET['desc'];
        $sort_idx = ($_GET['sort'] ?? null) === null ? null : (int) $_GET['sort'];
        $view_mode = ($_GET['view_mode'] ?? null) === null ? null : (int) $_GET['view_mode'];
        $xhr = 'xmlhttprequest' === strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');

        $sort_fields = [
            ['name', 'name DESC'],
            ['modified', 'modified DESC'],
            ['size', 'size DESC'],
        ];

        if (!empty($storage['attributes'])) {
            foreach ($storage['attributes'] as $v) {
                // Для текстовых полей добавляем NULLS LAST для корректной сортировки
                $field = 'a'.$v['id'];
                $is_text = in_array($v['type_name'] ?? '', ['string', 'text', '']);
                if ($is_text) {
                    $sort_fields[] = [$field.' NULLS LAST', $field.' DESC NULLS LAST'];
                } else {
                    $sort_fields[] = [$field, $field.' DESC'];
                }
            }
        }

        $sort_by = (isset($sort_fields[$sort_idx]) ? $sort_fields[$sort_idx][(int) ((bool) $sort_desc)] : 'name');

        if (!$search_query && !$xhr && (!empty($_GET['next']) || !empty($_GET['prev']))) {
            try {
                $pager = $this->Storage->getNextPrevFileIds($file_table, $id, $sort_by);
                $new_id = (!empty($_GET['next']) ? ($pager[0] ?? null) : ($pager[1] ?? null));
            } catch (\PDOException $e) {
                flash(__('Database error.'), 'error');
                error_log((string) $e);
                header('Location: '.$return_url);
                return;
            }

            header('Location: '.(function () {
                $v = $_SERVER['REQUEST_URI'] ?? '';
                if (($p = strpos($v, '?')) !== false) {
                    $v = substr($v, 0, $p);
                }
                return $v;
            })().'?'.http_build_query([
                'desc' => $sort_desc ? 1 : null,
                'id' => $new_id ? $new_id : $id,
                'sort' => $sort_idx,
                'url' => $return_url,
            ]));

            return;
        }

        if (!$search_query && isset($_POST['delete'])) {
            $count = null;
            $new_id = null;

            if ($id) {
                try {
                    $pager = $this->Storage->getNextPrevFileIds($file_table, $id, $sort_by);
                    $new_id = ($pager[0] ?? null) ?: ($pager[1] ?? null);
                } catch (\PDOException $e) {
                    flash(__('Database error.'), 'error');
                    error_log((string) $e);
                    header('Location: '.$return_url);
                    return;
                }

                $affected_files = null;

                try {
                    $this->db->beginTransaction();
                    $affected_files = $this->Storage->deleteFiles($this->config['upload'], $storage['id'], [$id]);
                    $this->db->commit();
                } catch (\PDOException $e) {
                    error_log((string) $e);
                    $this->db->rollBack();
                    flash(__('Database error.'), 'error');
                    header('Location: '.$return_url);
                    return;
                }

                if ($affected_files) {
                    if (!empty($this->config['storage_log'])) {
                        StorageLog::delete_file(
                            db: $this->db,
                            files: $affected_files,
                            storage: $storage,
                            user: $this->current_user,
                        );
                    }
                }

                try {
                    (new StorageSearch($this->db, new Cache($this->db, $this->config)))->rebuild_index($storage['id']);
                } catch (\PDOException $e) {
                    error_log((string) $e);
                }

                // Обновляем индексы Manticore после удаления файлов
                $this->updateManticoreIndexes();
            }

            if ($affected_files && $new_id) {
                header('Location: '.(function () {
                    $v = $_SERVER['REQUEST_URI'] ?? '';
                    if (($p = strpos($v, '?')) !== false) {
                        $v = substr($v, 0, $p);
                    }
                    return $v;
                })().'?'.http_build_query([
                    'desc' => $sort_desc ? 1 : null,
                    'id' => $new_id,
                    'sort' => $sort_idx,
                    'url' => $return_url,
                ]));
            } else {
                header('Location: '.$return_url);
            }

            return;
        }

        $file_data = null;

        if ($id) {
            try {
                $r = $this->db->prepare("select * from {$file_table} where id = ? and folder is not true");
                $r->execute([$id]);
                $file_data = $r->fetch(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                error_log((string) $e);
                if ($xhr) {
                    http_response_code(500);
                } else {
                    flash(__('Database error.'), 'error');
                    header('Location: '.$return_url);
                }
                return;
            }
        }

        if (!$file_data) {
            http_response_code(404);
            return;
        }

        $form_errors = [];

        $data = Form::filterInput(!empty($storage['attributes']) ? array_reduce(
            $storage['attributes'],
            function ($r, $v) {
                $k = 'a'.$v['id'];

                switch ($v['type_name'] ?? '') {
                    case 'float':
                        $r[$k] = FILTER_VALIDATE_FLOAT;
                        break;
                    case 'integer':
                        $r[$k] = FILTER_VALIDATE_INT;
                        break;
                    default:
                        $r[$k] = null;
                        break;
                }

                return $r;
            },
            [
                'name' => null,
            ]
        ) : [
            'name' => null,
        ]);

        $pager = null;

        if (!$search_query) {
            try {
                $pager = $this->Storage->getNextPrevFileIds($file_table, $id, $sort_by);
            } catch (\PDOException $e) {
                error_log((string) $e);
            }
        }

        if (($_SERVER['REQUEST_METHOD'] ?? null) === 'POST') {
            if (empty($data['name'])) {
                $form_errors['name'] = __('Enter filename.');
            } elseif (!$this->Storage->validateFilenameUnique($file_table, $data['name'], $file_data['parent_id'] ?? null, [$id])) {
                $form_errors['name'] = __('A file with the same name already exists.');
            }

            if (!empty($storage['attributes'])) {
                foreach ($storage['attributes'] as $v) {
                    $k = 'a'.$v['id'];

                    if (($v['type_name'] ?? null) === 'date' && !empty($data[$k])) {
                        $data[$k] = parse_date($data[$k]);
                    }

                    if (($v['type_name'] ?? null) === 'datetime' && !empty($data[$k])) {
                        $data[$k] = parse_datetime($data[$k]);
                    }

                    if (!empty($_POST[$k]) && empty($data[$k])) {
                        $form_errors[$k] = __('Incorrect value.');
                    }
                }
            }

            if (!$form_errors) {
                try {
                    $this->db->beginTransaction();

                    $r = $this->db->prepare(
                        "update {$file_table}
set modified = now(), name = :name".(
                            !empty($storage['attributes']) ?
    array_reduce(
        $storage['attributes'],
        function ($r, $v) {
            $r .= sprintf(',a%1$d = :a%1$d', $v['id']);
            return $r;
        },
        ''
    ) :
    ''
                        ).' where id = :id and folder is not true'
                    );

                    $r->execute(!empty($storage['attributes']) ? array_reduce(
                        $storage['attributes'],
                        function ($r, $v) use ($data) {
                            $k = 'a'.$v['id'];

                            switch ($v['type_name'] ?? '') {
                                case 'date':
                                    $r[':a'.$v['id']] = !empty($data[$k]) ? ($data[$k] instanceof \DateTime ? $data[$k]->format('Y-m-d') : null) : null;
                                    break;
                                case 'datetime':
                                    $r[':a'.$v['id']] = !empty($data[$k]) ? ($data[$k] instanceof \DateTime ? $data[$k]->format('Y-m-d H:i:s') : null) : null;
                                    break;
                                default:
                                    $r[':a'.$v['id']] = ($data[$k] ?? null) ?: null;
                                    break;
                            }

                            return $r;
                        },
                        [
                            ':id' => $id,
                            ':name' => $data['name'],
                        ]
                    ) : [
                        ':id' => $id,
                        ':name' => $data['name'],
                    ]);

                    $this->db->commit();
                } catch (\PDOException $e) {
                    error_log((string) $e);
                    $this->db->rollBack();
                    if ($xhr) {
                        http_response_code(500);
                    } else {
                        flash(__('Database error.'), 'error');
                        header('Location: '.$return_url);
                    }
                    return;
                }

                if (!empty($this->config['storage_log'])) {
                    StorageLog::change_file_attributes(
                        db: $this->db,
                        input_data: $data,
                        model_data: $file_data,
                        storage: $storage,
                        user: $this->current_user,
                    );
                }

                try {
                    (new StorageSearch($this->db, new Cache($this->db, $this->config)))->rebuild_index($storage['id']);
                } catch (\PDOException $e) {
                    error_log((string) $e);
                }

                // Обновляем индексы Manticore после удаления файлов
                $this->updateManticoreIndexes();

                if ($xhr) {
                    http_response_code(204);
                } else {
                    if (isset($_POST['save_and_next'])) {
                        if (!empty($pager[0])) {
                            header('Location: '.(function () {
                                $v = $_SERVER['REQUEST_URI'] ?? '';
                                if (($p = strpos($v, '?')) !== false) {
                                    $v = substr($v, 0, $p);
                                }
                                return $v;
                            })().'?'.http_build_query([
                                'desc' => $sort_desc ?: null,
                                'id' => $file_data['id'],
                                'next' => 1,
                                'sort' => $sort_idx,
                                'url' => $return_url,
                            ]));
                        } else {
                            header('Location: '.$return_url);
                        }
                    } else {
                        header('Location: '.$return_url);
                    }
                }

                return;
            }
        }

        render_template(
            !empty($this->current_user['settings']['file_form_pdf_preview']) && (($file_data['type'] ?? null) === null || 1 === $file_data['type']) ?
                'file_form_pdf_viewer' :
                (!empty($this->current_user['settings']['file_form_pdf_preview']) && 2 === $file_data['type'] ?
                    'file_form_image_viewer' :
                    'file_form'),
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,
                'storages' => &$this->storages,

                'active_item' => '/storage/'.$storage['uid'].'/',
                'breadcrumbs_data' => $this->Storage->getBreadcrubms($file_table, $file_data['parent_id'] ?? null),
                'data' => $file_data,
                'form_data' => Form::getFormData(
                    array_merge(
                        [
                            'name',
                        ],
                        !empty($storage['attributes']) ? array_map(function ($v) {
                            return 'a'.$v['id'];
                        }, $storage['attributes']) : []
                    ),
                    $file_data
                ),
                'form_errors' => $form_errors,
                'lang' => $this->lang['name'] ?? null,
                'languages' => $this->language->getLanguages(),
                'pager' => $pager,
                'return_url' => $return_url,
                'search_query' => $search_query,
                'sort_desc' => $sort_desc,
                'sort_idx' => $sort_idx,
                'storage' => $storage,
                'view' => $this->setting->getByGroups(['view'], $this->lang['name'] ?? null),
                'view_mode' => $view_mode,
            ],
            __DIR__.'/templates/'
        );
    }

    public function edit_folder($storage_uid = null)
    {
        $this->current_user = $this->auth->loginRequired();
        $this->Storage = new Storage($this->db);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null);

        $storage = $this->Storage->getStorage(null, $storage_uid, $this->current_user['id']);
        $file_table = $storage ? $this->Storage->getFileTable($storage['id']) : null;

        if (!$storage || !$file_table || !in_array($storage['permission_name'] ?? null, ['edit', 'full'])) {
            http_response_code(404);
            return;
        }

        $id = (int) ($_GET['id'] ?? 0);
        $return_url = get_redirect_target() ?: $this->config['base_url'].'/storage/'.$storage['uid'].'/';
        $sort_desc = ($_GET['desc'] ?? null) === null ? null : (int) $_GET['desc'];
        $sort_idx = ($_GET['sort'] ?? null) === null ? null : (int) $_GET['sort'];
        $view_mode = ($_GET['view_mode'] ?? null) === null ? null : (int) $_GET['view_mode'];

        $folder_data = null;

        if ($id) {
            try {
                $r = $this->db->prepare("select * from {$file_table} where id = ? and folder is true");
                $r->execute([$id]);
                $folder_data = $r->fetch(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                error_log((string) $e);
                flash(__('Database error.'), 'error');
                header('Location: '.$return_url);
                return;
            }
        }

        if (!$folder_data) {
            http_response_code(404);
            return;
        }

        $form_errors = [];

        $data = Form::filterInput([
            'name' => null,
        ]);

        if (($_SERVER['REQUEST_METHOD'] ?? null) === 'POST') {
            if (empty($data['name'])) {
                $form_errors['name'] = __('Enter filename.');
            } elseif (!$this->Storage->validateFilenameUnique($file_table, $data['name'], $folder_data['parent_id'] ?? null, [$id])) {
                $form_errors['name'] = __('A file with the same name already exists.');
            }

            if (!$form_errors) {
                try {
                    $this->db->beginTransaction();

                    $r = $this->db->prepare(
                        "update {$file_table}
set modified = now(), name = :name
where id = :id and folder is true"
                    );

                    $r->execute([
                        ':id' => $id,
                        ':name' => $data['name'],
                    ]);

                    $this->db->commit();
                } catch (\PDOException $e) {
                    error_log((string) $e);
                    $this->db->rollBack();
                    flash(__('Database error.'), 'error');
                    header('Location: '.$return_url);
                    return;
                }

                header('Location: '.$return_url);
                return;
            }
        }

        render_template(
            'folder_form',
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,
                'storages' => &$this->storages,

                'active_item' => '/storage/'.$storage['uid'].'/',
                'breadcrumbs_data' => $this->Storage->getBreadcrubms($file_table, $folder_data['id'] ?? null),
                'data' => $folder_data,
                'form_data' => Form::getFormData(
                    [
                        'name',
                    ],
                    $folder_data
                ),
                'form_errors' => $form_errors,
                'lang' => $this->lang['name'] ?? null,
                'languages' => $this->language->getLanguages(),
                'return_url' => $return_url,
                'sort_desc' => $sort_desc,
                'sort_idx' => $sort_idx,
                'storage' => $storage,
                'view' => $this->setting->getByGroups(['view'], $this->lang['name'] ?? null),
                'view_mode' => $view_mode,
            ],
            __DIR__.'/templates/'
        );
    }

    public function index($storage_uid = null, $folder_id = null)
    {
        $this->current_user = $this->auth->loginRequired();
        $this->Storage = new Storage($this->db);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null);

        $storage = $this->Storage->getStorage(null, $storage_uid, $this->current_user['id']);
        $file_table = $storage ? $this->Storage->getFileTable($storage['id']) : null;

        if (!$storage || !$file_table) {
            http_response_code(404);
            return;
        }

        $folder = null;

        if ((int) $folder_id) {
            $r = $this->db->prepare("select * from {$file_table} where id = ? and folder is true");
            $r->execute([(int) $folder_id]);
            $folder = $r->fetch(\PDO::FETCH_ASSOC);

            if (!$folder) {
                http_response_code(404);
                return;
            }
        }

        $upload_config = array_merge($this->config['upload'], [
            'path' => rtrim($this->config['upload']['path'], '/').'/'.$storage['uid'],
            'url' => rtrim($this->config['upload']['url'], '/').'/'.$storage['uid'].'/',
        ]);

        $page = (int) ($_GET['p'] ?? 0);
        $page_size = (int) ($this->current_user['settings']['page_size'] ?? 0);
        $search_query = ($_GET['q'] ?? null) === null ? null : (string) $_GET['q'];
        $sort_desc = ($_GET['desc'] ?? null) === null ? null : (int) $_GET['desc'];
        $sort_idx = ($_GET['sort'] ?? null) === null ? null : (int) $_GET['sort'];
        $view_mode = ($_GET['view_mode'] ?? null) === null ? null : (int) $_GET['view_mode'];
        $xhr = 'xmlhttprequest' === strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') || !empty($_GET['js']);

        $filter_data = filter_input_array(($_SERVER['REQUEST_METHOD'] ?? null) === 'POST' ? INPUT_POST : INPUT_GET, !empty($storage['attributes']) ? array_reduce(
            $storage['attributes'],
            function ($r, $v) {
                if (!empty($v['filter'])) {
                    $k = 'filter_a'.$v['id'];

                    switch ($v['type_name'] ?? '') {
                        case 'date':
                        case 'datetime':
                            $r[$k] = ['filter' => FILTER_UNSAFE_RAW, 'flags' => FILTER_REQUIRE_ARRAY];
                            break;
                        case 'float':
                            $r[$k] = ['filter' => FILTER_VALIDATE_FLOAT, 'flags' => FILTER_REQUIRE_ARRAY];
                            break;
                        case 'integer':
                            $r[$k] = ['filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_REQUIRE_ARRAY];
                            break;
                        default:
                            $r[$k] = ['filter' => FILTER_UNSAFE_RAW, 'flags' => 0];
                            break;
                    }
                }

                return $r;
            },
            ['filter_name' => ['filter' => FILTER_UNSAFE_RAW, 'flags' => 0]]
        ) : ['filter_name' => ['filter' => FILTER_UNSAFE_RAW, 'flags' => 0]]);

        $show_filter = null;

        if (is_array($filter_data)) {
            foreach ($filter_data as $k => $v) {
                if (isset($_REQUEST[$k])) {
                    $show_filter = true;
                    break;
                }
            }
        }

        $filter_form_errors = null;

        if (!empty($storage['attributes'])) {
            foreach ($storage['attributes'] as $v) {
                if (!empty($v['filter'])) {
                    $k = 'filter_a'.$v['id'];

                    switch ($v['type_name'] ?? '') {
                        case 'date':
                            if (isset($filter_data[$k][0]) && '' !== $filter_data[$k][0] && !parse_date($filter_data[$k][0])) {
                                $filter_form_errors[$k.'_0'] = __('Invalid date');
                            }

                            if (isset($filter_data[$k][1]) && '' !== $filter_data[$k][1] && !parse_date($filter_data[$k][1])) {
                                $filter_form_errors[$k.'_1'] = __('Invalid date');
                            }

                            break;
                        case 'datetime':
                            if (isset($filter_data[$k][0]) && '' !== $filter_data[$k][0] && !parse_datetime($filter_data[$k][0])) {
                                $filter_form_errors[$k.'_0'] = __('Invalid date');
                            }

                            if (isset($filter_data[$k][1]) && '' !== $filter_data[$k][1] && !parse_datetime($filter_data[$k][1])) {
                                $filter_form_errors[$k.'_1'] = __('Invalid date');
                            }

                            break;
                        case 'float':
                        case 'integer':
                            if (!empty($_REQUEST[$k][0]) && empty($filter_data[$k][0])) {
                                $filter_form_errors[$k.'_0'] = __('Incorrect value');
                            }

                            if (!empty($_REQUEST[$k][1]) && empty($filter_data[$k][1])) {
                                $filter_form_errors[$k.'_1'] = __('Incorrect value');
                            }

                            break;
                    }
                }
            }
        }

        $filter_query = null;

        if (!$filter_form_errors && $filter_data) {
            try {
                $sphinx = new \PDO($this->config['sphinx_uri'] ?? null);
                $sphinx->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $r = $sphinx->prepare("DESCRIBE {$file_table}_filter");
                $r->execute();
            } catch (\PDOException $e) {
                // Игнорируем ошибки подключения к Sphinx, если он недоступен
                if (strpos($e->getMessage(), "No such file or directory") !== false || strpos($e->getMessage(), "Connection refused") !== false) {
                    error_log("Sphinx/Manticore недоступен: " . $e->getMessage());
                    $r = null; // Помечаем, что подключение не удалось
                } elseif ('42S02' === $e->getCode()) {
                    error_log((string) $e);
                    $r = null;
                } else {
                    throw $e;
                }
            }

            $file_table_meta = [];
            $index_exists = false;

            if ($r) {
                while ($row = $r->fetch(\PDO::FETCH_ASSOC)) {
                    $file_table_meta[$row['Field']] = $row['Type'];
                }
                $index_exists = true; // Индекс существует, если DESCRIBE прошел успешно
            }

            // Создаем filter_query только если индекс существует
            if ($index_exists) {
                $filter_query = ['filter' => [], 'values' => [], 'text_values' => []];
                
                if (!empty($filter_data['filter_name']) && is_string($filter_data['filter_name'])) {
                    $filter_query['text_values'][] = sprintf('@name %s', $filter_data['filter_name']);
                }
            } else {
                // Если индекс не существует, логируем и не создаем filter_query
                error_log("Индекс Manticore {$file_table}_filter не существует. Фильтрация недоступна.");
                $filter_query = null;
            }

            foreach ($storage['attributes'] as $v) {
                if (!empty($v['filter']) && isset($file_table_meta['a'.$v['id'].(($v['type_name'] ?? '') == 'date' || ($v['type_name'] ?? '') == 'datetime' ? '_ts' : '')])) {
                    $k = 'filter_a'.$v['id'];

                    switch ($v['type_name'] ?? '') {
                        case 'date':
                            if (isset($filter_data[$k][0], $filter_data[$k][1]) && '' !== $filter_data[$k][0] && '' !== $filter_data[$k][1]) {
                                $filter_query['filter'][] = sprintf('a%1$s_ts BETWEEN :a%1$s_0 AND :a%1$s_1', $v['id']);
                                $filter_query['values'][':a'.$v['id'].'_0'] = parse_date($filter_data[$k][0])->getTimestamp();
                                $filter_query['values'][':a'.$v['id'].'_1'] = parse_date($filter_data[$k][1])->getTimestamp();
                            } elseif (isset($filter_data[$k][0]) && '' !== $filter_data[$k][0]) {
                                $filter_query['filter'][] = sprintf('a%1$s_ts >= :a%1$s_0', $v['id']);
                                $filter_query['values'][':a'.$v['id'].'_0'] = parse_date($filter_data[$k][0])->getTimestamp();
                            } elseif (isset($filter_data[$k][1]) && '' !== $filter_data[$k][1]) {
                                $filter_query['filter'][] = sprintf('a%1$s_ts <= :a%1$s_1', $v['id']);
                                $filter_query['values'][':a'.$v['id'].'_1'] = parse_date($filter_data[$k][1])->getTimestamp();
                            }

                            break;
                        case 'datetime':
                            if (isset($filter_data[$k][0], $filter_data[$k][1]) && '' !== $filter_data[$k][0] && '' !== $filter_data[$k][1]) {
                                $filter_query['filter'][] = sprintf('a%1$s_ts BETWEEN :a%1$s_0 AND :a%1$s_1', $v['id']);
                                $filter_query['values'][':a'.$v['id'].'_0'] = parse_datetime($filter_data[$k][0])->getTimestamp();
                                $filter_query['values'][':a'.$v['id'].'_1'] = parse_datetime($filter_data[$k][1])->getTimestamp();
                            } elseif (isset($filter_data[$k][0]) && '' !== $filter_data[$k][0]) {
                                $filter_query['filter'][] = sprintf('a%1$s_ts >= :a%1$s_0', $v['id']);
                                $filter_query['values'][':a'.$v['id'].'_0'] = parse_datetime($filter_data[$k][0])->getTimestamp();
                            } elseif (isset($filter_data[$k][1]) && '' !== $filter_data[$k][1]) {
                                $filter_query['filter'][] = sprintf('a%1$s_ts <= :a%1$s_1', $v['id']);
                                $filter_query['values'][':a'.$v['id'].'_1'] = parse_datetime($filter_data[$k][1])->getTimestamp();
                            }

                            break;
                        case 'float':
                            // PDO не поддерживает float

                            if (isset($filter_data[$k][0], $filter_data[$k][1]) && false !== $filter_data[$k][0] && false !== $filter_data[$k][1]) {
                                $filter_query['filter'][] = sprintf('a%s BETWEEN %s AND %s', $v['id'], $filter_data[$k][0], $filter_data[$k][1]);
                            } elseif (isset($filter_data[$k][0]) && false !== $filter_data[$k][0]) {
                                $filter_query['filter'][] = sprintf('a%s >= %s', $v['id'], $filter_data[$k][0]);
                            } elseif (isset($filter_data[$k][1]) && false !== $filter_data[$k][1]) {
                                $filter_query['filter'][] = sprintf('a%s <= %s', $v['id'], $filter_data[$k][1]);
                            }

                            break;
                        case 'integer':
                            if (isset($filter_data[$k][0], $filter_data[$k][1]) && false !== $filter_data[$k][0] && false !== $filter_data[$k][1]) {
                                $filter_query['filter'][] = sprintf('a%1$s BETWEEN :a%1$s_0 AND :a%1$s_1', $v['id']);
                                $filter_query['values'][':a'.$v['id'].'_0'] = $filter_data[$k][0];
                                $filter_query['values'][':a'.$v['id'].'_1'] = $filter_data[$k][1];
                            } elseif (isset($filter_data[$k][0]) && false !== $filter_data[$k][0]) {
                                $filter_query['filter'][] = sprintf('a%1$s >= :a%1$s_0', $v['id']);
                                $filter_query['values'][':a'.$v['id'].'_0'] = $filter_data[$k][0];
                            } elseif (isset($filter_data[$k][1]) && false !== $filter_data[$k][1]) {
                                $filter_query['filter'][] = sprintf('a%1$s <= :a%1$s_1', $v['id']);
                                $filter_query['values'][':a'.$v['id'].'_1'] = $filter_data[$k][1];
                            }

                            break;
                        default:
                            if (!empty($filter_data[$k]) && is_string($filter_data[$k])) {
                                $filter_query['text_values'][] = sprintf('@a%s %s', $v['id'], $filter_data[$k]);
                            }

                            break;
                    }
                }
            }

            if (is_array($filter_query['text_values'] ?? null) && $filter_query['text_values']) {
                $filter_query['filter'][] = 'MATCH(:text_query)';
                $filter_query['values'][':text_query'] = implode(' ', $filter_query['text_values']);
            }
        }

        $return_url = (function () {
            $v = $_SERVER['REQUEST_URI'] ?? '';
            if (($p = strpos($v, '?')) !== false) {
                $v = substr($v, 0, $p);
            }
            return $v;
        })().'?'.http_build_query(!empty($storage['attributes']) ? array_reduce(
            $storage['attributes'],
            function ($acc, $v) use ($filter_data) {
                if (!empty($v['filter'])) {
                    $k = 'filter_a'.$v['id'];
                    if (isset($filter_data[$k]) && is_array($filter_data[$k])) {
                        $acc[$k] = [($filter_data[$k][0] ?? null) ?: null, ($filter_data[$k][1] ?? null) ?: null];
                    } else {
                        $acc[$k] = ($filter_data[$k] ?? null) ?: null;
                    }
                }

                return $acc;
            },
            [
                'desc' => $sort_desc ? 1 : null,
                'filter_name' => ($filter_data['filter_name'] ?? null) ?: null,
                'p' => $page ?: null,
                'sort' => $sort_idx,
                'view_mode' => $view_mode,
            ]
        ) : [
            'desc' => $sort_desc ? 1 : null,
            'filter_name' => ($filter_data['filter_name'] ?? null) ?: null,
            'p' => $page ?: null,
            'sort' => $sort_idx,
            'view_mode' => $view_mode,
        ]);

        $sort_fields = [
            ['folder, name', 'folder, name DESC'],
            ['folder, modified', 'folder, modified DESC'],
            ['folder, size, name', 'folder, size DESC, name'],
        ];

        if (!empty($storage['attributes'])) {
            foreach ($storage['attributes'] as $v) {
                // Для текстовых полей добавляем NULLS LAST для корректной сортировки
                $field = 'a'.$v['id'];
                $is_text = in_array($v['type_name'] ?? '', ['string', 'text', '']);
                if ($is_text) {
                    $sort_fields[] = ['folder, '.$field.' NULLS LAST', 'folder, '.$field.' DESC NULLS LAST'];
                } else {
                    $sort_fields[] = ['folder, '.$field, 'folder, '.$field.' DESC'];
                }
            }
        }

        $data = null;

        if ($filter_query) {
            $offset = (int) max(0, $page * $page_size);

            try {
                $sphinx = new \PDO($this->config['sphinx_uri'] ?? null);
                $sphinx->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (\PDOException $e) {
                error_log("Не удалось подключиться к Manticore для фильтрации: " . $e->getMessage());
                $sphinx = null;
            }
            
            if (!$sphinx) {
                // Если Manticore недоступен, фильтр не работает
                $filter_query = null;
            }

            $folder_ids = null;

            if ($folder) {
                $r = $this->db->prepare("select id from {$file_table} where lft between ? and ? and folder is true");
                $r->execute([$folder['lft'], $folder['rgt']]);
                $folder_ids = $r->fetchAll(\PDO::FETCH_COLUMN);
            }

            $query = array_filter(array_merge(
                is_array($filter_query['filter']) ? $filter_query['filter'] : [],
                $folder_ids ? ['parent_id IN ('.array_reduce(
                    array_keys($folder_ids),
                    function ($r, $v) {
                        $r .= ($r ? ',' : '').':p'.($v + 1);
                        return $r;
                    },
                    ''
                ).')'] : []
            ));

            // Если нет условий, но есть текстовый поиск, добавляем условие 1=1
            $where_clause = !empty($query) ? implode(' and ', $query) : '1=1';
            
            $r = $sphinx->prepare(sprintf("select id from {$file_table}_filter where %s limit :offset, :page_size", $where_clause));

            if ($folder_ids) {
                foreach ($folder_ids as $i => $v) {
                    $r->bindValue(':p'.($i + 1), $v, \PDO::PARAM_INT);
                }
            }

            pdo_bind($r, [
                ':offset' => $offset,
                ':page_size' => $page_size,
            ]);

            if (is_array($filter_query['values'] ?? null)) {
                foreach ($filter_query['values'] as $k => $v) {
                    switch (true) {
                        case is_bool($v):
                            $r->bindValue($k, $v, \PDO::PARAM_BOOL);
                            break;
                        case is_float($v):
                            $r->bindValue($k, $v, \PDO::PARAM_INT);
                            break;
                        case is_int($v):
                            $r->bindValue($k, $v, \PDO::PARAM_INT);
                            break;
                        case is_null($v):
                            $r->bindValue($k, $v, \PDO::PARAM_NULL);
                            break;
                        default:
                            $r->bindValue($k, $v, \PDO::PARAM_STR);
                            break;
                    }
                }
            }

            try {
                $r->execute();
            } catch (\PDOException $e) {
                if ('42000' === $e->getCode()) {
                    error_log("Ошибка выполнения запроса к Manticore: " . (string) $e);
                    $filter_ids = [];
                    $count = 0;
                    $num_pages = 0;
                } else {
                    error_log("Критическая ошибка Manticore: " . (string) $e);
                    throw $e;
                }
            }

            if (isset($r) && $r) {
                $filter_ids = $r->fetchAll(\PDO::FETCH_COLUMN);

                try {
                    $r = $sphinx->prepare('show meta');
                    $r->execute();

                    $meta = [];

                    while ($row = $r->fetch(\PDO::FETCH_ASSOC)) {
                        $meta[$row['Variable_name']] = $row['Value'];
                    }

                    $count = (int) ($meta['total_found'] ?? 0);
                    $num_pages = $page_size ? ceil($count / $page_size) : 0;
                } catch (\PDOException $e) {
                    error_log("Ошибка получения метаданных Manticore: " . (string) $e);
                    $count = count($filter_ids);
                    $num_pages = $page_size ? ceil($count / $page_size) : 0;
                }
            } else {
                $filter_ids = [];
                $count = 0;
                $num_pages = 0;
            }

            if ($filter_ids) {
                $sort_by = (isset($sort_fields[$sort_idx]) ? $sort_fields[$sort_idx][(int) ((bool) $sort_desc)] : 'folder, name');

                $r = $this->db->prepare('select id,file,folder,image,mime_type,name,size,type'.
(!empty($storage['attributes']) ?
    array_reduce(
        $storage['attributes'],
        function ($r, $v) {
            $r .= sprintf(',a%d', $v['id']);
            return $r;
        },
        ''
    ) :
    '').
" from {$file_table} where id in (".str_repeat('?,', count($filter_ids) - 1)."?) order by {$sort_by}");
                $r->execute($filter_ids);
                $data = $r->fetchAll(\PDO::FETCH_ASSOC);
            }
        } else {
            $r = $this->db->prepare("select count(id) from {$file_table} where ".($folder ? 'parent_id = ?' : 'parent_id is null'));
            $r->execute($folder ? [$folder['id']] : null);
            $count = $r->fetchColumn();

            $num_pages = $page_size ? ceil($count / $page_size) : 0;
            $offset = (int) max(0, min($page, $num_pages - 1) * $page_size);

            $sort_by = (isset($sort_fields[$sort_idx]) ? $sort_fields[$sort_idx][(int) ((bool) $sort_desc)] : 'folder, name');

            $r = $this->db->prepare(
                'select id,file,folder,image,mime_type,name,size,type'.
(!empty($storage['attributes']) ?
    array_reduce(
        $storage['attributes'],
        function ($r, $v) {
            $r .= sprintf(',a%d', $v['id']);
            return $r;
        },
        ''
    ) :
    '').
" from {$file_table}
where ".($folder ? 'parent_id = :parent_id' : 'parent_id is null')."
order by {$sort_by}
limit :limit offset :offset"
            );

            pdo_bind($r, [
                ':limit' => $page_size,
                ':offset' => $offset,
            ]);

            if ($folder) {
                pdo_bind($r, [
                    ':parent_id' => $folder['id'],
                ]);
            }

            $r->execute();
            $data = $r->fetchAll(\PDO::FETCH_ASSOC);
        }

        if ($xhr) {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'attributes' => !empty($storage['attributes']) ? array_map(function ($v) {
                    return ['name' => 'a'.$v['id'], 'title' => $v['title']];
                }, $storage['attributes']) : null,

                'base_url' => $upload_config['url'],
                'count' => $count,

                'data' => $data ? array_map(function ($row) use ($return_url, $sort_desc, $sort_idx, $storage) {
                    $row['edit_url'] = $this->config['base_url'].'/storage/edit/'.$storage['uid'].'?'.http_build_query(['id' => $row['id'], 'url' => $return_url]);

                    if ($row['folder']) {
                        $row['folder_url'] = $this->config['base_url'].'/storage/'.$storage['uid'].'/'.$row['id'].'/?'.http_build_query(['sort' => $sort_idx, 'desc' => $sort_desc ?: null, 'url' => $return_url]);
                    }

                    foreach ($storage['attributes'] as $i => $attribute) {
                        $attribute_name = 'a'.$attribute['id'];

                        switch ($attribute['type_name'] ?? '') {
                            case 'date':
                                $row[$attribute_name] = datetime_format($row[$attribute_name] ?? null, 'dd.MM.yyyy');
                                break;
                            case 'datetime':
                                $row[$attribute_name] = datetime_format($row[$attribute_name] ?? null, 'dd.MM.yyyy H:mm');
                                break;
                        }
                    }

                    return $row;
                }, $data) : [],

                'filter_form_errors' => $filter_form_errors,
                'page_size' => $page_size,
                'return_url' => $return_url,
                'show_filter' => $show_filter,
                'static_url' => $this->config['static_url'],
                'storage_id' => $storage['uid'],
                'title' => $storage['title'],
                'view_mode' => $view_mode,
            ], JSON_UNESCAPED_UNICODE);
        } else {
            $pager_url = function ($p) use ($filter_data, $sort_desc, $sort_idx, $storage, $view_mode) {
                return (function () {
                    $v = $_SERVER['REQUEST_URI'] ?? '';
                    if (($p = strpos($v, '?')) !== false) {
                        $v = substr($v, 0, $p);
                    }
                    return $v;
                })().'?'.http_build_query(!empty($storage['attributes']) ? array_reduce(
                    $storage['attributes'],
                    function ($r, $v) use ($filter_data) {
                        if (!empty($v['filter'])) {
                            $k = 'filter_a'.$v['id'];
                            if (isset($filter_data[$k]) && is_array($filter_data[$k])) {
                                $r[$k] = [($filter_data[$k][0] ?? null) ?: null, ($filter_data[$k][1] ?? null) ?: null];
                            } else {
                                $r[$k] = ($filter_data[$k] ?? null) ?: null;
                            }
                        }

                        return $r;
                    },
                    [
                        'desc' => $sort_desc ? 1 : null,
                        'filter_name' => ($filter_data['filter_name'] ?? null) ?: null,
                        'p' => $p ?: null,
                        'sort' => $sort_idx,
                        'view_mode' => $view_mode,
                    ]
                ) : [
                    'desc' => $sort_desc ? 1 : null,
                    'filter_name' => ($filter_data['filter_name'] ?? null) ?: null,
                    'p' => $p ?: null,
                    'sort' => $sort_idx,
                    'view_mode' => $view_mode,
                ]);
            };

            $sort_url = function ($column_idx, $invert = false) use ($filter_data, $page, $sort_desc, $storage, $view_mode) {
                return (function () {
                    $v = $_SERVER['REQUEST_URI'] ?? '';
                    if (($p = strpos($v, '?')) !== false) {
                        $v = substr($v, 0, $p);
                    }
                    return $v;
                })().'?'.http_build_query(!empty($storage['attributes']) ? array_reduce(
                    $storage['attributes'],
                    function ($r, $v) use ($filter_data) {
                        if (!empty($v['filter'])) {
                            $k = 'filter_a'.$v['id'];
                            if (isset($filter_data[$k]) && is_array($filter_data[$k])) {
                                $r[$k] = [($filter_data[$k][0] ?? null) ?: null, ($filter_data[$k][1] ?? null) ?: null];
                            } else {
                                $r[$k] = ($filter_data[$k] ?? null) ?: null;
                            }
                        }

                        return $r;
                    },
                    [
                        'desc' => ($invert && !$sort_desc) ? 1 : null,
                        'filter_name' => ($filter_data['filter_name'] ?? null) ?: null,
                        'p' => $page ?: null,
                        'sort' => $column_idx,
                        'view_mode' => $view_mode,
                    ]
                ) : [
                    'desc' => ($invert && !$sort_desc) ? 1 : null,
                    'filter_name' => ($filter_data['filter_name'] ?? null) ?: null,
                    'p' => $page ?: null,
                    'sort' => $column_idx,
                    'view_mode' => $view_mode,
                ]);
            };

            render_template(
                'file_list',
                [
                    'config' => &$this->config,
                    'current_user' => &$this->current_user,
                    'storages' => &$this->storages,

                    'breadcrumbs_data' => $this->Storage->getBreadcrubms($file_table, (int) $folder_id),
                    'clear_filter_url' => (function () {
                        $v = $_SERVER['REQUEST_URI'] ?? '';
                        if (($p = strpos($v, '?')) !== false) {
                            $v = substr($v, 0, $p);
                        }
                        return $v;
                    })().'?'.http_build_query([
                        'desc' => $sort_desc ? 1 : null,
                        'sort' => $sort_idx,
                    ]),
                    'count' => $count,
                    'data' => $data,
                    'filter_data' => $filter_data,
                    'filter_form_errors' => $filter_form_errors,
                    'filter_form_url' => (function () {
                        $v = $_SERVER['REQUEST_URI'] ?? '';
                        if (($p = strpos($v, '?')) !== false) {
                            $v = substr($v, 0, $p);
                        }
                        return $v;
                    })().'?'.http_build_query([
                        'desc' => $sort_desc ? 1 : null,
                        'sort' => $sort_idx,
                    ]),
                    'folder' => $folder,
                    'folder_file_count' => $this->Storage->getFileCount($file_table, $folder['id'] ?? null),
                    'lang' => $this->lang['name'] ?? null,
                    'languages' => $this->language->getLanguages(),
                    'page' => $page,
                    'page_size' => $page_size,
                    'pager_url' => $pager_url,
                    'return_url' => $return_url,
                    'search_query' => $search_query,
                    'show_filter' => $show_filter,
                    'sort_desc' => $sort_desc,
                    'sort_idx' => $sort_idx,
                    'sort_url' => $sort_url,
                    'storage' => $storage,
                    'upload_config' => $upload_config,
                    'view' => $this->setting->getByGroups(['view'], $this->lang['name'] ?? null),
                    'view_mode' => $view_mode,
                ],
                __DIR__.'/templates/'
            );
        }
    }

    public function new_folder($storage_uid = null, $folder_id = null)
    {
        $this->current_user = $this->auth->loginRequired();
        $this->Storage = new Storage($this->db);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null);

        $storage = $this->Storage->getStorage(null, $storage_uid, $this->current_user['id']);
        $file_table = $storage ? $this->Storage->getFileTable($storage['id']) : null;

        if (!$storage || !$file_table || !in_array($storage['permission_name'] ?? null, ['edit', 'full'])) {
            http_response_code(404);
            return;
        }

        $folder = null;

        if ((int) $folder_id) {
            $r = $this->db->prepare("select * from {$file_table} where id = ? and folder is true");
            $r->execute([(int) $folder_id]);
            $folder = $r->fetch(\PDO::FETCH_ASSOC);

            if (!$folder) {
                http_response_code(404);
                return;
            }
        }

        $return_url = get_redirect_target() ?: $this->config['base_url'].'/storage/'.$storage['uid'].'/';
        $sort_desc = ($_GET['desc'] ?? null) === null ? null : (int) $_GET['desc'];
        $sort_idx = ($_GET['sort'] ?? null) === null ? null : (int) $_GET['sort'];
        $view_mode = ($_GET['view_mode'] ?? null) === null ? null : (int) $_GET['view_mode'];

        $form_errors = [];

        $data = Form::filterInput([
            'name' => null,
        ]);

        if (($_SERVER['REQUEST_METHOD'] ?? null) === 'POST') {
            if (empty($data['name'])) {
                $form_errors['name'] = __('Enter folder name.');
            } elseif (!$this->Storage->validateFilenameUnique($file_table, $data['name'], $file_data['parent_id'] ?? null)) {
                $form_errors['name'] = __('A file with the same name already exists.');
            }

            if (!$form_errors) {
                try {
                    $this->db->beginTransaction();

                    $r = $this->db->prepare('CALL storage_new_folder(:storage_id, :name, :parent_id)');
                    $r->execute([
                        ':name' => $data['name'] ?: null,
                        ':parent_id' => $folder['id'] ?? null,
                        ':storage_id' => $storage['id'],
                    ]);

                    $this->db->commit();
                } catch (\PDOException $e) {
                    error_log((string) $e);
                    $this->db->rollBack();
                    flash(__('Database error.'), 'error');
                    header('Location: '.$return_url);
                    return;
                }

                header('Location: '.$return_url);
                return;
            }
        }

        render_template(
            'folder_form',
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,
                'storages' => &$this->storages,

                'active_item' => '/storage/'.$storage['uid'].'/',
                'breadcrumbs_data' => $this->Storage->getBreadcrubms($file_table, $folder['id'] ?? null),
                'form_data' => Form::getFormData([
                    'name',
                ]),
                'form_errors' => $form_errors,
                'lang' => $this->lang['name'] ?? null,
                'languages' => $this->language->getLanguages(),
                'return_url' => $return_url,
                'sort_desc' => $sort_desc,
                'sort_idx' => $sort_idx,
                'storage' => $storage,
                'view' => $this->setting->getByGroups(['view'], $this->lang['name'] ?? null),
                'view_mode' => $view_mode,
            ],
            __DIR__.'/templates/'
        );
    }

    public function upload($storage_uid = null, $folder_id = null)
    {
        if (($_SERVER['REQUEST_METHOD'] ?? null) !== 'POST') {
            http_response_code(405);
            return;
        }

        $this->current_user = $this->auth->isLogged() ? $this->auth->getCurrentUser() : null;

        if (!$this->current_user) {
            http_response_code(401);
            exit;
        }

        $this->Storage = new Storage($this->db);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null);

        $storage = $this->Storage->getStorage(null, $storage_uid, $this->current_user['id']);
        $file_table = $storage ? $this->Storage->getFileTable($storage['id']) : null;

        if (!$storage || !$file_table || !in_array($storage['permission_name'] ?? null, ['edit', 'full'])) {
            http_response_code(401);
            echo __('No permission to upload files.');
            return;
        }

        $folder = null;

        if ((int) $folder_id) {
            $r = $this->db->prepare("select * from {$file_table} where id = ? and folder is true");
            $r->execute([(int) $folder_id]);
            $folder = $r->fetch(\PDO::FETCH_ASSOC);

            if (!$folder) {
                http_response_code(404);
                return;
            }
        }

        $file_overwrite = ($_SERVER['HTTP_X_FILE_OVERWRITE'] ?? null) === '?1' ? true : (($_SERVER['HTTP_X_FILE_OVERWRITE'] ?? null) === '?0' ? false : null);

        $upload_config = array_merge($this->config['upload'], [
            'path' => rtrim($this->config['upload']['path'], '/').'/'.$storage['uid'],
            'url' => rtrim($this->config['upload']['url'], '/').'/'.$storage['uid'].'/',
        ]);

        $error = false;
        $last_file_id = null;

        try {
            try {
                $r = $this->db->prepare("select last_value from {$file_table}_id_seq");
                $r->execute();
                $last_file_id = $r->fetchColumn();
            } catch (\PDOException $e) {
                $error = true;
                error_log((string) $e);
                throw new ElseException();
            }

            list($error, $upload) = FileUpload::upload($upload_config, 'file', $last_file_id);
            
            error_log("FileUpload::upload completed, error: " . ($error ?: 'none') . ", filename: " . ($upload['filename'] ?? 'none'));

            if (!$error && !empty($upload['filename'])) {
                error_log("Entering file upload success block, storage_id: " . ($storage['id'] ?? 'unknown'));
                $existing_file_id = $this->Storage->checkFilenameUnique($file_table, $upload['name'] ?? '', $folder['id'] ?? null);

                if ($existing_file_id && true !== $file_overwrite) {
                    echo sprintf(__('The file %s already exists.'), $upload['name']);
                    http_response_code(409);
                    exit;
                }

                try {
                    $this->db->beginTransaction();

                    $r = $this->db->prepare(
                        "insert into {$file_table} (
    file,
    image,
    image_height,
    image_width,
    mime_type,
    name,
    parent_id,
    size,
    type
) values (
    :file,
    :image,
    :image_height,
    :image_width,
    :mime_type,
    :name,
    :parent_id,
    :size,
    :type
)"
                    );

                    $r->execute([
                        ':image_height' => $upload['image_height'] ?? null,
                        ':image_width' => $upload['image_width'] ?? null,
                        ':mime_type' => $upload['mime_type'] ?? null,
                        ':name' => ($upload['name'] ?? null) ?: basename($upload['filename']),
                        ':type' => (int) ($upload['type'] ?? 0),
                        ':file' => $upload['filename'],
                        ':image' => ($upload['type'] ?? 0) === 2 && ($upload['mime_type'] ?? null) === 'image/svg+xml' ? $upload['filename'] : null,
                        ':parent_id' => $folder['id'] ?? null,
                        ':size' => $upload['size'] ?? null,
                    ]);

                    $file_id = (int) $this->db->lastInsertId("{$file_table}_id_seq");

                    if ($existing_file_id && true === $file_overwrite) {
                        $affected_files = $this->Storage->deleteFiles($this->config['upload'], $storage['id'], [$existing_file_id]);
                    }

                    $this->db->commit();
                } catch (\PDOException $e) {
                    $error = true;
                    error_log((string) $e);
                    $this->db->rollBack();
                    throw new ElseException();
                }

                $r = $this->db->prepare("select file.id, file.file, file.image, file.name, folder.id as folder_id, folder.name as folder_name from {$file_table} file left join {$file_table} folder on folder.id = file.parent_id where file.id = ? and file.folder is not true");

                $r->execute([$file_id]);
                $files = $r->fetchAll(\PDO::FETCH_ASSOC);

                if ($files) {
                    if (!empty($this->config['storage_log'])) {
                        StorageLog::file_upload(
                            db: $this->db,
                            files: $files,
                            storage: $storage,
                            user: $this->current_user,
                        );
                    }
                }

                if (($upload['type'] ?? 0) === 1) {
                    try {
                        $r = $this->db->prepare("insert into gue_jobs (job_id, args, created_at, job_type, priority, queue, run_at, updated_at) values (?, ?, current_timestamp, 'pdf_text', 10, 'pdf_text', current_timestamp, current_timestamp)");

                        $r->execute([
                            \PgIto\FastUlid\FastUlid::gen(),
                            json_encode(
                                [
                                    'file_id' => $file_id,
                                    'storage_id' => $storage['id'],
                                ],
                                JSON_UNESCAPED_UNICODE
                            ),
                        ]);
                    } catch (\PDOException $e) {
                        error_log((string) $e);
                        throw new ElseException();
                    }
                }

                if (($upload['type'] ?? 0) === 1 || (($upload['type'] ?? 0) === 2 && ($upload['mime_type'] ?? null) !== 'image/svg+xml')) {
                    try {
                        $r = $this->db->prepare("insert into gue_jobs (job_id, args, created_at, job_type, priority, queue, run_at, updated_at) values (?, ?, current_timestamp, 'thumbnail', 20, 'queue', current_timestamp, current_timestamp)");

                        $r->execute([
                            \PgIto\FastUlid\FastUlid::gen(),
                            json_encode(
                                [
                                    'file_id' => $file_id,
                                    'storage_id' => $storage['id'],
                                ],
                                JSON_UNESCAPED_UNICODE
                            ),
                        ]);
                    } catch (\PDOException $e) {
                        error_log((string) $e);
                        throw new ElseException();
                    }
                }

                try {
                    $r = $this->db->prepare("insert into gue_jobs (job_id, args, created_at, job_type, priority, queue, run_at, updated_at) values (?, ?, current_timestamp, 'file_crc', 10, 'queue', current_timestamp, current_timestamp)");

                    $r->execute([
                        \PgIto\FastUlid\FastUlid::gen(),
                        json_encode(
                            [
                                'file_id' => $file_id,
                                'storage_id' => $storage['id'],
                            ],
                            JSON_UNESCAPED_UNICODE
                        ),
                    ]);
                } catch (\PDOException $e) {
                    error_log((string) $e);
                    throw new ElseException();
                }

                try {
                    (new StorageSearch($this->db, new Cache($this->db, $this->config)))->rebuild_index($storage['id']);
                } catch (\PDOException $e) {
                    error_log((string) $e);
                }

                // Обновляем индексы Manticore после загрузки файлов
                error_log("Before updateManticoreIndexes call, storage_id: " . ($storage['id'] ?? 'unknown'));
                $this->updateManticoreIndexes();
                error_log("After updateManticoreIndexes call");
            }
        } catch (ElseException $e) {
            error_log("ElseException caught in upload method: " . $e->getMessage());
        }

        if ($error) {
            FileUpload::deleteTempFiles('file');
            if (!empty($upload['filename'])) {
                FileUpload::deleteFiles($upload_config, $upload['filename']);
            }

            http_response_code(422);
            echo is_string($error) ? $error : __('File upload error');
            exit;
        }

        http_response_code(204);
    }

    /**
     * Автоматическое обновление конфигурации Manticore и создание индексов
     * Вызывается после загрузки файлов
     */
    protected function updateManticoreIndexes(): void
    {
        error_log("updateManticoreIndexes: метод вызван");
        
        $project_dir = dirname(__DIR__);
        $config_script = $project_dir . '/doc/manticore.conf.debian.sample';
        $manticore_config = '/etc/manticoresearch/manticore.conf';

        if (!file_exists($config_script)) {
            error_log("Manticore config script not found: {$config_script}");
            return;
        }
        
        error_log("Manticore config script found: {$config_script}");

        try {
            // Генерируем конфигурацию Manticore
            error_log("updateManticoreIndexes: генерирую конфигурацию");
            $output = [];
            $return_var = 0;
            exec("php " . escapeshellarg($config_script) . " 2>&1", $output, $return_var);

            if ($return_var !== 0) {
                error_log("Failed to generate Manticore config: " . implode("\n", $output));
                return;
            }
            
            error_log("updateManticoreIndexes: конфигурация сгенерирована успешно");

            $config_content = implode("\n", $output);

            // Сохраняем во временный файл
            $tmp_config = sys_get_temp_dir() . '/manticore_config_' . uniqid() . '.conf';
            if (file_put_contents($tmp_config, $config_content) === false) {
                error_log("Failed to write temporary Manticore config");
                return;
            }

            // Копируем конфигурацию (требуются права root или sudo)
            $commands = [
                [
                    'cmd' => "cp " . escapeshellarg($tmp_config) . " " . escapeshellarg($manticore_config),
                    'use_sudo' => true,
                ],
                [
                    'cmd' => "systemctl restart manticore",
                    'use_sudo' => true,
                ],
                [
                    'cmd' => "su - manticore -s /bin/bash -c " . escapeshellarg("indexer --all --rotate"),
                    'use_sudo' => true,
                    'ignore_no_tables' => true, // Игнорируем ошибку "no tables found"
                ],
            ];

            // Выполняем команды
            error_log("updateManticoreIndexes: начинаю выполнение команд");
            
            // Проверяем, запущен ли PHP от root
            $is_root = (posix_geteuid() === 0);
            error_log("updateManticoreIndexes: PHP запущен от root: " . ($is_root ? 'да' : 'нет'));
            
            foreach ($commands as $cmd_data) {
                $cmd = $cmd_data['cmd'];
                $output = [];
                $return_var = 0;

                error_log("updateManticoreIndexes: выполняю команду: " . substr($cmd, 0, 100));

                // Если PHP запущен от root, выполняем команды напрямую без sudo
                // Если нет - пробуем через sudo (если настроено)
                if ($is_root) {
                    // PHP от root - выполняем напрямую
                    exec($cmd . " 2>&1", $output, $return_var);
                } elseif (!empty($cmd_data['use_sudo'])) {
                    // Пробуем через sudo
                    exec("sudo " . $cmd . " 2>&1", $output, $return_var);
                    
                    // Если sudo не сработал, пробуем напрямую (на случай, если права есть)
                    if ($return_var !== 0) {
                        exec($cmd . " 2>&1", $output, $return_var);
                    }
                } else {
                    // Выполняем напрямую
                    exec($cmd . " 2>&1", $output, $return_var);
                }
                
                error_log("updateManticoreIndexes: команда выполнена, код возврата: {$return_var}");

                if ($return_var !== 0) {
                    $output_str = implode("\n", $output);
                    
                    // Для indexer ошибка "no tables found" - это нормально, если хранилищ еще нет
                    if (!empty($cmd_data['ignore_no_tables']) && strpos($output_str, 'no tables found') !== false) {
                        // Это нормально, игнорируем
                        continue;
                    }
                    
                    error_log("Manticore update command failed: {$cmd} - " . $output_str);
                }
            }

            // Удаляем временный файл
            @unlink($tmp_config);
            error_log("updateManticoreIndexes: завершено успешно");
        } catch (\Exception $e) {
            error_log("Error updating Manticore indexes: " . $e->getMessage());
            error_log("Error updating Manticore indexes: trace: " . $e->getTraceAsString());
        }
    }
}
