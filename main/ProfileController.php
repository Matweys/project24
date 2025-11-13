<?php

declare(strict_types=1);

namespace Main;

use Helpers\Form;
use Main\BaseController;
use Main\ElseException;
use Main\Language;
use Main\Storage;
use ZxcvbnPhp\Zxcvbn;

class ProfileController extends BaseController
{
    protected $fields;

    public function index()
    {
        $this->current_user = $this->auth->loginRequired();

        $this->Storage = new Storage($this->db);

        $this->fields = [
            'file_form_pdf_preview' => ['title' => __('Show the PDF viewer on the file attribute edit page'), 'filter' => FILTER_VALIDATE_BOOLEAN],
            'page_size' => ['title' => __('Number of items per page'), 'filter' => [FILTER_VALIDATE_INT, ['min_range' => 1]]],
        ];

        $this->storages = $this->Storage->getAllowedStorages($this->current_user['id']);

        $this->lang = $this->language->getCurrentLanguage($this->current_user['id']);
        $this->load_translation($this->lang['name'] ?? null);
        $this->setting->load($this->lang['name'] ?? null);

        $next_url = get_redirect_target('next') ?: $this->config['auth']['login_url'] ?: '/';

        $user_data = null;

        try {
            $r = $this->db->prepare("select * from {$this->config['auth']['table_users']} where id = ?");
            $r->execute([$this->current_user['id']]);
            $user_data = $r->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            flash(__('Database error.'), 'error');
            error_log((string) $e);
            header('Location: ' . $next_url);
            return;
        }

        if (! $user_data) {
            header('Location: ' . $next_url);
            return;
        }

        $user_data['lang'] = $user_data['language_id'];
        $user_data['settings'] = json_decode(($user_data['settings'] ?? '') ?: '', true);

        $form_errors = [];

        $form_input = Form::filterInput(array_reduce(
            array_keys($this->fields),
            function ($r, $k) {
                if (isset($this->fields[$k]['filter'])) {
                    $r[$k] = $this->fields[$k]['filter'] ?? '';
                }
                return $r;
            },
            [
                'lang' => null,
                'name' => null,
                'new_password' => null,
                'password' => null,
            ]
        ));

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!empty($form_input['password']) || !empty($form_input['new_password'])) {
                $v = $this->auth->login($this->current_user['email'], $form_input['password']);

                if (!empty($v['error']) && !empty($v['message'])) {
                    $form_errors['password'] = $v['message'];
                } elseif (strlen($form_input['password']) < (int) $this->config['auth']['verify_password_min_length']) {
                    $form_errors['password'] = 'Пароль слишком короткий.';
                } elseif (strlen($form_input['new_password']) < (int) $this->config['auth']['verify_password_min_length']) {
                    $form_errors['new_password'] = 'Пароль слишком короткий.';
                } elseif ((new Zxcvbn())->passwordStrength($form_input['new_password'])['score'] < (int) $this->config['auth']['password_min_score']) {
                    $form_errors['new_password'] = 'Пароль слишком ненадежный.';
                }
            }

            Form::validateFormFields($this->fields, $form_input, $form_errors);

            if (!$form_errors) {
                $settings = array_reduce(
                    array_keys($this->fields),
                    function ($r, $k) use ($form_input) {
                        $r[$k] = $form_input[$k] ?? '';
                        return $r;
                    },
                    []
                );

                try {
                    try {
                        $this->db->beginTransaction();

                        $r = $this->db->prepare(
                            "update {$this->config['auth']['table_users']}
set
    language_id = :lang,
    modified = now(),
    name = :name,
    settings = :settings
where id = :id"
                        );
                        $r->execute([
                            ':id' => $this->current_user['id'],
                            ':lang' => $form_input['lang'] ?: null,
                            ':name' => $form_input['name'] ?: null,
                            ':settings' => json_encode($settings),
                        ]);

                        if ($form_input['password']) {
                            $r = $this->db->prepare("update {$this->config['auth']['table_users']} set password = ? where id = ?");
                            $r->execute([\Auth\Auth::getHash($form_input['new_password'], $this->config['auth']['bcrypt_cost']), $this->current_user['id']]);
                        }
                        $this->db->commit();
                    } catch (\PDOException $e) {
                        flash(__('Database error.'), 'error');
                        error_log((string) $e);
                        $this->db->rollBack();
                        throw new ElseException();
                    }

                    if ($form_input['password']) {
                        flash(__('Password has been changed.'));
                    } else {
                        flash(__('Profile has been saved.'));
                    }

                    if ($form_input['password']) {
                        $this->auth->logoutAll((int) $this->current_user['id']);
                        $this->auth->login($this->current_user['email'] ?? '', $form_input['new_password']);
                    }

                    if ($form_input['lang'] !== $user_data['lang']) {
                        setcookie(
                            'lang',
                            ((new Language($this->db))->getLanguage($form_input['lang']))['name'],
                            time() + 3600 * 24 * 31,
                            '/',
                            '',
                            false,
                            false
                        );
                    }

                    header('Location: ' . $next_url);
                    return;
                } catch (ElseException $e) {
                }
            }
        }

        render_template(
            'profile',
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,
                'storages' => &$this->storages,

                'form_data' => (function ($v) {
                    $rv = Form::getFormData(array_merge([
                        'lang',
                        'name',
                    ], array_keys($this->fields)), $v);
                    if (!isset($rv['lang'])) {
                        $rv['lang'] = ((new Language($this->db))->getCurrentLanguage())['id'] ?? null;
                    }
                    return $rv;
                })(array_merge($this->config['auth']['default_user_settings'], $user_data, is_array($user_data['settings']) ? $user_data['settings'] : [])),

                'form_errors' => $form_errors,
                'lang' => $this->lang['name'] ?? null,
                'languages' => $this->language->getLanguages(),
                'next_url' => $next_url,
                'view' => $this->setting->getByGroups(['view'], $this->lang['name'] ?? null),
            ],
            __DIR__ . '/templates/'
        );
    }
}
