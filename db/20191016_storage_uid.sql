alter table public.storage add column uid character varying(100);

alter table public.storage alter column uid set not null;

alter table public.storage add constraint storage_uid_key unique(uid);
