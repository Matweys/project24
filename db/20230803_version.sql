drop table if exists public.version cascade;
create table public.version (
	versionid text not null
);
insert into public.version (versionid) values ('20230803');
