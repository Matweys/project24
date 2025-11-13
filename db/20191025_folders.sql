alter table file_%s add column folder boolean;

alter table file_%s add column text text default null;

alter table file_%s alter column file drop not null;

alter table file_%s add constraint file_%s_parent_id_fk foreign key (parent_id) references file_%s(id) on delete cascade on update cascade;

create unique index file_%s_parent_id_null_name_key on file_%s (name) where parent_id is null;
