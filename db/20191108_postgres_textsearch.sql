alter table public."user" add column description text;
alter table public."user" add column search tsvector generated always as (to_tsvector('russian', coalesce(description, '') || ' ' || coalesce(email, '') || ' ' || coalesce(name, ''))) stored;

drop index if exists user_search_key;
create index concurrently user_search_key on public."user" using gin (search);

alter table public.storage add column description text;
alter table public.storage add column search tsvector generated always as (to_tsvector('russian', coalesce(description, '') || ' ' || coalesce(title, '') || ' ' || coalesce(uid, ''))) stored;

drop index if exists storage_search_key;
create index concurrently storage_search_key on public.storage using gin (search);
