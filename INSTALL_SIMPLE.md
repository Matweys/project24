# Инструкция по запуску проекта на удаленном сервере

## Предварительные требования

- Сервер Ubuntu/Debian (только что созданный, пустой)
- Доступ по SSH с правами root или sudo
- Интернет для скачивания пакетов

---

## Шаг 1: Подключение к серверу

```bash
ssh root@ваш_ip_адрес
# или
ssh пользователь@ваш_ip_адрес
```

---

## Шаг 2: Обновление системы и установка зависимостей

```bash
# Обновление системы
apt update
apt upgrade -y

# Установка всех необходимых пакетов
apt install -y postgresql postgresql-contrib nginx php8.1-fpm php8.1-pgsql php8.1-mysql php8.1-imagick php8.1-gd php8.1-mbstring php8.1-zip php8.1-bcmath php8.1-intl php8.1-xml composer git

# Установка Node.js 18.x (требуется для компиляции фронтенда)
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs
```

---

## Шаг 3: Установка Manticore Search

```bash
# Скачивание репозитория Manticore
wget https://repo.manticoresearch.com/manticore-repo.noarch.deb
dpkg -i manticore-repo.noarch.deb
apt update
apt install -y manticore manticore-extra

# Создание необходимых директорий
mkdir -p /var/lib/manticore/{data,binlog} /var/log/manticore /run/manticore
chown -R manticore:manticore /var/lib/manticore /var/log/manticore /run/manticore
```

---

## Шаг 4: Клонирование проекта с GitHub

```bash
# Переход в директорию веб-сервера
cd /var/www

# Клонирование проекта (замените URL на ваш репозиторий)
git clone https://github.com/ваш_username/название_репозитория.git
# или если репозиторий приватный:
# git clone https://ваш_токен@github.com/ваш_username/название_репозитория.git

# Переход в директорию проекта
cd название_репозитория
```

**Примечание:** Если проект уже скопирован на сервер другим способом, просто перейдите в его директорию.

---

## Шаг 5: Создание базы данных PostgreSQL

```bash
# Создание базы данных и пользователя
# Пароль: замените 'ваш_пароль_бд' на надежный пароль
su - postgres << EOF
psql << SQL
CREATE DATABASE app_db;
CREATE USER app_user WITH PASSWORD 'ваш_пароль_бд';
GRANT ALL PRIVILEGES ON DATABASE app_db TO app_user;
\c app_db
GRANT ALL ON SCHEMA public TO app_user;
SQL
EOF
```

**Важно:** Запомните пароль базы данных — он понадобится в следующем шаге.

---

## Шаг 6: Загрузка схемы базы данных

```bash
# Переход в директорию проекта
cd /var/www/название_репозитория

# Установка переменной окружения с паролем (чтобы не вводить пароль каждый раз)
export PGPASSWORD='ваш_пароль_бд'

# Загрузка основной схемы (используем -h localhost для TCP подключения)
psql -h localhost -U app_user -d app_db -f db/schema.sql

# Загрузка дополнительных таблиц
psql -h localhost -U app_user -d app_db -f db/cache.sql
psql -h localhost -U app_user -d app_db -f db/storage_folders.sql
psql -h localhost -U app_user -d app_db -f db/throttle.sql

# Применение миграций (файлы с датами)
# Некоторые миграции могут выдавать ошибки о существующих колонках - это нормально
for file in db/20*.sql; do
    psql -h localhost -U app_user -d app_db -f "$file" 2>&1 | grep -v "ERROR\|NOTICE" || true
done
```

**Примечание:** Параметр `-h localhost` необходим для обхода peer authentication в PostgreSQL. Переменная `PGPASSWORD` позволяет не вводить пароль каждый раз.

---

## Шаг 7: Настройка конфигурации

```bash
# Копирование примера конфигурации
cp config/archivarius_web_config.php.sample config/archivarius_web_config.php

# Открытие конфига для редактирования
nano config/archivarius_web_config.php
```

**Примечание:** Имя файла конфигурации `archivarius_web_config.php` - это реальное имя файла в проекте, его менять не нужно.

### Что нужно изменить в конфиге:

**1. `database_uri` (примерно строка 67):**
```php
'database_uri' => 'pgsql:host=localhost;dbname=app_db;user=app_user;password=ваш_пароль_бд',
```
Замените `ваш_пароль_бд` на пароль, который вы указали в шаге 5.

**Важно:** Параметр `host=localhost` обязателен для обхода peer authentication в PostgreSQL при работе через PHP-FPM.

**2. `site_url` (примерно строка 88):**
```php
'site_url' => 'http://ваш_ip_или_домен',
```
Например: `'http://62.60.157.148'` или `'http://example.com'`

**3. `site_name` (примерно строка 85):**
```php
'site_name' => 'Название вашего сайта',
```

**4. `auth['site_email']` (примерно строка 38):**
```php
'site_email' => 'noreply@ваш-домен.com',
```

**5. `user_activation_url` (примерно строка 108):**
```php
'user_activation_url' => 'http://ваш_ip_или_домен/user/',
```
Должен совпадать с `site_url` + `/user/`

**Сохраните файл:** `Ctrl+O`, затем `Enter`, затем `Ctrl+X`

---

## Шаг 8: Установка зависимостей PHP

```bash
cd /var/www/название_репозитория
composer install --no-dev --optimize-autoloader
```

Если composer не установлен, установите его:
```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
composer install --no-dev --optimize-autoloader
```

---

## Шаг 9: Компиляция фронтенд-ассетов

```bash
# Основное приложение
cd /var/www/название_репозитория/main
npm install
npm run compile:main.css
npm run compile
npm run compress
npm run update_asset_version

# Страница авторизации
cd /var/www/название_репозитория/auth
npm install
npm run compile:main.css
npm run compile:main.js
npm run update_asset_version
```

---

## Шаг 10: Настройка прав доступа

```bash
cd /var/www/название_репозитория

# Создание директории для загруженных файлов, если её нет
mkdir -p public/static/storage

# Установка прав доступа
chown -R www-data:www-data /var/www/название_репозитория
chmod -R 755 /var/www/название_репозитория
chmod -R 775 /var/www/название_репозитория/public/static/storage
chmod 600 /var/www/название_репозитория/config/archivarius_web_config.php
```

---

## Шаг 11: Настройка Manticore Search

```bash
cd /var/www/название_репозитория

# Генерация конфигурации Manticore
# ВАЖНО: Сначала нужно настроить конфиг приложения (шаг 7), чтобы скрипт мог прочитать параметры БД
php doc/manticore.conf.debian.sample > /tmp/manticore_config.conf

# Проверка содержимого конфигурации
cat /tmp/manticore_config.conf
```

**Примечание:** 
- Скрипт автоматически читает параметры БД из `config/archivarius_web_config.php`
- Если в базе данных еще нет хранилищ, файл будет содержать только секцию `searchd` — это нормально
- Конфигурация будет дополняться автоматически после создания хранилищ через веб-интерфейс

Затем скопируйте конфигурацию:
```bash
mv /tmp/manticore_config.conf /etc/manticoresearch/manticore.conf

# Запуск Manticore
systemctl start manticore
systemctl enable manticore

# Проверка что Manticore запустился
systemctl status manticore

# Индексация данных
# ВАЖНО: Если в базе данных еще нет хранилищ, команда выдаст ошибку "no tables found" - это нормально
# Индексы появятся автоматически после создания первого хранилища через веб-интерфейс
su - manticore -s /bin/bash -c "indexer --all --rotate" || echo "Индексация пропущена: хранилищ еще нет"
```

---

## Шаг 12: Настройка Nginx

```bash
# Создание конфигурации Nginx
nano /etc/nginx/sites-available/app
```

Вставьте следующее (замените `your-domain.com` на ваш IP или домен, и `название_репозитория` на реальное название директории проекта):

```nginx
server {
    listen 80;
    server_name your-domain.com;  # Замените на ваш IP или домен
    root /var/www/название_репозитория/public;
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

**Сохраните:** `Ctrl+O`, `Enter`, `Ctrl+X`

Активируйте конфигурацию:
```bash
# Активация конфигурации
ln -s /etc/nginx/sites-available/app /etc/nginx/sites-enabled/

# Проверка конфигурации
nginx -t

# Перезагрузка Nginx
systemctl reload nginx
```

---

## Шаг 13: Создание администратора с предустановленным паролем

```bash
cd /var/www/название_репозитория

# Запуск скрипта для создания администратора
php setup_admin.php
```

Скрипт автоматически создаст пользователя `admin@localhost.ru` с паролем `Qq1234567!` и назначит все необходимые роли.

**Логин для входа:** `admin@localhost.ru`  
**Пароль для входа:** `Qq1234567!`

**Примечание:** Если скрипт выдает ошибку подключения к БД, убедитесь что в конфиге `config/archivarius_web_config.php` правильно указан `database_uri` с паролем.

---

## Шаг 14: Запуск сервисов

```bash
# Запуск и включение автозапуска для всех сервисов
systemctl start postgresql
systemctl enable postgresql

systemctl start php8.1-fpm
systemctl enable php8.1-fpm

systemctl start nginx
systemctl enable nginx

systemctl start manticore
systemctl enable manticore
```

---

## Шаг 15: Проверка работы

1. **Откройте сайт в браузере:**
   - Перейдите по адресу `http://ваш_ip_адрес` или `http://ваш_домен`
   - Должна открыться страница логина

2. **Вход в систему:**
   - Откройте `http://ваш_ip_адрес/login`
   - **Логин:** `admin@localhost.ru`
   - **Пароль:** `Qq1234567!`

3. **Создание первого хранилища:**
   - После входа вы можете увидеть пустую страницу — это нормально, если еще нет хранилищ
   - Для создания первого хранилища перейдите по адресу: `http://ваш_ip_адрес/admin/storages/new`
   - Или откройте список хранилищ: `http://ваш_ip_адрес/admin/storages/`
   - Создайте первое хранилище через веб-интерфейс

**Примечание:** Если Manticore выдает ошибку "no tables found" при индексации — это нормально. Индексы появятся автоматически после создания первого хранилища.

---

## Просмотр логов (если что-то не работает)

```bash
# Логи Nginx
tail -f /var/log/nginx/error.log

# Логи PHP
tail -f /var/log/php8.1-fpm.log

# Логи Manticore
tail -f /var/log/manticore/searchd.log

# Логи PostgreSQL
tail -f /var/log/postgresql/postgresql-*.log
```

---

## Шаг 16: Замена логотипа (опционально)

Если вы хотите использовать свой логотип вместо стандартного:

```bash
cd /var/www/название_репозитория

# 1. Скопируйте ваш PNG файл в директорию со статикой
# Замените /path/to/your/logo.png на путь к вашему файлу
cp /path/to/your/logo.png public/static/img/logo.png

# 2. Установите правильные права доступа
chown www-data:www-data public/static/img/logo.png
chmod 644 public/static/img/logo.png

# 3. Обновите настройку в базе данных
export PGPASSWORD='ваш_пароль_бд'
psql -h localhost -U app_user -d app_db -c "
UPDATE public.setting 
SET text_value = '/static/img/logo.png' 
WHERE name = 'header_logo_url' AND language_id IN (1, 2);
"
```

**Примечание:** Замените `logo.png` на имя вашего файла, если оно отличается. Путь должен быть относительным от директории `public/` (например, `/static/img/ваш_логотип.png`).

---

## Готово!

После выполнения всех шагов сайт должен работать. Вы можете войти в систему используя:
- **Логин:** `admin@localhost.ru`
- **Пароль:** `Qq1234567!`

**Важные URL:**
- Главная страница: `http://ваш_ip_адрес/`
- Страница логина: `http://ваш_ip_адрес/login`
- Управление хранилищами: `http://ваш_ip_адрес/admin/storages/`
- Создание хранилища: `http://ваш_ip_адрес/admin/storages/new`
- Управление пользователями: `http://ваш_ip_адрес/admin/users/`
- Логи системы: `http://ваш_ip_адрес/admin/log/`

---

## Полезные команды для управления

```bash
# Перезапуск всех сервисов
systemctl restart nginx
systemctl restart php8.1-fpm
systemctl restart manticore

# Проверка статуса сервисов
systemctl status nginx
systemctl status php8.1-fpm
systemctl status postgresql
systemctl status manticore
```

