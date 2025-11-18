-- Проверка всех настроек, которые могут содержать фоновые изображения
-- Выполните этот скрипт, чтобы найти настройки, связанные с фоном

-- Поиск всех настроек, содержащих слова связанные с фоном или изображениями
SELECT name, text_value, language_id, group_id
FROM public.setting 
WHERE name LIKE '%background%' 
   OR name LIKE '%header%background%'
   OR name LIKE '%footer%background%'
   OR name LIKE '%header%image%'
   OR name LIKE '%footer%image%'
   OR text_value LIKE '%background%'
   OR text_value LIKE '%url(%'
ORDER BY name, language_id;

-- Также проверим все настройки группы 'view' и 'admin_view'
SELECT s.name, s.text_value, s.language_id, sg.name as group_name
FROM public.setting s
LEFT JOIN public.setting_group sg ON sg.id = s.group_id
WHERE sg.name IN ('view', 'admin_view')
ORDER BY s.name, s.language_id;

