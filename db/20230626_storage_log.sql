alter table public.log add column storage_id integer;
alter table public.log add constraint log_storage_id_fk foreign key (storage_id) references public.storage(id) on delete set null on update cascade;

--update log set storage_id = cast(nullif(substring(message from 'storage #(\d+)'), '') as integer);

with subquery as (select log.id as log_id, storage.id as storage_id from log left join storage on storage.id = cast(nullif(substring(log.message from 'storage #(\d+)'), '') as integer)) update log set storage_id = subquery.storage_id from subquery where log.id = subquery.log_id;
