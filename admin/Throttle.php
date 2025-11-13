<?php

declare(strict_types=1);

namespace Admin;

class Throttle
{
    public static function add($db, $id, $threshold, $min_throttle_time = 600, $max_throttle_time = 86400)
    {
        $t_id = sprintf('%s:t', $id);

        $r = $db->prepare('SELECT count FROM throttle WHERE id = ? AND expire > current_timestamp');
        $r->execute([$t_id]);
        $ttl = min((int) $max_throttle_time, (int) $min_throttle_time * pow(2, (int) $r->fetchColumn()));

        $r_update = $db->prepare('CALL throttle_update(?, ?)');
        $r_update->execute([$ttl, $id]);

        // Increese throttle time if need
        if (!static::check($db, $id, $threshold)) {
            $r_update->execute([$max_throttle_time, $t_id]);
        }

        if (!mt_rand(0, 100)) {
            $r = $db->prepare('DELETE FROM throttle WHERE expire < current_timestamp');
            $r->execute();
        }
    }

    public static function save($db, $id, $threshold, $min_throttle_time = 600, $max_throttle_time = 86400)
    {
        if (static::check($db, $id, $threshold)) {
            static::add($db, $id, $threshold, $min_throttle_time, $max_throttle_time);
            return true;
        }
    }

    public static function check($db, $id, $threshold)
    {
        $r = $db->prepare('SELECT count FROM throttle WHERE id = ? AND expire > current_timestamp');
        $r->execute([$id]);
        return ((int) $r->fetchColumn() < $threshold);
    }
}
