-- Удаление дубликатов в storage_user_permission
-- Оставляет только одну запись для каждой пары (storage_id, user_id)

DELETE FROM storage_user_permission
WHERE ctid NOT IN (
    SELECT MIN(ctid)
    FROM storage_user_permission
    GROUP BY storage_id, user_id
);

