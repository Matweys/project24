
DROP PROCEDURE IF EXISTS throttle_update;
CREATE PROCEDURE throttle_update (ttl integer, src_id text)
AS $$
DECLARE
	new_expire timestamp with time zone;
BEGIN

	SELECT current_timestamp + ttl * interval '1 second' into new_expire;

	INSERT INTO throttle (id, count, expire)
		VALUES (src_id, 1, new_expire)
		ON CONFLICT (id) DO UPDATE
		SET count = (CASE WHEN throttle.expire > current_timestamp THEN throttle.count + 1 ELSE 1 END), expire = new_expire;

END;
$$ LANGUAGE plpgsql;
