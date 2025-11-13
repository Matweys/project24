<?php

declare(strict_types=1);

namespace Auth;

class Auth extends \PHPAuth\Auth
{
    /**
     * Gets public user data for a given UID and returns an array, password will be returned if param $withpassword is TRUE.
     *
     * @param bool|false $withpassword
     *
     * @return array $data
     */
    public function getUser(int $uid, bool $withpassword = false): ?array
    {
        if ($uid) {
            try {
                $r = $this->dbh->prepare("SELECT * FROM {$this->config->table_users} WHERE id = ?");
                $r->execute([$uid]);
                $data = $r->fetch(\PDO::FETCH_ASSOC);

                if ($data) {
                    $r = $this->dbh->prepare("SELECT r.name FROM {$this->config->table_roles_users} m, {$this->config->table_roles} r WHERE m.user_id = ? AND r.id = m.role_id");
                    $r->execute([$uid]);
                    $data['role'] = $r->fetchAll(\PDO::FETCH_COLUMN) ?: [];
                }
            } catch (PDOException $e) {
                error_log((string) $e);
                return null;
            }

            if ($data) {
                $data['uid'] = $uid;

                if (!$withpassword) {
                    unset($data['password']);
                }

                $v = json_decode(($data['settings'] ?? '') ?: '', true);
                $data['settings'] = array_merge($this->config->default_user_settings, is_array($v) ? $v : []);
                return $data;
            }
        }
    }

    public function loginRequired($login_url = '/login')
    {
        $user = $this->isLogged() ? $this->getCurrentUser() : null;

        if (!$user) {
            if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
                http_response_code(401);
            } else {
                header('Location: ' . $this->config->base_url . $login_url);
            }

            exit;
        }

        return $user;
    }

    public function rolesAccepted($roles, $login_url = '/login')
    {
        $user = $this->isLogged() ? $this->getCurrentUser() : null;

        if (!$user || !isset($user['role']) || !is_array($user['role'])) {
            if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
                http_response_code(401);
            } else {
                header('Location: ' . $this->config->base_url . $login_url);
            }
            exit;
        }

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        foreach ($roles as $v) {
            if ($v && in_array($v, $user['role'])) {
                return $user;
            }
        }

        http_response_code(401);
        exit;
    }

    public function rolesRequired($roles, $login_url = '/login')
    {
        $user = $this->isLogged() ? $this->getCurrentUser() : null;

        if (!$user || !isset($user['role']) || !is_array($user['role'])) {
            if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
                http_response_code(401);
            } else {
                header('Location: ' . $this->config->base_url . $login_url);
            }
            exit;
        }

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        foreach ($roles as $v) {
            if (!$v || !in_array($v, $user['role'])) {
                http_response_code(401);
                exit;
            }
        }

        return $user;
    }
}

class Config extends \PHPAuth\Config
{
    public function __construct($dbh, $config_source = null, string $config_type = self::CONFIG_TYPE_SQL, string $config_site_language = '')
    {
        if (!is_array($config_source) || !$config_source) {
            throw new RuntimeException('PHPAuth: config is not an array type, or empty');
        }
        $this->config = &$config_source;

        $f = null;
        $lang = !empty($_COOKIE['lang']) ? $_COOKIE['lang'] : substr(locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '') ?: '', 0, 2);

        if ($lang === 'ru') {
            $f = __DIR__ . '/languages/ru_RU.php';
        } else {
            $f = __DIR__ . '/languages/en_GB.php';
        }

        if (is_readable($f)) {
            $this->config['dictionary'] = include $f;
        }

        $this->setForgottenDefaults();
    }
}
