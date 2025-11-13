<?php

declare(strict_types=1);

namespace Admin;

class Cache
{
    public const max_ttl = 2592000;

    public function __construct(\PDO $db, ?array &$config)
    {
        $this->config = &$config;
        $this->db = &$db;
    }

    public function cleanup()
    {
        for ($i = 0; $i < 3; $i++) {
            $r = $this->db->prepare('select count(*) from public.cache where bucket = ?');
            $r->execute([$i]);

            if ($r->fetchColumn()) {
                $r = $this->db->prepare('select count(*) from public.cache where bucket = ? and expire > current_timestamp');
                $r->execute([$i]);

                if (!$r->fetchColumn()) {
                    $r = $this->db->prepare('truncate public.cache_p' . $i);
                    $r->execute();
                }
            }
        }
    }

    public function delete(array $keys)
    {
        $r = $this->db->prepare('delete from public.cache where key in (' . str_repeat('?,', count($keys) - 1) . '?)');
        $r->execute($keys);
        return $r->rowCount();
    }

    public function get(string $key)
    {
        $r = $this->db->prepare('select value from public.cache where key = ? and expire > current_timestamp order by expire desc limit 1');
        $r->execute([$key]);

        $value = $r->fetchColumn();

        if ($value) {
            return json_decode($value, true);
        }
    }

    public function inc(string $key, ?int $ttl = null)
    {
        if ($key) {
            if (!isset($ttl)) {
                $ttl = self::max_ttl;
            }

            try {
                $r = $this->db->prepare('call cache_inc(?, ?)');
                $r->execute([$key, max(1, min((int) $ttl, self::max_ttl))]);
            } catch (\PDOException $e) {
                error_log((string) $e);
            }
        }
    }

    public function set(string $key, $value, ?int $ttl = null)
    {
        if ($key && $value) {
            if (!isset($ttl)) {
                $ttl = self::max_ttl;
            }

            try {
                $r = $this->db->prepare('call cache_set(?, ?, ?)');
                $r->execute([$key, json_encode($value, JSON_UNESCAPED_UNICODE), max(1, min((int) $ttl, self::max_ttl))]);
            } catch (\PDOException $e) {
                error_log((string) $e);
            }
        }
    }
}
