-- Обновление названия сайта на "Проект24"
-- Выполните этот файл на существующей базе данных

-- Обновление для русского языка (language_id = 2)
UPDATE public.setting 
SET text_value = 'Проект24' 
WHERE name = 'short_site_name' AND language_id = 2;

UPDATE public.setting 
SET text_value = 'Проект24. Электронный архив' 
WHERE name = 'site_name' AND language_id = 2;

UPDATE public.setting 
SET text_value = 'Проект24' 
WHERE name = 'header_logo_text' AND language_id = 2;

UPDATE public.setting 
SET text_value = 'Проект24. Панель управления' 
WHERE name = 'admin_site_name' AND language_id = 2;

UPDATE public.setting 
SET text_value = 'Проект24' 
WHERE name = 'admin_header_logo_text' AND language_id = 2;

-- Проверка изменений
SELECT name, text_value, language_id 
FROM public.setting 
WHERE name IN ('site_name', 'short_site_name', 'header_logo_text', 'admin_site_name', 'admin_header_logo_text') 
AND language_id = 2
ORDER BY name;

