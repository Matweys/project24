Archivarius Web
===

## Требования программы

* PostgreSQL 12 и выше
* nginx mod-zip (https://github.com/evanmiller/mod_zip)
* PHP 8.0 и выше 
* php-bcmath
* php-gd или php-imagick
* php-intl
* php-mysql (для Manticore)
* php-pgsql
* php-session
* php-zip


## Установка и настройка программы

Установите и настройте PHP и веб-сервер

Отредактируйте конфигурационный файл программы

	app/config.php

Создайте базу данных и загрузите начальные данные в таблицы

	psql --set ON_ERROR_STOP=on -U dbuser dbname < database_scheme.sql
	psql --set ON_ERROR_STOP=on -U dbuser dbname < cache.sql
	psql --set ON_ERROR_STOP=on -U dbuser dbname < storage_folders.sql
	psql --set ON_ERROR_STOP=on -U dbuser dbname < throttle.sql

или

	gunzip -c database_dump.sql.gz | psql --set ON_ERROR_STOP=on -U dbuser dbname

Добавьте супер администратора

	psql --dbname="postgresql:///dbname?user=dbuser&passfile=pgpass" -c "insert into public.user (email, isactive) values ('your_email@domain.tld', true); insert into user_role_user (role_id, user_id) values (1, 1), (2, 1), (3, 1);"

При необходимости запустите веб-сервер разработчика

	php -d display_errors=1 -d error_reporting=32767 -d post_max_size=50M -d upload_max_filesize=50M -S localhost:8080 -t public

Установите пароль пользователя

	http://localhost:8080/forgot_password

Настройте запуск заданий из app/tasks в cron

При необходимости создайте резервную копию базы данных

    pg_dump --dbname="postgresql:///database?user=&passfile=.pgpass" --no-owner | gzip -9 -c > database.sql.gz

Чтобы сохранить схему базы данных

	pg_dump --dbname="postgresql:///database?user=&passfile=.pgpass" --no-owner --schema-only -f database_scheme.sql


## Code formating

	php-cs-fixer fix --rules=@PhpCsFixer,-blank_line_before_statement,-combine_consecutive_issets,-concat_space,-increment_style,-yoda_style file.php


## Перевод

	bin/translations update
	
Перевести po-файлы

	(cd main; npx po2json-gettextjs i18n/main_ru.po i18n/translations.json)
	bin/translations build

## Справка

ffmpeg -i input.mov -c:v libx264 -framerate 20 -preset slow -crf 18 -profile:v baseline -level 3.0 -pix_fmt yuv420p -movflags +faststart -an output.mp4
ffmpeg -i input.mov -c:v libvpx-vp9 -crf 30 -pix_fmt yuv420p -an output.webm
