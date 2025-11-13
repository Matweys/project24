<?php

declare(strict_types=1);

namespace Main;

use Main\Language;
use Main\Setting;

abstract class BaseController
{
    protected $auth;
    protected $config;
    protected $current_user;
    protected $db;
    protected $storages;

    public function __construct(?array $config)
    {
        $this->config = &$config;

        try {
            $this->db = new \PDO($this->config['database_uri'] ?? null, options: [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        } catch(\PDOException $e) {
            error_log((string) $e);
            die('Ошибка базы данных');
        }

        date_default_timezone_set($this->config['site_timezone']);
        setlocale(LC_CTYPE, 'en_US.UTF-8');

        $this->auth = new \Auth\Auth($this->db, new \Auth\Config($this->db, $config['auth'], \Auth\Config::CONFIG_TYPE_ARRAY, 'ru_RU'));

        $this->language = new Language($this->db);
        $this->language->table_users = $this->config['auth']['table_users'];

        $this->setting = new Setting($this->db, $this->config);

        session_start();
    }

    public function load_translation($lang)
    {
        if ($lang === 'ru') {
            load_textdomain('default', __DIR__ . '/languages/main_ru.mo');
        }
    }
}
