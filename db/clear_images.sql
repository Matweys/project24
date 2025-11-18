-- Очистка настроек фоновых изображений
-- Этот скрипт очищает настройки, связанные с фоновыми изображениями
-- Примечание: header_logo_url НЕ очищается, так как логотип должен остаться

-- Очистка admin_header_logo_url для всех языков (если такая настройка существует)
UPDATE public.setting 
SET text_value = '' 
WHERE name = 'admin_header_logo_url';

-- Проверка изменений
SELECT name, text_value, language_id 
FROM public.setting 
WHERE name = 'admin_header_logo_url'
ORDER BY language_id;

