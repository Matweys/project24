# Диагностика проблем с фильтрацией

Если фильтр не работает (не находит файлы), проверьте следующее:

## 1. Проверка статуса Manticore

```bash
# Проверка, что Manticore запущен
systemctl status manticore

# Если не запущен, запустите
systemctl start manticore
systemctl enable manticore
```

## 2. Проверка подключения к Manticore

```bash
# Проверка сокета
ls -la /run/manticore/manticore.sock

# Если сокета нет, проверьте конфигурацию
cat /etc/manticoresearch/manticore.conf | grep listen
```

## 3. Проверка конфигурации в приложении

Убедитесь, что в `config/archivarius_web_config.php` правильно указан `sphinx_uri`:

```php
'sphinx_uri' => 'mysql:unix_socket=/run/manticore/manticore.sock',
```

## 4. Проверка существования индексов

```bash
cd /var/www/project24

# Проверка всех индексов
php check_manticore.php

# Проверка конкретного хранилища (замените 3 на ID вашего хранилища)
php check_manticore.php 3
```

Скрипт покажет:
- Статус подключения к Manticore
- Список всех индексов
- Количество документов в каждом индексе
- Структуру индекса для конкретного хранилища

## 5. Создание/обновление индексов

Если индексы не существуют или не обновлены:

```bash
# 1. Обновить конфигурацию Manticore
cd /var/www/project24
php doc/manticore.conf.debian.sample > /tmp/manticore_config.conf
mv /tmp/manticore_config.conf /etc/manticoresearch/manticore.conf

# 2. Перезапустить Manticore
systemctl restart manticore

# 3. Создать/обновить индексы
su - manticore -s /bin/bash -c "indexer --all --rotate"
```

## 6. Проверка логов

```bash
# Логи Manticore
tail -f /var/log/manticore/searchd.log

# Логи PHP (ошибки фильтрации)
tail -f /var/log/php8.1-fpm.log
# или
tail -f /var/log/nginx/error.log
```

## 7. Ручная проверка индексации

Используйте скрипт проверки:
```bash
cd /var/www/project24
php check_manticore.php [storage_id]
```

Или установите mysql-client для прямого подключения:
```bash
apt install mysql-client-core-8.0
mysql -h 127.0.0.1 -P 9306

# Проверка количества документов в индексе (замените X на ID хранилища)
SELECT COUNT(*) FROM file_X_filter;

# Тестовый поиск
SELECT * FROM file_X_filter WHERE MATCH('@name Приказ') LIMIT 10;
```

## 8. Если индексы не создаются автоматически

Индексация происходит через очередь задач. Если воркер очереди не запущен, индексы не будут обновляться автоматически.

Проверьте, запущен ли воркер:
```bash
ps aux | grep gue
# или
ps aux | grep search_indexer
```

Если воркер не запущен, индексы нужно обновлять вручную после каждого изменения данных:
```bash
su - manticore -s /bin/bash -c "indexer --all --rotate"
```

## Решение проблем

1. **Manticore не запущен** → `systemctl start manticore`
2. **Индексы не существуют** → Выполните шаг 5
3. **Индексы не обновляются** → Выполните шаг 5 или настройте воркер очереди
4. **Неправильный sphinx_uri** → Проверьте шаг 3
5. **Ошибки в логах** → Проверьте шаг 6

