<?php
/**
 * Скрипт для проверки подключения к Manticore и наличия индексов
 * 
 * Использование:
 * php check_manticore.php [storage_id]
 * 
 * Если storage_id не указан, покажет все индексы
 */

require_once __DIR__ . '/config/archivarius_web_config.php';

$config = require __DIR__ . '/config/archivarius_web_config.php';

if (empty($config['sphinx_uri'])) {
    echo "ОШИБКА: sphinx_uri не настроен в конфиге\n";
    exit(1);
}

echo "Подключение к Manticore: {$config['sphinx_uri']}\n\n";

try {
    $sphinx = new \PDO($config['sphinx_uri']);
    $sphinx->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Подключение успешно\n\n";
    
    // Получаем список всех индексов
    try {
        $r = $sphinx->query("SHOW TABLES");
        $tables = [];
        while ($row = $r->fetch(\PDO::FETCH_NUM)) {
            if (!empty($row[0])) {
                $tables[] = $row[0];
            }
        }
        
        // Если не получилось через FETCH_NUM, пробуем FETCH_ASSOC
        if (empty($tables)) {
            $r = $sphinx->query("SHOW TABLES");
            while ($row = $r->fetch(\PDO::FETCH_ASSOC)) {
                $value = reset($row);
                if (!empty($value)) {
                    $tables[] = $value;
                }
            }
        }
    } catch (\PDOException $e) {
        echo "✗ Ошибка получения списка индексов: " . $e->getMessage() . "\n";
        echo "Попробуйте проверить логи: tail -f /var/log/manticore/searchd.log\n";
        exit(1);
    }
    
    if (empty($tables)) {
        echo "⚠ Индексы не найдены. Нужно создать индексы.\n";
        echo "Выполните: su - manticore -s /bin/bash -c \"indexer --all --rotate\"\n";
        exit(1);
    }
    
    echo "Найдено индексов: " . count($tables) . "\n\n";
    
    $storage_id = $argv[1] ?? null;
    
    if ($storage_id) {
        // Проверяем конкретный индекс
        $filter_index = "file_{$storage_id}_filter";
        $main_index = "file_{$storage_id}_main";
        
        echo "Проверка индексов для хранилища ID {$storage_id}:\n";
        echo "----------------------------------------\n";
        
        if (in_array($filter_index, $tables)) {
            echo "✓ Индекс {$filter_index} существует\n";
            
            // Проверяем структуру
            $r = $sphinx->prepare("DESCRIBE {$filter_index}");
            $r->execute();
            $fields = $r->fetchAll(\PDO::FETCH_ASSOC);
            
            echo "  Поля: " . count($fields) . "\n";
            
            // Проверяем количество документов
            try {
                $r = $sphinx->prepare("SELECT COUNT(*) FROM {$filter_index}");
                $r->execute();
                $count = $r->fetchColumn();
                echo "  Документов в индексе: {$count}\n";
            } catch (\PDOException $e) {
                echo "  ⚠ Не удалось получить количество документов: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✗ Индекс {$filter_index} НЕ существует\n";
            echo "  Нужно создать индекс. Выполните:\n";
            echo "  php doc/manticore.conf.debian.sample > /tmp/manticore_config.conf\n";
            echo "  mv /tmp/manticore_config.conf /etc/manticoresearch/manticore.conf\n";
            echo "  systemctl restart manticore\n";
            echo "  su - manticore -s /bin/bash -c \"indexer --all --rotate\"\n";
        }
        
        if (in_array($main_index, $tables)) {
            echo "✓ Индекс {$main_index} существует\n";
        } else {
            echo "✗ Индекс {$main_index} НЕ существует\n";
        }
    } else {
        // Показываем все индексы
        echo "Список всех индексов:\n";
        echo "----------------------------------------\n";
        foreach ($tables as $table) {
            echo "- {$table}\n";
            
            // Пытаемся определить ID хранилища из имени индекса
            if (preg_match('/file_(\d+)_(filter|main)/', $table, $matches)) {
                $storage_id_from_name = $matches[1];
                try {
                    $r = $sphinx->prepare("SELECT COUNT(*) FROM {$table}");
                    $r->execute();
                    $count = $r->fetchColumn();
                    echo "  Хранилище ID: {$storage_id_from_name}, документов: {$count}\n";
                } catch (\PDOException $e) {
                    echo "  (не удалось получить количество документов)\n";
                }
            }
        }
        
        echo "\nДля проверки конкретного хранилища используйте:\n";
        echo "php check_manticore.php [storage_id]\n";
    }
    
} catch (\PDOException $e) {
    echo "✗ ОШИБКА подключения к Manticore:\n";
    echo "  " . $e->getMessage() . "\n\n";
    echo "Проверьте:\n";
    echo "1. Запущен ли Manticore: systemctl status manticore\n";
    echo "2. Правильно ли указан sphinx_uri в конфиге\n";
    echo "3. Существует ли сокет: ls -la /run/manticore/manticore.sock\n";
    exit(1);
}

