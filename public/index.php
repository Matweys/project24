<?php

declare(strict_types=1);

define('__VERSION__', (include __DIR__ . '/../version.php') ?: null);

require __DIR__ . '/../vendor/autoload.php';

\Router\Router::start(
    array_merge((include __DIR__ . '/../config/archivarius_web_config.php') ?: [], (include __DIR__ . '/../assets.php') ?: []),
    array_merge(
        (include __DIR__ . '/../admin/route.php'),
        (include __DIR__ . '/../auth/route.php'),
        (include __DIR__ . '/../main/route.php'),
    )
);
