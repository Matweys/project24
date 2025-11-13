#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

define('PROG', 'Storage sizes');


function help()
{
    printf("%s
Подсчитывает размеры хранилищ файлов. Запускайте из cron.

Usage: php storage_sizes.php -c config.php

arguments:
  -c --config config.php  загрузить конфигурационный файл\n\n", PROG);
}

$opts = getopt('c:h', ['config:', 'help']);

if (isset($opts['h']) || isset($opts['help'])) {
    help();
} elseif (!empty($opts['c']) || !empty($opts['config'])) {
    set_error_handler(function ($n, $s, $f, $l, $c) {
        throw new ErrorException($s, 0, $n, $f, $l);
    });

    try {
        $config = include(!empty($opts['c']) ? $opts['c'] : $opts['config']);
    } catch (ErrorException $e) {
        fwrite(STDERR, sprintf("Error: %s\n", $e->getMessage()));
        exit(1);
    }

    restore_error_handler();

    $db = new \PDO($config['database_uri'] ?? null);
    $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

    $r_storage = $db->prepare('select * from storage');
    $r_storage->execute();

    $r_update = $db->prepare('update storage set size = :size where id = :storage_id');

    while ($storage = $r_storage->fetch(\PDO::FETCH_ASSOC)) {
        $table = 'file_' . (int) $storage['id'];

        $r = $db->prepare('select to_regclass(?)');
        $r->execute([$table]);

        if ($r->fetchColumn()) {
            $r = $db->prepare('select pg_total_relation_size(?)');
            $r->execute([$table]);

            $table_size = (int) $r->fetchColumn();

            $r = $db->prepare("select coalesce(sum(image_size), 0) + coalesce(sum(size), 0) from $table");
            $r->execute();

            $size = (int) $r->fetchColumn();

            $file_size = 0;

            if (!empty($config['upload']['path']) && !empty($storage['uid'])) {
                $storage_upload_path = rtrim($config['upload']['path'], '/') . '/' . $storage['uid'];

                if (is_dir($storage_upload_path)) {
                    $io = popen(sprintf('/usr/bin/du -sb "%s"', $storage_upload_path), 'r');

                    if ($io !== false) {
                        $file_size = (int) fgets($io, 80);
                        pclose($io);
                    }
                }
            }

            $r_update->execute([
                ':size' => max($size, $file_size) + $table_size,
                ':storage_id' => $storage['id'],
            ]);
        }
    }
} else {
    help();
}
