-- Восстановление header_logo_url (если нужно вернуть логотип)
-- Этот скрипт восстанавливает значения header_logo_url из исходных настроек

-- Восстановление header_logo_url для английского языка (language_id = 1)
UPDATE public.setting 
SET text_value = '/static/img/archivarius_logo.png' 
WHERE name = 'header_logo_url' AND language_id = 1;

-- Восстановление header_logo_url для русского языка (language_id = 2)
UPDATE public.setting 
SET text_value = '/static/img/archivarius_logo.png' 
WHERE name = 'header_logo_url' AND language_id = 2;

-- Проверка изменений
SELECT name, text_value, language_id 
FROM public.setting 
WHERE name = 'header_logo_url'
ORDER BY language_id;

