#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

function cache_cleanup(&$config)
{
    (new \Main\Cache(
        (new \PDO($config['database_uri'] ?? null, options: [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION])),
        $config
    ))->cleanup();
}

function help()
{
    printf("Cache cleanup
Usage: php cache_cleanup.php -c config.php

arguments:
  -c --config config.php  load config from this file\n\n");
}

$opts = getopt('c:h', ['config:', 'cleanup', 'help']);

if (isset($opts['h']) || isset($opts['help'])) {
    help();
} elseif (! empty($opts['c']) || ! empty($opts['config'])) {
    set_error_handler(function ($n, $s, $f, $l, $c) {
        throw new ErrorException($s, 0, $n, $f, $l);
    });

    try {
        $config = include ! empty($opts['c']) ? $opts['c'] : $opts['config'];
    } catch (ErrorException $e) {
        fwrite(STDERR, sprintf("Error: %s\n", $e->getMessage()));
        exit(1);
    }

    restore_error_handler();

    cache_cleanup($config);
} else {
    help();
}
