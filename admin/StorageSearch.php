<?php

declare(strict_types=1);

namespace Admin;

class StorageSearch
{
    public const cache_rebuild_index_try = 'storage_%s_search_index_rebuild';

    public function __construct(\PDO $db, Cache $cache)
    {
        $this->db = &$db;
        $this->cache = &$cache;
    }

    public function rebuild_index(int $storage_id)
    {
        $r = $this->db->prepare("select job_id from gue_jobs where job_type = 'search_indexer' and queue = 'search_indexer' and error_count = 0 and (convert_from(args, 'utf8')::json->>'storage_id')::int = ?");
        $r->execute([$storage_id]);

        if (!$r->fetchColumn()) {
            $cache_key = sprintf(self::cache_rebuild_index_try, $storage_id);

            $this->cache->inc($cache_key, 1800);
            $rebuild_try = min(1, (int) $this->cache->get($cache_key));

            $delay = min(1800, round(pow(1.6, $rebuild_try) + mt_rand(60, 300)));

            $r = $this->db->prepare("insert into gue_jobs (job_id, args, created_at, job_type, priority, queue, run_at, updated_at) values (?, (json_build_object('storage_id', (?)::int)::text)::bytea, current_timestamp, 'search_indexer', 0, 'search_indexer', (current_timestamp + ? * interval '1 second'), current_timestamp)");

            $r->execute([\PgIto\FastUlid\FastUlid::gen(), $storage_id, $delay]);
        }
    }
}
