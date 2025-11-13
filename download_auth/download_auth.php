<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

try {
    $db = new \PDO($config['database_uri'] ?? null);
    $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
} catch(\PDOException $e) {
    error_log((string) $e);
    http_response_code(401);
    exit;
}

$auth = new \Auth\Auth($db, new \Auth\Config($db, $config['auth'], \Auth\Config::CONFIG_TYPE_ARRAY, 'ru_RU'));

$user = $auth->isLogged() ? $auth->getCurrentUser() : null;

if (!empty($user['id'])) {
    $uri = $_SERVER['REQUEST_URI'] ?? '';

    $m = null;
    preg_match('@^' . trim($config['upload']['url'], '/') . '/(\w+)@i', trim($uri, '/'), $m);

    if (!empty($m[1])) {
        $r = $db->prepare(
            'SELECT storage.id
FROM storage_user_permission m
JOIN storage ON storage.id = m.storage_id
WHERE m.user_id = :user_id AND storage.uid = :storage_uid'
        );

        $r->execute([
            ':storage_uid' => $m[1],
            ':user_id' => $user['id'],
        ]);

        $allowed = $r->fetchColumn();

        if ($allowed) {
            http_response_code(200);
            exit;
        }
    }
}

http_response_code(401);
