# Быстрая установка проекта

## Все команды по порядку (копируйте и выполняйте)

```bash
# 1. Обновление системы
apt update && apt upgrade -y

# 2. Установка зависимостей
apt install -y postgresql postgresql-contrib nginx php8.1-fpm php8.1-pgsql php8.1-mysql php8.1-imagick php8.1-gd php8.1-mbstring php8.1-zip php8.1-bcmath php8.1-intl php8.1-xml composer git
curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && apt install -y nodejs

# 3. Установка Manticore
wget https://repo.manticoresearch.com/manticore-repo.noarch.deb && dpkg -i manticore-repo.noarch.deb && apt update && apt install -y manticore manticore-extra
mkdir -p /var/lib/manticore/{data,binlog} /var/log/manticore /run/manticore
chown -R manticore:manticore /var/lib/manticore /var/log/manticore /run/manticore

# 4. Клонирование проекта (замените URL на ваш)
cd /var/www
git clone https://github.com/ваш_username/project24.git
cd project24

# 5. Создание БД (замените 'ваш_пароль_бд' на пароль)
su - postgres << EOF
psql << SQL
CREATE DATABASE app_db;
CREATE USER app_user WITH PASSWORD 'ваш_пароль_бд';
GRANT ALL PRIVILEGES ON DATABASE app_db TO app_user;
\c app_db
GRANT ALL ON SCHEMA public TO app_user;
SQL
EOF

# 6. Загрузка схемы БД
export PGPASSWORD='ваш_пароль_бд'
psql -h localhost -U app_user -d app_db -f db/schema.sql
psql -h localhost -U app_user -d app_db -f db/cache.sql
psql -h localhost -U app_user -d app_db -f db/storage_folders.sql
psql -h localhost -U app_user -d app_db -f db/throttle.sql
for file in db/20*.sql; do psql -h localhost -U app_user -d app_db -f "$file" 2>&1 | grep -v "ERROR\|NOTICE" || true; done

# 7. Настройка конфига
cp config/archivarius_web_config.php.sample config/archivarius_web_config.php
nano config/archivarius_web_config.php
# Измените: database_uri, site_url, site_name, auth['site_email'], user_activation_url

# 8. Установка зависимостей PHP
composer install --no-dev --optimize-autoloader

# 9. Компиляция фронтенда
cd main && npm install && npm run compile:main.css && npm run compile && npm run compress && npm run update_asset_version && cd ..
cd auth && npm install && npm run compile:main.css && npm run compile:main.js && npm run update_asset_version && cd ..

# 10. Права доступа
mkdir -p /var/www/project24/public/static/storage
chown -R www-data:www-data /var/www/project24
chmod -R 755 /var/www/project24
chmod -R 775 /var/www/project24/public/static/storage
chmod 600 /var/www/project24/config/archivarius_web_config.php

# 11. Настройка Manticore
# Скрипт автоматически читает параметры БД из config/archivarius_web_config.php
php doc/manticore.conf.debian.sample > /tmp/manticore_config.conf
mv /tmp/manticore_config.conf /etc/manticoresearch/manticore.conf
systemctl start manticore && systemctl enable manticore
# Примечание: ошибка "no tables found" нормальна, если хранилищ еще нет
su - manticore -s /bin/bash -c "indexer --all --rotate" || echo "Индексация пропущена: хранилищ еще нет"

# 12. Настройка PHP для загрузки файлов
cp /etc/php/8.1/fpm/php.ini /etc/php/8.1/fpm/php.ini.backup
sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 100M/' /etc/php/8.1/fpm/php.ini
sed -i 's/^post_max_size = .*/post_max_size = 100M/' /etc/php/8.1/fpm/php.ini
systemctl restart php8.1-fpm

# 13. Настройка Nginx
nano /etc/nginx/sites-available/app
# Вставьте конфиг из INSTALL_SIMPLE.md (шаг 13), замените your-domain.com
ln -s /etc/nginx/sites-available/app /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx

# 14. Создание администратора
php setup_admin.php

# 15. Запуск сервисов
systemctl start postgresql && systemctl enable postgresql
systemctl start php8.1-fpm && systemctl enable php8.1-fpm
systemctl start nginx && systemctl enable nginx
systemctl start manticore && systemctl enable manticore
```

## Данные для входа

- **Логин:** `admin@localhost.ru`
- **Пароль:** `Qq1234567!`

## Что нужно изменить в конфиге

Откройте `config/archivarius_web_config.php` и измените:

1. **`database_uri`** (строка ~67):
   ```php
   'database_uri' => 'pgsql:host=localhost;dbname=app_db;user=app_user;password=ваш_пароль_бд',
   ```
   **Важно:** Параметр `host=localhost` обязателен для обхода peer authentication.

2. **`site_url`** (строка ~88):
   ```php
   'site_url' => 'http://ваш_ip_или_домен',
   ```

3. **`site_name`** (строка ~85):
   ```php
   'site_name' => 'Название сайта',
   ```

4. **`auth['site_email']`** (строка ~38):
   ```php
   'site_email' => 'noreply@домен.com',
   ```

5. **`user_activation_url`** (строка ~108):
   ```php
   'user_activation_url' => 'http://ваш_ip_или_домен/user/',
   ```

## Конфигурация Nginx

Создайте файл `/etc/nginx/sites-available/app`:

```nginx
server {
    listen 80;
    server_name ваш_ip_или_домен;
    root /var/www/project24/public;
    index index.php;

    client_max_body_size 1g;
    client_body_timeout 1200;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location /static {
        expires 31d;
        add_header Cache-Control public;
    }

    location /static/storage {
        expires 31d;
        add_header Cache-Control public;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

Замените:
- `ваш_ip_или_домен` на ваш IP или домен

## Готово!

Откройте `http://ваш_ip_или_домен` и войдите:
- Логин: `admin@localhost.ru`
- Пароль: `Qq1234567!`

**Важные URL:**
- Создание хранилища: `http://ваш_ip_или_домен/admin/storages/new`
- Управление хранилищами: `http://ваш_ip_или_домен/admin/storages/`
- Управление пользователями: `http://ваш_ip_или_домен/admin/users/`

**Примечание:** После входа может быть пустая страница — это нормально. Создайте первое хранилище через админ-панель.

