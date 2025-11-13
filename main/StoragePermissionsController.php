<?php

declare(strict_types=1);

namespace Main;

use Helpers\Form;

class StoragePermissionsController extends BaseController
{
    protected $storage;

    public function index($storage_uid = null)
    {
        $this->current_user = $this->auth->loginRequired();
        $this->StorageFileTable = new StorageFileTable($this->db);
        $this->Storage = new Storage($this->db);
        $this->User = new User($this->db, $this->config);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null);

        $storage = $this->Storage->getStorage(null, $storage_uid, $this->current_user['id']);
        $file_table = $storage ? $this->Storage->getFileTable($storage['id']) : null;

        if (!$storage || !$file_table || ($storage['permission_name'] ?? null) !== 'full') {
            http_response_code(404);
            return;
        }

        $return_url = get_redirect_target() ?: $this->config['base_url'].'/storage/'.$storage['uid'].'/';

        $storage_data = null;

        try {
            $r = $this->db->prepare('SELECT * FROM storage WHERE id = ?');
            $r->execute([$storage['id']]);
            $storage_data = $r->fetch(\PDO::FETCH_ASSOC);

            if ($storage_data) {
                $storage_data['user_permissions'] = array_values(array_filter(
                    $this->Storage->getUserPermissions($storage['id'], $this->lang['name'] ?? null) ?: [],
                    function ($v) {
                        return ($v['email'] ?? null) != $this->current_user['email'];
                    }
                ));
            }
        } catch (\PDOException $e) {
            flash(__('Database error.'), 'error');
            error_log((string) $e);
            header('Location: '.$return_url);
            return;
        }

        if (!$storage_data) {
            http_response_code(404);
            return;
        }

        $form_errors = [];

        $data = Form::filterInput([
            'title' => null,
        ]);

        $data['user_permissions'] = Form::filterInlineFormInput('user_permissions', [
            'activate' => FILTER_VALIDATE_BOOLEAN,
            'del' => FILTER_VALIDATE_BOOLEAN,
            'email' => null,
            'permission' => FILTER_VALIDATE_INT,
        ]);

        if (isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD']) {
            if (!empty($data['user_permissions']) && is_array($data['user_permissions'])) {
                foreach ($data['user_permissions'] as $i => $v) {
                    if (empty($v['email'])) {
                        $form_errors[sprintf('user_permissions-%d-email', $i)] = __('Enter User Email.');
                    } elseif (strlen($v['email']) < (int) $this->config['auth']['verify_email_min_length']) {
                        $form_errors[sprintf('user_permissions-%d-email', $i)] = __('Email is too short.');
                    } elseif (strlen($v['email']) > (int) $this->config['auth']['verify_email_max_length']) {
                        $form_errors[sprintf('user_permissions-%d-email', $i)] = __('Email is too long.');
                    }
                }
            }

            if (!$form_errors) {
                try {
                    $this->db->beginTransaction();

                    $r = $this->db->prepare(
                        'UPDATE storage
SET
    modified = NOW()
WHERE id = :id'
                    );
                    $r->execute([
                        ':id' => $storage['id'],
                    ]);

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
                        (int) $storage['id'],
                        is_array($data['user_permissions']) ? array_map(function ($v) {
                            $v['permission_id'] = $v['permission'];
                            return $v;
                        }, array_filter($data['user_permissions'], function ($v) {
                            return empty($v['del']);
                        })) : null,
                        [$this->current_user['email']]
                    );

                    $this->db->commit();

                    $this->StorageFileTable->createFileTable($storage['id']);
                } catch (\PDOException $e) {
                    flash(__('Database error.'), 'error');
                    error_log((string) $e);
                    $this->db->rollBack();
                }

                if (!empty($this->config['storage_log'])) {
                    StorageLog::storage_config(
                        db: $this->db,
                        storage_id: $storage['id'],
                        user: $this->current_user,
                    );
                }

                header('Location: '.$return_url);
                return;
            }
        }

        render_template(
            'storage_permissions',
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,
                'storages' => &$this->storages,

                'data' => $storage_data,
                'form_data' => array_merge(
                    Form::getFormData(
                        [
                            'title',
                        ],
                        $storage_data
                    ),
                    [
                        'user_permissions' => Form::getInlineFormData(
                            'user_permissions',
                            [
                                'email',
                                'isactive',
                                'permission',
                            ],
                            $storage_data
                        ),
                    ]
                ),
                'form_errors' => $form_errors,
                'lang' => $this->lang['name'] ?? null,
                'languages' => $this->language->getLanguages(),
                'return_url' => $return_url,
                'storage' => $storage,
                'widget_data' => [
                    'storage_permissions' => $this->Storage->getStoragePermissions($this->lang['name'] ?? null),
                ],
                'view' => $this->setting->getByGroups(['view'], $this->lang['name'] ?? null),
            ],
            __DIR__.'/templates/'
        );
    }
}
