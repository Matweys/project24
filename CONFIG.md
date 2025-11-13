# ⚙️ Настройка конфигурации Archivarius Web

Файл конфигурации: `config/archivarius_web_config.php`

## 📝 Как редактировать конфиг

1. Скопируйте пример конфигурации:
```bash
cp config/archivarius_web_config.php.sample config/archivarius_web_config.php
```

2. Откройте файл в редакторе:
```bash
nano config/archivarius_web_config.php
```

3. Найдите нужные параметры и измените их значения

## 🔑 Обязательные параметры (нужно изменить)

### 1. Подключение к базе данных

**Параметр:** `database_uri`

**Формат:**
```php
'database_uri' => 'pgsql:dbname=имя_базы;user=пользователь;password=пароль',
```

**Пример:**
```php
'database_uri' => 'pgsql:dbname=archivarius;user=archivarius_user;password=мой_пароль_123',
```

**Где взять:**
- `имя_базы` - имя базы данных, которую вы создали (обычно `archivarius`)
- `пользователь` - пользователь PostgreSQL (обычно `archivarius_user`)
- `пароль` - пароль пользователя PostgreSQL

---

### 2. URL сайта

**Параметр:** `site_url`

**Формат:**
```php
'site_url' => 'http://your-domain.com',
```

**Примеры:**
```php
'site_url' => 'http://62.60.157.148',           // Если используете IP
'site_url' => 'http://archivarius.example.com', // Если используете домен
'site_url' => 'https://archivarius.example.com', // Если используете HTTPS
```

**Важно:** 
- Указывайте полный URL с протоколом (`http://` или `https://`)
- Без слеша в конце
- Если сайт в подпапке, добавьте путь: `http://domain.com/path`

---

### 3. Базовый URL (если сайт не в корне)

**Параметр:** `base_url`

**Обычно оставляйте пустым:**
```php
'base_url' => '',
```

**Если сайт в подпапке:**
```php
'base_url' => '/subfolder',  // Например, если сайт в /archivarius/
```

---

### 4. Название сайта

**Параметр:** `site_name`

**Пример:**
```php
'site_name' => 'Archivarius - Управление документами',
```

---

### 5. Email для отправки писем

**Параметр:** `auth['site_email']`

**Пример:**
```php
'auth' => [
    // ... другие параметры ...
    'site_email' => 'noreply@your-domain.com',
    // ...
]
```

**Где используется:**
- Отправка писем для сброса пароля
- Отправка писем для активации аккаунта

---

### 6. URL для активации пользователя

**Параметр:** `user_activation_url`

**Пример:**
```php
'user_activation_url' => 'http://your-domain.com/user/',
```

**Важно:** Должен совпадать с `site_url` + `/user/`

---

## 🔧 Дополнительные параметры (опционально)

### Настройка SMTP (если нужна отправка писем)

**Параметры в секции `auth`:**
```php
'auth' => [
    'smtp' => true,                              // Включить SMTP
    'smtp_host' => 'smtp.gmail.com',            // SMTP сервер
    'smtp_port' => 587,                          // Порт (587 для TLS, 465 для SSL)
    'smtp_username' => 'your-email@gmail.com',   // Логин SMTP
    'smtp_password' => 'your-app-password',      // Пароль SMTP
    'smtp_security' => 'tls',                    // 'tls' или 'ssl'
]
```

**Пример для Gmail:**
```php
'smtp' => true,
'smtp_host' => 'smtp.gmail.com',
'smtp_port' => 587,
'smtp_username' => 'your-email@gmail.com',
'smtp_password' => 'your-app-specific-password',
'smtp_security' => 'tls',
```

---

### Настройка Manticore Search

**Параметр:** `sphinx_uri`

**Обычно не нужно менять:**
```php
'sphinx_uri' => 'mysql:unix_socket=/run/manticore/manticore.sock',
```

**Если Manticore на другом сервере:**
```php
'sphinx_uri' => 'mysql:host=127.0.0.1;port=9306',
```

---

### Режим отладки

**Параметр:** `debug`

**Для разработки:**
```php
'debug' => true,
```

**Для продакшена (рекомендуется):**
```php
'debug' => false,
```

---

### Настройка загрузки файлов

**Параметры в секции `upload`:**
```php
'upload' => [
    'path' => 'static/storage',        // Путь для хранения файлов (относительно public/)
    'url' => '/static/storage/',       // URL для доступа к файлам
    'number_files_per_directory' => 10000, // Максимум файлов в одной папке
]
```

**Обычно не нужно менять**, если только не хотите хранить файлы в другом месте.

---

### Часовой пояс

**Параметр:** `site_timezone`

**Примеры:**
```php
'site_timezone' => 'Europe/Moscow',    // Москва
'site_timezone' => 'Europe/Kiev',      // Киев
'site_timezone' => 'UTC',              // UTC
```

**Список всех часовых поясов:** https://www.php.net/manual/timezones.php

---

## 📋 Чек-лист настройки

Перед запуском проверьте, что изменили:

- [ ] `database_uri` - строка подключения к PostgreSQL
- [ ] `site_url` - URL вашего сайта
- [ ] `base_url` - базовый URL (обычно пустая строка)
- [ ] `site_name` - название сайта
- [ ] `auth['site_email']` - email для отправки писем
- [ ] `user_activation_url` - URL для активации (должен совпадать с site_url + /user/)
- [ ] `site_timezone` - часовой пояс (если не Москва)

## 🔒 Безопасность

После настройки ограничьте доступ к конфигу:

```bash
chmod 600 config/archivarius_web_config.php
```

Это позволит читать файл только владельцу (обычно `www-data`).

## ❓ Частые вопросы

**Q: Что делать, если сайт не открывается?**
A: Проверьте, что `site_url` указан правильно и совпадает с тем, что в браузере.

**Q: Письма не отправляются**
A: Проверьте настройки SMTP или убедитесь, что на сервере настроена отправка почты.

**Q: Ошибка подключения к базе данных**
A: Проверьте `database_uri` - имя базы, пользователь и пароль должны быть правильными.

**Q: Поиск не работает**
A: Проверьте, что Manticore запущен и `sphinx_uri` указан правильно.

## 📖 Полный список параметров

Для полного описания всех параметров см. комментарии в файле `config/archivarius_web_config.php.sample`

