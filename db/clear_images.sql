-- Очистка всех настроек, связанных с изображениями
-- Этот скрипт удаляет URL изображений из настроек, чтобы они не подтягивались из базы данных

-- Очистка header_logo_url для всех языков
UPDATE public.setting 
SET text_value = '' 
WHERE name = 'header_logo_url';

-- Очистка admin_header_logo_url для всех языков (если такая настройка существует)
UPDATE public.setting 
SET text_value = '' 
WHERE name = 'admin_header_logo_url';

-- Проверка изменений
SELECT name, text_value, language_id 
FROM public.setting 
WHERE name IN ('header_logo_url', 'admin_header_logo_url')
ORDER BY name, language_id;

