<?php

declare(strict_types=1);

namespace Admin;

class Setting
{
    public function __construct(\PDO $db, array &$config)
    {
        $this->config = &$config;
        $this->db = &$db;
    }

    public function load(string $lang, array $groups = ['autoload'])
    {
        $r = $this->db->prepare(
            'SELECT setting.*
FROM setting
LEFT JOIN language ON (language.id = setting.language_id AND language.active IS TRUE)
LEFT JOIN setting_group ON (setting_group.id = setting.group_id)
WHERE setting_group.name IN (' . str_repeat('?,', count($groups) - 1) . '?) AND language.name = ?'
        );

        $r->execute(array_merge($groups, [$lang]));

        while ($row = $r->fetch(\PDO::FETCH_ASSOC)) {
            $v = $row['type'] == 'json' ?
                json_decode($row['text_value'], true) :
                ($row['type'] == 'float' ?
                    (float) $row['float_value'] :
                    ($row['type'] == 'int' ?
                        (int) $row['int_value'] :
                        $row['text_value']));

            if (isset($v)) {
                $this->config[$row['name']] = $v;
            }
        }
    }

    public function getByGroups(array $groups, string $lang)
    {
        $settings = [];

        $r = $this->db->prepare(
            'SELECT setting.*
FROM setting
LEFT JOIN language ON (language.id = setting.language_id AND language.active IS TRUE)
LEFT JOIN setting_group ON (setting_group.id = setting.group_id)
WHERE setting_group.name IN (' . str_repeat('?,', count($groups) - 1) . '?) AND language.name = ?'
        );

        $r->execute(array_merge($groups, [$lang]));

        while ($row = $r->fetch(\PDO::FETCH_ASSOC)) {
            $v = $row['type'] == 'json' ?
                json_decode($row['text_value'], true) :
                ($row['type'] == 'float' ?
                    (float) $row['float_value'] :
                    ($row['type'] == 'int' ?
                        (int) $row['int_value'] :
                        $row['text_value']));

            if (isset($v)) {
                $settings[$row['name']] = $v;
            }
        }

        return $settings;
    }

    public function getByNames(array $names, string $lang)
    {
        $settings = [];

        $r = $this->db->prepare(
            'SELECT setting.*
FROM setting
LEFT JOIN language ON (language.id = setting.language_id AND language.active IS TRUE)
WHERE setting.name IN (' . str_repeat('?,', count($names) - 1) . '?) AND language.name = ?'
        );

        $r->execute(array_merge($names, [$lang]));

        while ($row = $r->fetch(\PDO::FETCH_ASSOC)) {
            $v = $row['type'] == 'json' ?
                json_decode($row['text_value']) :
                ($row['type'] == 'float' ?
                    (float) $row['float_value'] :
                    ($row['type'] == 'int' ?
                        (int) $row['int_value'] :
                        $row['text_value']));

            if (isset($v)) {
                $settings[$row['name']] = $v;
            }
        }

        return $settings;
    }
}
