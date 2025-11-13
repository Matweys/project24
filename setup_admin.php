<?php
/**
 * Скрипт для создания администратора с предустановленным паролем
 * Запуск: php setup_admin.php
 */

require __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config/archivarius_web_config.php';

try {
    // Принудительно используем TCP подключение для обхода peer authentication
    $database_uri = $config['database_uri'];
    if (strpos($database_uri, 'host=') === false) {
        $database_uri .= ';host=localhost';
    }
    $db = new PDO($database_uri);
    $auth = new \Auth\Auth($db, new \Auth\Config($db, $config['auth'], \Auth\Config::CONFIG_TYPE_ARRAY, 'ru_RU'));
    
    $email = 'admin@localhost.ru';
    $password = 'Qq1234567!';
    
    // Проверяем, существует ли пользователь
    $stmt = $db->prepare("SELECT id FROM public.user WHERE email = ?");
    $stmt->execute([$email]);
    $userId = $stmt->fetchColumn();
    
    if ($userId) {
        // Пользователь существует, обновляем пароль
        echo "Пользователь $email уже существует. Обновляем пароль...\n";
        
        // Используем метод getHash из Auth для правильного хеширования
        $hash = $auth->getHash($password);
        $updateStmt = $db->prepare("UPDATE public.user SET password = ?, isactive = true, modified = current_timestamp WHERE id = ?");
        $updateStmt->execute([$hash, $userId]);
        
        echo "Пароль успешно обновлен для пользователя $email\n";
    } else {
        // Создаем нового пользователя напрямую в БД, чтобы избежать проблем с типами данных
        echo "Создаем нового пользователя $email...\n";
        
        // Используем метод getHash из Auth для правильного хеширования
        $hash = $auth->getHash($password);
        
        $insertStmt = $db->prepare("
            INSERT INTO public.user (email, password, isactive, created, modified) 
            VALUES (?, ?, true, current_timestamp, current_timestamp)
            RETURNING id
        ");
        $insertStmt->execute([$email, $hash]);
        $userId = $insertStmt->fetchColumn();
        
        if (!$userId) {
            echo "Ошибка при создании пользователя\n";
            exit(1);
        }
        
        echo "Пользователь $email успешно создан\n";
    }
    
    // Назначаем роли администратора
    echo "Назначаем роли администратора...\n";
    
    $roleStmt = $db->prepare("
        INSERT INTO user_role_user (role_id, user_id) 
        SELECT r.id, ? 
        FROM user_role r
        WHERE r.name IN ('admin', 'user_management', 'storage_management')
        ON CONFLICT DO NOTHING
    ");
    $roleStmt->execute([$userId]);
    
    echo "Роли успешно назначены\n";
    echo "\n";
    echo "========================================\n";
    echo "Администратор создан успешно!\n";
    echo "========================================\n";
    echo "Логин: $email\n";
    echo "Пароль: $password\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}

