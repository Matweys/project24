<?php
/**
 * Скрипт для создания администратора с предустановленным паролем
 * Запуск: php setup_admin.php
 */

require __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config/archivarius_web_config.php';

try {
    $db = new PDO($config['database_uri']);
    $auth = new \PHPAuth\Auth($db, $config['auth']);
    
    $email = 'admin';
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
        $updateStmt = $db->prepare("UPDATE public.user SET password = ?, modified = current_timestamp WHERE id = ?");
        $updateStmt->execute([$hash, $userId]);
        
        echo "Пароль успешно обновлен для пользователя $email\n";
    } else {
        // Создаем нового пользователя
        echo "Создаем нового пользователя $email...\n";
        
        $result = $auth->register($email, $password, $password, null, null, true);
        
        if ($result['error']) {
            echo "Ошибка при создании пользователя: " . $result['message'] . "\n";
            exit(1);
        } else {
            echo "Пользователь $email успешно создан\n";
            $userId = $result['uid'];
        }
    }
    
    // Назначаем роли администратора
    echo "Назначаем роли администратора...\n";
    
    $roleStmt = $db->prepare("
        INSERT INTO user_role_user (role_id, user_id) 
        SELECT role_id, ? 
        FROM user_role 
        WHERE name IN ('admin', 'user_management', 'storage_management')
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

