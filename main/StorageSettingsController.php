<?php

declare(strict_types=1);

namespace Main;

use Helpers\Form;

class StorageSettingsController extends BaseController
{
    protected $storage;

    public function index($storage_uid = null)
    {
        $this->current_user = $this->auth->loginRequired();
        $this->StorageFileTable = new StorageFileTable($this->db);
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

        $return_url = get_redirect_target() ?: $this->config['base_url'].'/storage/'.$storage['uid'].'/';

        $storage_data = null;

        try {
            $r = $this->db->prepare('SELECT * FROM storage WHERE id = ?');
            $r->execute([$storage['id']]);
            $storage_data = $r->fetch(\PDO::FETCH_ASSOC);

            if ($storage_data) {
                $storage_data['attributes'] = $this->Storage->getAttributes($storage['id']);
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

        $data['attributes'] = Form::filterInlineFormInput('attributes', [
            'del' => FILTER_VALIDATE_BOOLEAN,
            'filter' => FILTER_VALIDATE_BOOLEAN,
            'id' => FILTER_VALIDATE_INT,
            'sort' => FILTER_VALIDATE_INT,
            'title' => null,
            'type' => FILTER_VALIDATE_INT,
        ]);

        if (isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD']) {
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

            if (!$form_errors) {
                try {
                    $this->db->beginTransaction();

                    $r = $this->db->prepare(
                        'UPDATE storage
SET
    modified = NOW(),
    title = :title
WHERE id = :id'
                    );
                    $r->execute([
                        ':id' => $storage['id'],
                        ':title' => $data['title'],
                    ]);

                    $this->Storage->updateAttributes(
                        (int) $storage['id'],
                        $data['attributes']
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
            'storage_settings',
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
                        'attributes' => Form::getInlineFormData(
                            'attributes',
                            [
                                'filter',
                                'sort',
                                'title',
                                'type',
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
                    'attribute_types' => $this->Storage->getAttributeTypes($this->lang['name'] ?? null),
                ],
                'view' => $this->setting->getByGroups(['view'], $this->lang['name'] ?? null),
            ],
            __DIR__.'/templates/'
        );
    }
}
