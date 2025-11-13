drop table if exists public.cache cascade;
create table public.cache (
  bucket smallint not null,
  expire timestamp with time zone not null,
  key text not null,
  value text,
  primary key (key, bucket)
) partition by range (bucket);

create table public.cache_p0 partition of public.cache for values from (0) to (1);
create table public.cache_p1 partition of public.cache for values from (1) to (2);
create table public.cache_p2 partition of public.cache for values from (2) to (32767);

drop index if exists cache__bucket__key;
create index if not exists cache__bucket__key on public.cache (bucket);

drop index if exists cache__expire__key;
create index if not exists cache__expire__key on public.cache (expire);
