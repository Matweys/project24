
DROP PROCEDURE IF EXISTS storage_delete_folder;
CREATE PROCEDURE storage_delete_folder (storage_id integer, src_id bigint)
LANGUAGE plpgsql
AS $$
DECLARE
	src_lft bigint;
	src_rgt bigint;
	width bigint;
BEGIN

	EXECUTE format(
		'SELECT
			lft, rgt, (rgt - lft + 1)
			FROM file_%s
			WHERE id = $1 AND folder IS TRUE', storage_id)
		INTO src_lft, src_rgt, width
		USING src_id;

	IF src_lft > 0 AND src_rgt > 0 THEN

		EXECUTE format('DELETE FROM file_%s WHERE lft BETWEEN $1 AND $2', storage_id) USING src_lft, src_rgt;

		EXECUTE format('UPDATE file_%s SET lft = lft - $2 WHERE lft > $1', storage_id) USING src_rgt, width;
		EXECUTE format('UPDATE file_%s SET rgt = rgt - $2 WHERE rgt > $1', storage_id) USING src_rgt, width;

	END IF;
END;
$$;


DROP PROCEDURE IF EXISTS storage_new_folder;
CREATE PROCEDURE storage_new_folder (storage_id integer, folder_name text, IN parent_folder_id bigint)
LANGUAGE plpgsql
AS $$
DECLARE
	margin integer;
BEGIN
	margin := NULL;

	IF parent_folder_id > 0 THEN
		EXECUTE format('SELECT rgt FROM file_%s WHERE id = $1 AND folder IS TRUE', storage_id) INTO margin USING parent_folder_id;
	END IF;

	IF margin > 1 THEN
		EXECUTE format('UPDATE file_%s SET rgt = rgt + 2 WHERE rgt >= $1 AND folder IS TRUE', storage_id) USING margin;
		EXECUTE format('UPDATE file_%s SET lft = lft + 2 WHERE lft >= $1 AND folder IS TRUE', storage_id) USING margin;
	ELSE
		EXECUTE format('SELECT COALESCE(MAX(rgt), 0) + 1 FROM file_%s WHERE folder IS TRUE', storage_id) INTO margin;
	END IF;

	EXECUTE format(
		'INSERT INTO
			file_%s (
				folder,
				lft,
				name,
				parent_id,
				rgt
			)
			VALUES (
				true,
				$1,
				$2,
				$3,
				$4
			)', storage_id)
		USING
			margin,
			folder_name,
			parent_folder_id,
			margin + 1;

END;
$$;


DROP PROCEDURE IF EXISTS storage_folder_move_to;
CREATE PROCEDURE storage_folder_move_to (storage_id integer, src_id bigint, dest_folder_id bigint)
LANGUAGE plpgsql
AS $$
DECLARE
	new_position bigint;
BEGIN

	EXECUTE format(
		'SELECT rgt
			FROM file_%s
			WHERE id = $1 AND folder IS TRUE', storage_id)
		INTO new_position
		USING dest_folder_id;

	IF new_position is NULL OR new_position < 2 THEN

		EXECUTE format(
			'SELECT
				COALESCE(MAX(rgt), 0) + 1
				FROM file_%s
				WHERE folder IS TRUE', storage_id)
			INTO new_position;

	END IF;

	CALL __storage_folder_move(storage_id, src_id, new_position, dest_folder_id);

END;
$$;


DROP PROCEDURE IF EXISTS __storage_folder_move;
CREATE PROCEDURE __storage_folder_move (storage_id integer, src_id bigint, new_position bigint, dest_folder_id bigint)
LANGUAGE plpgsql
AS $$
DECLARE
	src_lft bigint;
	src_rgt bigint;
BEGIN

	EXECUTE format(
		'SELECT
			lft, rgt
			FROM file_%s
			WHERE id = $1 AND folder IS TRUE', storage_id)
		INTO src_lft, src_rgt
		USING src_id;

	IF src_lft > 0 AND src_rgt > 0 THEN

		IF new_position < src_lft THEN

			EXECUTE format(
				'UPDATE file_%s
					SET
						lft = lft + CASE
							WHEN lft BETWEEN $2 AND $3 THEN
								$1 - $2
							WHEN lft BETWEEN $1 AND $2 - 1 THEN
								$3 - $2 + 1
							ELSE 0
						END,

						rgt = rgt + CASE
							WHEN rgt BETWEEN $2 AND $3 THEN
								$1 - $2
							WHEN rgt BETWEEN $1 AND $2 - 1 THEN
								$3 - $2 + 1
							ELSE 0
						END

					WHERE
						(lft BETWEEN $1 AND $3
							OR rgt BETWEEN $1 AND $3)
						AND folder IS TRUE', storage_id)
				USING
					new_position,
					src_lft,
					src_rgt;

			EXECUTE format(
				'UPDATE file_%s SET parent_id = $1 WHERE id = $2 AND folder IS TRUE', storage_id)
				USING
					dest_folder_id,
					src_id;

		ELSEIF new_position > src_rgt THEN

			EXECUTE format(
				'UPDATE file_%s
					SET
						lft = lft + CASE
							WHEN lft BETWEEN $2 AND $3 THEN
								$1 - $3 - 1
							WHEN lft BETWEEN $3 + 1 AND $1 - 1 THEN
								$2 - $3 - 1
							ELSE 0
						END,

						rgt = rgt + CASE
							WHEN rgt BETWEEN $2 AND $3 THEN
								$1 - $3 - 1
							WHEN rgt BETWEEN $3 + 1 AND $1 - 1 THEN
								$2 - $3 - 1
							ELSE 0
						END

					WHERE
						(lft BETWEEN $2 AND $1
							OR rgt BETWEEN $2 AND $1)
						AND folder IS TRUE', storage_id)
				USING
					new_position,
					src_lft,
					src_rgt;

			EXECUTE format(
				'UPDATE file_%s SET parent_id = $1 WHERE id = $2 AND folder IS TRUE', storage_id)
				USING
					dest_folder_id,
					src_id;

		END IF;
	END IF;
END;
$$;
