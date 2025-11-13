alter table public.user alter column isactive drop not null;
alter table public.user alter column isactive drop default;

alter table public.user alter column isactive type boolean using case when isactive = 1 then true else null end;
