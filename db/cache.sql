
drop procedure if exists cache_inc (src_key text, ttl integer);
create procedure cache_inc (src_key text, ttl integer)
as $$
declare
	new_expire timestamp with time zone;
begin
	select current_timestamp + ttl * interval '1 second' into new_expire;

	insert into public.cache (bucket, expire, key, value)
		values (floor(extract(epoch from new_expire)) % 3, new_expire, src_key, cast(1 as text))
		on conflict (key, bucket) do update
		set expire = excluded.expire, value = (public.cache.value::bigint + 1)::text;
end;
$$ language plpgsql;


drop procedure if exists cache_set (src_key text, src_value text, ttl integer);
create procedure cache_set (src_key text, src_value text, ttl integer)
as $$
declare
	new_expire timestamp with time zone;
begin
	select current_timestamp + ttl * interval '1 second' into new_expire;

	insert into public.cache (bucket, expire, key, value)
		values (floor(extract(epoch from new_expire)) % 3, new_expire, src_key, src_value)
		on conflict (key, bucket) do update
		set expire = excluded.expire, value = excluded.value;
end;
$$ language plpgsql;
