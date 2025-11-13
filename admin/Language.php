<?php

declare(strict_types=1);

namespace Admin;

class Language
{
    protected $db;
    public $table_users;

    public function __construct(\PDO $db)
    {
        $this->db = &$db;
    }

    public function getCurrentLanguage($user_id = null)
    {
        $language = null;

        if (!empty($_COOKIE['lang'])) {
            $language = $this->getLanguage(null, $_COOKIE['lang']);
        }

        if (!$language && $user_id && $this->table_users) {
            $r = $this->db->prepare("SELECT l.* FROM language l JOIN {$this->table_users} u ON (u.language_id = l.id AND l.active IS TRUE) WHERE u.id = ? AND u.isactive IS TRUE");
            $r->execute([$user_id]);
            $language = $r->fetch(\PDO::FETCH_ASSOC);
        }

        if (!$language) {
            $language = $this->getLanguage(null, substr(locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '') ?: '', 0, 2) ?: 'en');
        }

        return $language;
    }

    public function getLanguage($id = null, $name = null)
    {
        if (!empty($id)) {
            $r = $this->db->prepare('SELECT * FROM language WHERE id = ? AND active IS TRUE');
            $r->execute([$id]);
            return $r->fetch(\PDO::FETCH_ASSOC);
        } elseif (!empty($name)) {
            $r = $this->db->prepare('SELECT * FROM language WHERE name = ? AND active IS TRUE');
            $r->execute([$name]);
            return $r->fetch(\PDO::FETCH_ASSOC);
        }
    }

    public function getLanguages()
    {
        $r = $this->db->prepare('SELECT * FROM language WHERE active IS TRUE ORDER BY title');
        $r->execute();
        return $r->fetchAll(\PDO::FETCH_ASSOC);
    }
}
