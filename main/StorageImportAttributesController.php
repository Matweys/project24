<?php

declare(strict_types=1);

namespace Main;

use Helpers\Form;

class StorageImportAttributesController extends BaseController
{
    protected $storage;

    public function index($storage_uid = null, $folder_id = null)
    {
        $this->current_user = $this->auth->loginRequired();
        $this->Storage = new Storage($this->db);
        $this->StorageImportAttributes = new StorageImportAttributes($this->db);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null);

        $storage = $this->Storage->getStorage(null, $storage_uid, $this->current_user['id']);
        $file_table = $storage ? $this->Storage->getFileTable($storage['id']) : null;

        if (!$storage || !$file_table || !in_array($storage['permission_name'] ?? null, ['full'])) {
            http_response_code(404);
            return;
        }

        $folder_data = null;

        if ((int) $folder_id) {
            $r = $this->db->prepare("SELECT * FROM {$file_table} WHERE id = ? AND folder IS TRUE");
            $r->execute([(int) $folder_id]);
            $folder_data = $r->fetch(\PDO::FETCH_ASSOC);

            if (!$folder_data) {
                http_response_code(404);
                return;
            }
        }

        $return_url = get_redirect_target() ?: $this->config['base_url'].'/storage/'.$storage['uid'].'/';

        $form_errors = [];

        $data = Form::filterInput([
            'csv_delimiter' => null,
        ]);

        if (isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD']) {
            list($form_errors['file'], $filename, $mime_type, $uploaded_filename) = StorageImportAttributes::upload('file');

            if (!$filename) {
                $form_errors['file'] = __('Select file for import attributes.');
            }

            if ('text/plain' === $mime_type && empty($data['csv_delimiter'])) {
                $form_errors['csv_delimiter'] = __('Enter CSV delimiter.');
            }

            if (!array_filter($form_errors)) {
                $_SESSION['import_files_attributes'] = [
                    'csv_delimiter' => $data['csv_delimiter'],
                    'filename' => $filename,
                    'mime_type' => $mime_type,
                    'uploaded_filename' => $uploaded_filename,
                ];

                header('Location: '.$this->config['base_url'].'/storage/import_attributes_columns/'.$storage['uid'].'/'.($folder_data ? $folder_data['id'].'/' : '').'?'.http_build_query(['url' => $return_url]));
                return;
            }

            StorageImportAttributes::deleteTempFiles(['file']);
        }

        render_template(
            'import_attributes',
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,
                'storages' => &$this->storages,

                'active_item' => '/storage/'.$storage['uid'].'/',
                'breadcrumbs_data' => $this->Storage->getBreadcrubms($file_table, $folder_data['id'] ?? null),
                'form_data' => Form::getFormData(
                    [
                        'csv_delimiter',
                    ],
                    [
                        'csv_delimiter' => ';',
                    ]
                ),
                'form_errors' => $form_errors,
                'lang' => $this->lang['name'] ?? null,
                'languages' => $this->language->getLanguages(),
                'return_url' => $return_url,
                'storage' => $storage,
                'view' => $this->setting->getByGroups(['view'], $this->lang['name'] ?? null),
            ],
            __DIR__.'/templates/'
        );
    }

    public function columns($storage_uid = null, $folder_id = null)
    {
        $this->current_user = $this->auth->loginRequired();
        $this->Storage = new Storage($this->db);
        $this->StorageImportAttributes = new StorageImportAttributes($this->db);
        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null);

        $storage = $this->Storage->getStorage(null, $storage_uid, $this->current_user['id']);
        $file_table = $storage ? $this->Storage->getFileTable($storage['id']) : null;

        if (!$storage || !$file_table || !in_array($storage['permission_name'] ?? null, ['full'])) {
            http_response_code(404);
            return;
        }

        $folder_data = null;

        if ((int) $folder_id) {
            $r = $this->db->prepare("SELECT * FROM {$file_table} WHERE id = ? AND folder IS TRUE");
            $r->execute([(int) $folder_id]);
            $folder_data = $r->fetch(\PDO::FETCH_ASSOC);

            if (!$folder_data) {
                http_response_code(404);
                return;
            }
        }

        $return_url = get_redirect_target() ?: $this->config['base_url'].'/storage/'.$storage['uid'].'/';

        $form_errors = [];

        if (empty($_SESSION['import_files_attributes']['filename']) || !is_file($_SESSION['import_files_attributes']['filename'])) {
            header('Location: '.$this->config['base_url'].'/storage/import_attributes/'.$storage['uid'].'/'.($folder_data ? $folder_data['id'].'/' : '').'?'.http_build_query(['url' => $return_url]));
            return;
        }

        $data = Form::filterInput([
            'basename_match' => FILTER_VALIDATE_BOOLEAN,
        ]);

        $data['columns_attributes'] = Form::filterInlineFormInput('columns_attributes', [
            'attribute' => null,
        ]);

        if (isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD']) {
            $columns_attributes = array_column(($data['columns_attributes'] ?? []) ?: [], 'attribute');

            if (count(array_unique(array_filter($columns_attributes))) < 2 || !in_array('f', $columns_attributes)) {
                $form_errors[''] = __('Select match of the columns to the attributes of the File Storage.');
            }

            if (!$form_errors) {
                $result = null;

                try {
                    $this->db->beginTransaction();

                    $result = $this->StorageImportAttributes->importAttributes(
                        basename_match: (bool) ($data['basename_match'] ?? false),
                        columns_attributes: $columns_attributes,
                        delimiter: $_SESSION['import_files_attributes']['csv_delimiter'] ?? null,
                        enclosure: '"',
                        file_table: $file_table,
                        filename: $_SESSION['import_files_attributes']['filename'] ?? null,
                        folder_id: $folder_data['id'] ?? null,
                        source: $_SESSION['import_files_attributes']['uploaded_filename'] ?? '',
                        storage: $storage,
                        storage_log: !empty($this->config['storage_log']),
                        user: $this->current_user,
                    );

                    $this->db->commit();
                } catch (\PDOException $e) {
                    error_log((string) $e);
                    $this->db->rollBack();
                    flash(__('Database error.'), 'error');
                    header('Location: '.$return_url);
                    return;
                } catch (StorageImportAttributesException $e) {
                    error_log((string) $e);
                    $this->db->rollBack();
                    flash(__('Failed to import attributes.'), 'error');
                    header('Location: '.$return_url);
                    return;
                }

                if (is_file($_SESSION['import_files_attributes']['filename'] ?? null)) {
                    try {
                        unlink($_SESSION['import_files_attributes']['filename']);
                    } catch (\Exception $e) {
                    }
                }

                unset($_SESSION['import_files_attributes']);

                try {
                    (new StorageSearch($this->db, new Cache($this->db, $this->config)))->rebuild_index($storage['id']);
                } catch (\PDOException $e) {
                    error_log((string) $e);
                }

                flash(sprintf(__('Import completed successfully. Total rows processed: %d. Affected file records: %d. Attributes with errors: %d.'), $result[0] ?? 0, $result[1] ?? 0, $result[2] ?? 0));
                header('Location: '.$return_url);
                return;
            }
        }

        $sample_data = $this->StorageImportAttributes->loadImportData(
            (string) ($_SESSION['import_files_attributes']['filename'] ?? ''),
            (string) ($_SESSION['import_files_attributes']['csv_delimiter'] ?? ''),
            '"',
            20
        );

        $imported_data = [];
        foreach (($sample_data[0] ?? []) as $i => $column_title) {
            $column_title = trim(mb_strtolower($column_title));

            if ('file' === $column_title || 'filename' === $column_title || 'имя файла' === $column_title || 'файл' === $column_title) {
                $imported_data['columns_attributes'][$i]['attribute'] = 'f';
            } else {
                foreach ($storage['attributes'] ?? [] as $attribute) {
                    if ($column_title === trim(mb_strtolower($attribute['title'] ?? ''))) {
                        $imported_data['columns_attributes'][$i]['attribute'] = (string) ($attribute['id'] ?? '');
                        break;
                    }
                }
            }
        }

        render_template(
            'import_attributes_columns',
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,
                'storages' => &$this->storages,

                'active_item' => '/storage/'.$storage['uid'].'/',
                'breadcrumbs_data' => $this->Storage->getBreadcrubms($file_table, $folder_data['id'] ?? null),
                'columns_storage_attributes' => ['f' => __('Filename')] + array_reduce(
                    array_keys($storage['attributes'] ?? []),
                    function ($r, $k) use ($storage) {
                        $v = $storage['attributes'][$k] ?? null;
                        $r[$v['id']] = $v['title'];
                        return $r;
                    },
                    []
                ),
                'form_data' => array_merge(
                    Form::getFormData(
                        [
                            'csv_delimiter',
                        ],
                        [
                            'csv_delimiter' => ';',
                        ]
                    ),
                    [
                        'columns_attributes' => Form::getInlineFormData(
                            'columns_attributes',
                            [
                                'attribute',
                            ],
                            $imported_data,
                        ),
                    ]
                ),
                'form_errors' => $form_errors,
                'lang' => $this->lang['name'] ?? null,
                'languages' => $this->language->getLanguages(),
                'return_url' => $return_url,
                'sample_data' => $sample_data,
                'storage' => $storage,
                'view' => $this->setting->getByGroups(['view'], $this->lang['name'] ?? null),
            ],
            __DIR__.'/templates/'
        );
    }
}
