<?php

declare(strict_types=1);

namespace Admin;

use Helpers\Form;

class StorageController extends BaseController
{
    protected $default_url;

    public function action()
    {
        $this->current_user = $this->auth->rolesRequired(['admin', 'storage_management']);

        $this->default_url = $this->config['base_url'].'/storages/';
        $this->StorageFileTable = new StorageFileTable($this->db);
        $this->Storage = new Storage($this->db);
        $this->User = new User($this->db, $this->config);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null, ['admin_autoload', 'autoload']);

        $return_url = get_redirect_target() ?: $this->default_url;

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $ids = $id ? [(int) $id] : filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);

        if (isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD']) {
            if (($_POST['action'] ?? null) === 'action_delete') {
                try {
                    if ($ids) {
                        $deleted_count = 0;
                        $errors = [];

                        foreach ($ids as $storage_id) {
                            try {
                                // Получаем информацию о хранилище
                                $r = $this->db->prepare('SELECT id, uid FROM storage WHERE id = ?');
                                $r->execute([$storage_id]);
                                $storage = $r->fetch(\PDO::FETCH_ASSOC);
                                
                                if (!$storage) {
                                    // Удаляем старые задачи для несуществующего хранилища
                                    try {
                                        $r = $this->db->prepare("DELETE FROM gue_jobs WHERE job_type = 'delete_storage' AND (convert_from(args, 'utf8')::json->>'storage_id')::int = ?");
                                        $r->execute([$storage_id]);
                                    } catch (\PDOException $e) {
                                        error_log((string) $e);
                                    }
                                    $errors[] = sprintf(__('Storage with ID %d does not exist.'), $storage_id);
                                    continue;
                                }

                                $this->db->beginTransaction();

                                try {
                                    // Получаем все файлы из таблицы хранилища для удаления физических файлов
                                    $file_table = $this->Storage->getFileTable($storage_id);
                                    if ($file_table) {
                                        $r = $this->db->prepare("SELECT file, image FROM $file_table WHERE folder is not true");
                                        $r->execute();
                                        $files = $r->fetchAll(\PDO::FETCH_ASSOC);

                                        // Удаляем физические файлы
                                        if ($files && !empty($this->config['upload'])) {
                                            $upload_path = rtrim($this->config['upload']['path'], '/') . '/' . $storage['uid'];
                                            
                                            $file_paths = array_filter(array_column($files, 'file'));
                                            $image_paths = array_filter(array_column($files, 'image'));
                                            
                                            // Удаляем файлы
                                            foreach (array_merge($file_paths, $image_paths) as $file_path) {
                                                if ($file_path) {
                                                    $full_path = $upload_path . '/' . $file_path;
                                                    if (is_file($full_path)) {
                                                        try {
                                                            unlink($full_path);
                                                        } catch (\Exception $e) {
                                                            error_log((string) $e);
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        // Удаляем таблицу файлов
                                        $this->StorageFileTable->deleteFileTable($storage_id);
                                    }

                                    // Удаляем задачи из очереди для этого хранилища
                                    try {
                                        $r = $this->db->prepare("DELETE FROM gue_jobs WHERE (convert_from(args, 'utf8')::json->>'storage_id')::int = ?");
                                        $r->execute([$storage_id]);
                                    } catch (\PDOException $e) {
                                        error_log((string) $e);
                                    }

                                    // Удаляем запись хранилища (связанные данные удалятся каскадно)
                                    $r = $this->db->prepare('DELETE FROM storage WHERE id = ?');
                                    $r->execute([$storage_id]);

                                    $this->db->commit();
                                    ++$deleted_count;
                                } catch (\Exception $e) {
                                    $this->db->rollBack();
                                    throw $e;
                                }
                            } catch (\PDOException $e) {
                                error_log((string) $e);
                                $errors[] = sprintf(__('Error deleting storage ID %d: %s'), $storage_id, $e->getMessage());
                            } catch (\Exception $e) {
                                error_log((string) $e);
                                $errors[] = sprintf(__('Error deleting storage ID %d: %s'), $storage_id, $e->getMessage());
                            }
                        }

                        if ($deleted_count > 0) {
                            flash(sprintf(_n('File storage has been deleted.', '%d file storages have been deleted.', $deleted_count), $deleted_count));
                        }

                        if ($errors) {
                            flash(implode('<br>', $errors), 'error');
                        }
                    }
                } catch (ElseException $e) {
                    flash(__('An error occurred while deleting storage.'), 'error');
                    error_log((string) $e);
                }
            }
        }

        header('Location: '.$return_url);
    }

    public function create()
    {
        $this->current_user = $this->auth->rolesRequired(['admin', 'storage_management']);

        $this->default_url = $this->config['base_url'].'/storages/';
        $this->StorageFileTable = new StorageFileTable($this->db);
        $this->Storage = new Storage($this->db);
        $this->User = new User($this->db, $this->config);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null, ['admin_autoload', 'autoload']);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $return_url = get_redirect_target() ?: $this->default_url;

        $form_errors = [];

        $data = self::filterInput();

        if (isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD']) {
            $this->form_validate($data, $form_errors);

            if (!$form_errors) {
                try {
                    $this->db->beginTransaction();

                    $r = $this->db->prepare(
                        'INSERT INTO storage (
    description,
    title,
    uid
)
VALUES (
    :description,
    :title,
    :uid
)'
                    );

                    $r->execute([
                        ':description' => $data['description'],
                        ':title' => $data['title'],
                        ':uid' => generate_random_string(10),
                    ]);

                    $id = $this->db->lastInsertId('storage_id_seq');

                    $this->Storage->updateAttributes(
                        (int) $id,
                        $data['attributes']
                    );

                    $errors = [];
                    $invite_count = 0;

                    if (is_array($data['user_permissions'])) {
                        foreach ($data['user_permissions'] as $v) {
                            $r = $this->User->register($v['email'], $v['activate'], $this->lang['name'] ?? null);

                            if (true === $r) {
                                ++$invite_count;
                            } elseif ($r) {
                                $errors[] = $r;
                            }
                        }
                    }

                    $errors = array_filter($errors);
                    if ($errors) {
                        flash(implode('<br>', $errors), 'error');
                    } elseif ($invite_count) {
                        flash(sprintf(_n('The invite email has been sent.', 'The invite emails have been sent to %d users.', $invite_count), $invite_count));
                    }

                    $this->Storage->updateUserPermissions(
                        (int) $id,
                        is_array($data['user_permissions']) ? array_map(function ($v) {
                            $v['permission_id'] = $v['permission'];
                            return $v;
                        }, array_filter($data['user_permissions'], function ($v) {
                            return empty($v['del']);
                        })) : null
                    );

                    $this->db->commit();

                    $this->StorageFileTable->createFileTable($id);

                    if (!empty($this->config['storage_log'])) {
                        StorageLog::storage_config(
                            db: $this->db,
                            storage_id: (int) $id,
                            user: $this->current_user,
                        );
                    }
                } catch (PDOException $e) {
                    flash(__('Database error.'), 'error');
                    error_log((string) $e);
                    $this->db->rollBack();
                }

                try {
                    (new StorageSearch($this->db, new Cache($this->db, $this->config)))->rebuild_index((int) $id);
                } catch (\PDOException $e) {
                    error_log((string) $e);
                }

                // Автоматическое обновление индексов Manticore
                $this->updateManticoreIndexes((int) $id);

                header('Location: '.$return_url);
                return;
            }
        }

        render_template(
            'storage_form',
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,
                'storages' => &$this->storages,

                'active_item' => $this->default_url,
                'form_data' => static::getFormData([
                    'active' => 1,
                ]),
                'form_errors' => $form_errors,
                'lang' => $this->lang['name'] ?? null,
                'languages' => $this->language->getLanguages(),
                'return_url' => $return_url,
                'view' => $this->setting->getByGroups(['admin_view', 'view'], $this->lang['name'] ?? null),
                'widget_data' => [
                    'attribute_types' => $this->Storage->getAttributeTypes($this->lang['name'] ?? null),
                    'storage_permissions' => $this->Storage->getStoragePermissions($this->lang['name'] ?? null),
                ],
            ],
            __DIR__.'/templates/'
        );
    }

    public function edit()
    {
        $this->current_user = $this->auth->rolesRequired(['admin', 'storage_management']);

        $this->default_url = $this->config['base_url'].'/storages/';
        $this->StorageFileTable = new StorageFileTable($this->db);
        $this->Storage = new Storage($this->db);
        $this->User = new User($this->db, $this->config);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null, ['admin_autoload', 'autoload']);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $return_url = get_redirect_target() ?: $this->default_url;

        $id = (int) ($_GET['id'] ?? 0);

        $storage_data = null;

        if ($id) {
            try {
                $r = $this->db->prepare('SELECT * FROM storage WHERE id = ?');
                $r->execute([$id]);
                $storage_data = $r->fetch(\PDO::FETCH_ASSOC);

                if ($storage_data) {
                    $storage_data['attributes'] = $this->Storage->getAttributes($id);
                    $storage_data['user_permissions'] = $this->Storage->getUserPermissions($id, $this->lang['name'] ?? null);
                }
            } catch (PDOException $e) {
                flash(__('Database error.'), 'error');
                error_log((string) $e);
                header('Location: '.$return_url);
                return;
            }
        }

        if (!$storage_data) {
            http_response_code(404);
            return;
        }

        $form_errors = [];

        $data = self::filterInput();

        if (isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD']) {
            $this->form_validate($data, $form_errors, $id);

            if (!$form_errors) {
                try {
                    $this->db->beginTransaction();

                    $r = $this->db->prepare(
                        'UPDATE storage
SET
    description = :description,
    modified = NOW(),
    title = :title
WHERE id = :id'
                    );
                    $r->execute([
                        ':description' => $data['description'],
                        ':id' => $id,
                        ':title' => $data['title'],
                    ]);

                    $this->Storage->updateAttributes(
                        (int) $id,
                        $data['attributes']
                    );

                    $errors = [];
                    $invite_count = 0;

                    if (is_array($data['user_permissions'])) {
                        foreach ($data['user_permissions'] as $v) {
                            $r = $this->User->register($v['email'], $v['activate'], $this->lang['name'] ?? null);

                            if (true === $r) {
                                ++$invite_count;
                            } elseif ($r) {
                                $errors[] = $r;
                            }
                        }
                    }

                    $errors = array_filter($errors);
                    if ($errors) {
                        flash(implode('<br>', $errors), 'error');
                    } elseif ($invite_count) {
                        flash(sprintf(_n('The invite email has been sent.', 'The invite emails have been sent to %d users.', $invite_count), $invite_count));
                    }

                    $this->Storage->updateUserPermissions(
                        (int) $id,
                        is_array($data['user_permissions']) ? array_map(function ($v) {
                            $v['permission_id'] = $v['permission'];
                            return $v;
                        }, array_filter($data['user_permissions'], function ($v) {
                            return empty($v['del']);
                        })) : null
                    );

                    $this->db->commit();

                    $this->StorageFileTable->createFileTable($id);

                    if (!empty($this->config['storage_log'])) {
                        StorageLog::storage_config(
                            db: $this->db,
                            storage_id: (int) $id,
                            user: $this->current_user,
                        );
                    }
                } catch (PDOException $e) {
                    flash(__('Database error.'), 'error');
                    error_log((string) $e);
                    $this->db->rollBack();
                }

                try {
                    (new StorageSearch($this->db, new Cache($this->db, $this->config)))->rebuild_index((int) $id);
                } catch (\PDOException $e) {
                    error_log((string) $e);
                }

                // Автоматическое обновление индексов Manticore
                $this->updateManticoreIndexes((int) $id);

                header('Location: '.$return_url);
                return;
            }
        }

        render_template(
            'storage_form',
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,
                'storages' => &$this->storages,

                'active_item' => $this->default_url,
                'data' => $storage_data,
                'form_data' => self::getFormData($storage_data),
                'form_errors' => $form_errors,
                'lang' => $this->lang['name'] ?? null,
                'languages' => $this->language->getLanguages(),
                'return_url' => $return_url,
                'view' => $this->setting->getByGroups(['admin_view', 'view'], $this->lang['name'] ?? null),
                'widget_data' => [
                    'attribute_types' => $this->Storage->getAttributeTypes($this->lang['name'] ?? null),
                    'storage_permissions' => $this->Storage->getStoragePermissions($this->lang['name'] ?? null),
                ],
            ],
            __DIR__.'/templates/'
        );
    }

    public function index()
    {
        $this->current_user = $this->auth->rolesRequired(['admin', 'storage_management']);

        $this->default_url = $this->config['base_url'].'/storages/';
        $this->StorageFileTable = new StorageFileTable($this->db);
        $this->Storage = new Storage($this->db);
        $this->User = new User($this->db, $this->config);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null, ['admin_autoload', 'autoload']);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $page = (int) ($_GET['p'] ?? 0);
        $page_size = (int) ($this->current_user['settings']['page_size'] ?? 0);
        $search_query = (string) ($_GET['q'] ?? '');
        $sort_desc = (int) ($_GET['desc'] ?? 0);
        $sort_idx = (int) ($_GET['sort'] ?? 0);

        if ($search_query) {
            $search_query = mb_substr($search_query, 0, 200);
        }

        if ($search_query) {
            $r = $this->db->prepare("SELECT COUNT(id) FROM storage WHERE search @@ plainto_tsquery('russian', ?)");
            $r->execute([$search_query]);
        } else {
            $r = $this->db->prepare('SELECT COUNT(id) FROM storage');
            $r->execute();
        }

        $count = $r->fetchColumn();

        $num_pages = $page_size ? ceil($count / $page_size) : 0;
        $offset = (int) max(0, min($page, $num_pages - 1) * $page_size);

        $sort_fields = [
            ['id', 'id DESC'],
            ['title', 'title DESC'],
            ['permissions.emails', 'permissions.emails DESC'],
            ['size', 'size DESC'],
        ];

        // Если сортировка по размеру, используем сортировку по ID для SQL, а потом пересортируем в PHP
        $sort_by_sql = ($sort_idx === 3) ? 'id' : (isset($sort_fields[$sort_idx]) ? $sort_fields[$sort_idx][(int) ((bool) $sort_desc)] : ($search_query ? "ts_rank(search, plainto_tsquery('russian', :search)) DESC" : 'id'));

        // Если сортировка по размеру, получаем все данные без LIMIT/OFFSET для правильной сортировки
        $limit_sql = ($sort_idx === 3) ? '' : 'LIMIT :limit OFFSET :offset';

        $r = $this->db->prepare(
            "SELECT storage.*, permissions.emails AS users
FROM storage
LEFT JOIN (
    SELECT m.storage_id, STRING_AGG(u.email, ', ' ORDER BY u.email) AS emails
    FROM storage_user_permission m, public.user u
    WHERE u.id = m.user_id
    GROUP BY m.storage_id
) permissions ON permissions.storage_id = storage.id
".($search_query ? "WHERE search @@ plainto_tsquery('russian', :search)" : '')."
ORDER BY {$sort_by_sql}
{$limit_sql}"
        );

        if ($search_query) {
            pdo_bind_param($r, ':search', $search_query);
        }

        if ($sort_idx !== 3) {
            pdo_bind($r, [
                ':limit' => $page_size,
                ':offset' => $offset,
            ]);
        }

        $r->execute();
        $data = $r->fetchAll(\PDO::FETCH_ASSOC);

        // Вычисляем размер каждого хранилища
        foreach ($data as &$row) {
            $file_table = 'file_' . (int) $row['id']; // Приводим к int для безопасности
            $table_check = $this->db->prepare('SELECT to_regclass(?)');
            $table_check->execute([$file_table]);
            
            if ($table_check->fetchColumn()) {
                // Экранируем имя таблицы двойными кавычками для PostgreSQL
                $quoted_table = '"' . str_replace('"', '""', $file_table) . '"';
                $size_query = $this->db->prepare("SELECT COALESCE(SUM(size), 0) FROM {$quoted_table} WHERE folder IS NOT TRUE");
                $size_query->execute();
                $row['size'] = (int) $size_query->fetchColumn();
            } else {
                $row['size'] = 0;
            }
        }
        unset($row);

        // Если сортировка по размеру, пересортируем данные после вычисления размера
        if ($sort_idx === 3) {
            usort($data, function ($a, $b) use ($sort_desc) {
                if ($sort_desc) {
                    return $b['size'] <=> $a['size'];
                } else {
                    return $a['size'] <=> $b['size'];
                }
            });
            // Применяем пагинацию после сортировки
            $data = array_slice($data, $offset, $page_size);
        }

        $pager_url = function ($p) use ($search_query, $sort_desc, $sort_idx) {
            return (function () {
                $v = $_SERVER['REQUEST_URI'] ?? '';
                if (($p = strpos($v, '?')) !== false) {
                    $v = substr($v, 0, $p);
                }
                return $v;
            })().'?'.http_build_query([
                'desc' => $sort_desc ? 1 : null,
                'p' => $p ?: null,
                'q' => $search_query,
                'sort' => $sort_idx,
            ]);
        };

        $sort_url = function ($column, $invert = false) use ($page, $search_query, $sort_desc) {
            return (function () {
                $v = $_SERVER['REQUEST_URI'] ?? '';
                if (($p = strpos($v, '?')) !== false) {
                    $v = substr($v, 0, $p);
                }
                return $v;
            })().'?'.http_build_query([
                'desc' => ($invert && !$sort_desc) ? 1 : null,
                'p' => $page ?: null,
                'q' => $search_query,
                'sort' => $column,
            ]);
        };

        render_template(
            'storages',
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,
                'storages' => &$this->storages,

                'clear_search_url' => (function () {
                    $v = $_SERVER['REQUEST_URI'] ?? '';
                    if (($p = strpos($v, '?')) !== false) {
                        $v = substr($v, 0, $p);
                    }
                    return $v;
                })().'?'.http_build_query([
                    'desc' => $sort_desc ? 1 : null,
                    'p' => $page ?: null,
                    'q' => null,
                    'sort' => $sort_idx,
                ]),
                'count' => $count,
                'data' => $data,
                'lang' => $this->lang['name'] ?? null,
                'languages' => $this->language->getLanguages(),
                'page' => $page,
                'page_size' => $page_size,
                'pager_url' => $pager_url,
                'return_url' => (function () {
                    $v = $_SERVER['REQUEST_URI'] ?? '';
                    if (($p = strpos($v, '?')) !== false) {
                        $v = substr($v, 0, $p);
                    }
                    return $v;
                })().'?'.http_build_query([
                    'desc' => $sort_desc ? 1 : null,
                    'p' => $page ?: null,
                    'q' => $search_query,
                    'sort' => $sort_idx,
                ]),
                'search_query' => $search_query,
                'view' => $this->setting->getByGroups(['admin_view', 'view'], $this->lang['name'] ?? null),
                'sort_desc' => $sort_desc,
                'sort' => $sort_idx,
                'sort_url' => $sort_url,
            ],
            __DIR__.'/templates/'
        );
    }

    protected function form_validate(&$data, &$form_errors, $id = null)
    {
        if (empty($data['title'])) {
            $form_errors['title'] = __('Enter the name of the file storage');
        }

        if (!empty($data['attributes']) && is_array($data['attributes'])) {
            foreach ($data['attributes'] as $i => $v) {
                if (!empty($v['del'])) {
                    continue;
                }

                if (empty($v['title'])) {
                    $form_errors[sprintf('attributes-%d-title', $i)] = __('Enter the name of the attribute');
                }
            }
        }

        if (!empty($data['user_permissions']) && is_array($data['user_permissions'])) {
            foreach ($data['user_permissions'] as $i => $v) {
                if (empty($v['email'])) {
                    $form_errors[sprintf('user_permissions-%d-email', $i)] = __('Enter User Email');
                } elseif (strlen($v['email']) < (int) $this->config['auth']['verify_email_min_length']) {
                    $form_errors[sprintf('user_permissions-%d-email', $i)] = __('Email is too short');
                } elseif (strlen($v['email']) > (int) $this->config['auth']['verify_email_max_length']) {
                    $form_errors[sprintf('user_permissions-%d-email', $i)] = __('Email is too long');
                }
            }
        }
    }

    protected static function getFormData($v)
    {
        $rv = Form::getFormData([
            'description',
            'title',
        ], $v);

        $rv['attributes'] = Form::getInlineFormData('attributes', [
            'filter',
            'sort',
            'title',
            'type',
        ], $v);

        $rv['user_permissions'] = Form::getInlineFormData('user_permissions', [
            'email',
            'isactive',
            'permission',
        ], $v);

        return $rv;
    }

    protected static function filterInput()
    {
        $rv = Form::filterInput([
            'description' => null,
            'title' => null,
        ]);

        $rv['attributes'] = Form::filterInlineFormInput('attributes', [
            'del' => FILTER_VALIDATE_BOOLEAN,
            'filter' => FILTER_VALIDATE_BOOLEAN,
            'id' => FILTER_VALIDATE_INT,
            'sort' => FILTER_VALIDATE_INT,
            'title' => null,
            'type' => FILTER_VALIDATE_INT,
        ]);

        $rv['user_permissions'] = Form::filterInlineFormInput('user_permissions', [
            'activate' => FILTER_VALIDATE_BOOLEAN,
            'del' => FILTER_VALIDATE_BOOLEAN,
            'email' => null,
            'permission' => FILTER_VALIDATE_INT,
        ]);

        return $rv;
    }

    /**
     * Автоматическое обновление конфигурации Manticore и создание индексов
     * Вызывается после создания или обновления хранилища
     */
    /**
     * Автоматическое обновление конфигурации Manticore и создание индексов
     * @param int|null $storage_id ID хранилища для индексации конкретного индекса (если null - индексируются все)
     */
    protected function updateManticoreIndexes(?int $storage_id = null): void
    {
        $project_dir = dirname(__DIR__);
        $config_script = $project_dir . '/doc/manticore.conf.debian.sample';
        $manticore_config = '/etc/manticoresearch/manticore.conf';

        if (!file_exists($config_script)) {
            error_log("Manticore config script not found: {$config_script}");
            return;
        }

        try {
            // Генерируем конфигурацию Manticore
            $output = [];
            $return_var = 0;
            exec("php " . escapeshellarg($config_script) . " 2>&1", $output, $return_var);

            if ($return_var !== 0) {
                error_log("Failed to generate Manticore config: " . implode("\n", $output));
                return;
            }

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
            ];

            // Небольшая задержка перед индексацией, чтобы данные успели сохраниться в БД
            if ($storage_id !== null) {
                sleep(2);
            }

            $commands[] = [
                'cmd' => "su - manticore -s /bin/bash -c " . escapeshellarg(
                    $storage_id !== null 
                        ? "indexer file_{$storage_id}_filter file_{$storage_id}_main --rotate"
                        : "indexer --all --rotate"
                ),
                'use_sudo' => true,
                'ignore_no_tables' => true, // Игнорируем ошибку "no tables found"
            ];

            $commands[] = [
                'cmd' => "systemctl restart manticore",
                'use_sudo' => true,
            ];

            // Выполняем команды
            foreach ($commands as $cmd_data) {
                $cmd = $cmd_data['cmd'];
                $output = [];
                $return_var = 0;

                // Пробуем сначала через sudo, если не получится - напрямую
                if (!empty($cmd_data['use_sudo'])) {
                    exec("sudo " . $cmd . " 2>&1", $output, $return_var);
                }
                
                if ($return_var !== 0) {
                    // Если sudo не сработал, пробуем напрямую (для случая, когда PHP запущен от root)
                    exec($cmd . " 2>&1", $output, $return_var);
                }

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
        } catch (\Exception $e) {
            error_log("Error updating Manticore indexes: " . $e->getMessage());
        }
    }
}

