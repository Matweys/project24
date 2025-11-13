<?php

declare(strict_types=1);

namespace Auth;

use URLSafeTimedSerializer\URLSafeTimedSerializer;
use ZxcvbnPhp\Zxcvbn;

class ElseException extends \Exception {}

class AuthController
{
    protected $auth;
    protected $config;
    protected $current_user;
    protected $db;

    public function __construct(?array $config)
    {
        $this->config = &$config;

        try {
            $this->db = new \PDO($this->config['database_uri'] ?? null, options: [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        } catch (\PDOException $e) {
            error_log((string) $e);
            exit('Ошибка базы данных');
        }

        $this->auth = new \Auth\Auth($this->db, new \Auth\Config($this->db, $config['auth'], \Auth\Config::CONFIG_TYPE_ARRAY, 'ru_RU'));
    }

    public function activate($key = null)
    {
        session_start();

        if (!$key) {
            header('Location: '.$this->config['base_url'].'/');
            return;
        }

        $language = new Language($this->db);
        $lang = $language->getCurrentLanguage();

        setlocale(LC_CTYPE, 'en_US.UTF-8');

        if (($lang['name'] ?? null) === 'ru') {
            load_textdomain('default', __DIR__.'/languages/auth_ru.mo');
        }

        $csrf_id = 'auth_activate';
        $error = null;
        $user_data = null;
        $user_id = null;

        if ($key) {
            try {
                $user_id = (new URLSafeTimedSerializer($this->config['user_activation_key'], (int) $this->config['user_activation_key_expire']))->load($key, false);
            } catch (Exception $e) {
                error_log((string) $e);
            }
        }

        if ($user_id) {
            try {
                $r = $this->db->prepare("SELECT * FROM {$this->config['auth']['table_users']} WHERE id = ?");
                $r->execute([$user_id]);
                $user_data = $r->fetch(\PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                flash(__('Database error.'), 'error');
                error_log((string) $e);
            }
        }

        if (!$user_data) {
            flash(__('Invalid account activation link'), 'error');
            header('Location: '.$this->config['base_url'].'/');
            return;
        }

        if (!empty($user_data['isactive']) && !validate_csrf_token($this->config, $csrf_id)) {
            flash(__('Account is already activated.'));
            header('Location: '.$this->config['base_url'].'/login');
            return;
        }

        if (isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD']) {
            $password = $_POST['password'] ?? '';

            if (!validate_csrf_token($this->config, $csrf_id)) {
                $error = __('Session error');
            } elseif (strlen($password) < (int) $this->config['auth']['verify_password_min_length']) {
                $error = __('Password is too short.');
            } elseif ((new Zxcvbn())->passwordStrength($password)['score'] < (int) $this->config['auth']['password_min_score']) {
                $error = __('Password is too weak.');
            }

            if (!$error) {
                try {
                    try {
                        $this->db->beginTransaction();

                        $r = $this->db->prepare("UPDATE {$this->config['auth']['table_users']} SET password = ?, modified = current_timestamp WHERE id = ? AND isactive IS TRUE");
                        $r->execute([$this->auth->getHash($password), $user_data['id']]);

                        $this->db->commit();
                    } catch (PDOException $e) {
                        flash(__('Database error.'), 'error');
                        error_log((string) $e);
                        throw new ElseException();
                    }

                    if ($r->rowCount()) {
                        $this->auth->login($user_data['email'] ?? '', $password, 1);
                    }

                    flash(__('Password has been change.'));
                    header('Location: '.$this->config['base_url'].'/');
                    return;
                } catch (ElseException $e) {
                }
            }
        }

        try {
            try {
                $this->db->beginTransaction();

                $r = $this->db->prepare("UPDATE {$this->config['auth']['table_users']} SET isactive = true, modified = current_timestamp WHERE id = ? AND isactive IS NOT TRUE");
                $r->execute([$user_data['id']]);

                $this->db->commit();
            } catch (PDOException $e) {
                flash(__('Database error.'), 'error');
                error_log((string) $e);
                throw new ElseException();
            }

            if ($r->rowCount()) {
                flash(sprintf(
                    __('Account %s has been activated. Please set a password for your account. You may set the password later by following the link <a href="%s">Forgot your password</a>.'),
                    $user_data['email'],
                    $this->config['base_url'].'/forgot_password'
                ));
            }
        } catch (ElseException $e) {
        }

        return $this->render(
            'reset_password',
            [
                'csrf_id' => $csrf_id,
                'data' => [
                    'message' => $error,
                ],
                'lang' => $lang['name'] ?? null,
                'languages' => $language->getLanguages(),
            ],
            __DIR__.'/templates/'
        );
    }

    public function login()
    {
        session_start();

        $language = new Language($this->db);
        $lang = $language->getCurrentLanguage();

        setlocale(LC_CTYPE, 'en_US.UTF-8');

        if (($lang['name'] ?? null) === 'ru') {
            load_textdomain('default', __DIR__.'/languages/auth_ru.mo');
        }

        $csrf_id = 'auth_login';
        $data = null;

        $password = $_POST['password'] ?? '';
        $remember = (bool) ($_POST['remember'] ?? false);
        $username = $_POST['username'] ?? '';

        $next_url = get_redirect_target('next');

        if ($this->auth->isLogged()) {
            header('Location: '.($next_url ?: $this->config['auth']['login_url'] ?: '/'));
            return;
        }

        if (isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD']) {
            if (!form_csrf_validate($this->config, $csrf_id)) {
                $data['error'] = true;
                $data['message'] = __('Session error');
            } else {
                $data = $this->auth->login($username, (string) $password, (int) $remember);

                if (empty($data['error'])) {
                    header('Location: '.($next_url ?: $this->config['auth']['login_url'] ?: '/'));
                    return;
                }
            }
        } else {
            $remember = true;
        }

        render_template(
            'login',
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,

                'csrf_id' => $csrf_id,
                'data' => $data,
                'lang' => $lang['name'] ?? null,
                'languages' => $language->getLanguages(),
                'next_url' => $next_url,
                'remember' => $remember,
                'username' => $username,
            ],
            __DIR__.'/templates/'
        );
    }

    public function logout()
    {
        if ($this->auth->isLogged()) {
            $this->auth->logout($this->auth->getCurrentSessionHash());
        }

        header('Location: '.($this->config['auth']['logout_url'] ?: '/'));
    }

    public function forgotPassword()
    {
        session_start();

        $language = new Language($this->db);
        $lang = $language->getCurrentLanguage();

        setlocale(LC_CTYPE, 'en_US.UTF-8');

        if (($lang['name'] ?? null) === 'ru') {
            load_textdomain('default', __DIR__.'/languages/auth_ru.mo');
        }

        $csrf_id = 'auth_forgot_password';
        $data = null;
        $email = null;

        if (isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD']) {
            $email = $_POST['email'] ?? '';

            if (!form_csrf_validate($this->config, $csrf_id)) {
                $data['error'] = true;
                $data['message'] = __('Session error');
            } else {
                $data = $this->auth->requestReset($email, true);
            }
        }

        if (empty($data['error']) && !empty($data['message'])) {
            flash($data['message']);
            $data = null;
        }

        render_template(
            'forgot_password',
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,

                'csrf_id' => $csrf_id,
                'data' => $data,
                'email' => $email,
                'lang' => $lang['name'] ?? null,
                'languages' => $language->getLanguages(),
            ],
            __DIR__.'/templates/'
        );
    }

    public function resetPassword($key = null)
    {
        session_start();

        $language = new Language($this->db);
        $lang = $language->getCurrentLanguage();

        setlocale(LC_CTYPE, 'en_US.UTF-8');

        if (($lang['name'] ?? null) === 'ru') {
            load_textdomain('default', __DIR__.'/languages/auth_ru.mo');
        }

        $csrf_id = 'auth_reset_password';
        $data = null;

        if (!$key) {
            header('Location: '.$this->config['base_url'].'/forgot_password');
            return;
        }

        $request = $this->auth->getRequest($key, 'reset');

        if (!empty($request['error'])) {
            if (!empty($request['message'])) {
                flash($request['message'], 'error');
            }

            header('Location: '.$this->config['base_url'].'/forgot_password');
            return;
        }

        if (isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD']) {
            $password = $_POST['password'] ?? '';

            if (!form_csrf_validate($this->config, $csrf_id)) {
                $data['error'] = true;
                $data['message'] = __('Session error');
            } else {
                $data = $this->auth->resetPass($key, $password, $password);

                if (empty($data['error'])) {
                    if (!empty($data['message'])) {
                        flash($data['message']);
                    }

                    if ((int) ($request['uid'] ?? 0)) {
                        $this->auth->logoutAll((int) $request['uid']);

                        $user = $this->auth->getUser((int) $request['uid']);

                        if (!empty($user['email']) && $password) {
                            $data = $this->auth->login($user['email'] ?? '', $password);
                        }

                        if (empty($data['error'])) {
                            header('Location: '.($this->config['auth']['login_url'] ?: '/'));
                        }
                    }

                    header('Location: '.($this->config['auth']['login_url'] ?: '/'));
                    return;
                }
            }
        }

        render_template(
            'reset_password',
            [
                'config' => &$this->config,
                'current_user' => &$this->current_user,

                'csrf_id' => $csrf_id,
                'data' => $data,
                'lang' => $lang['name'] ?? null,
                'languages' => $language->getLanguages(),
            ],
            __DIR__.'/templates/'
        );
    }
}
