-- Удаление дубликатов в user_role_user
-- Оставляет только одну запись для каждой пары (role_id, user_id)

DELETE FROM user_role_user
WHERE ctid NOT IN (
    SELECT MIN(ctid)
    FROM user_role_user
    GROUP BY role_id, user_id
);

