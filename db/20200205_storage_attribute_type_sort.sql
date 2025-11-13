alter table public.storage_attribute_type add column sort integer;

update public.storage_attribute_type set sort = 1 where name = 'string';
