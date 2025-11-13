alter table public."user" drop constraint user_lang_id_fk;

alter table public."user" add column language_id integer;

alter table public."user" add constraint user_language_id_fk FOREIGN KEY (language_id) REFERENCES public.language(id) ON DELETE SET NULL ON UPDATE CASCADE;

update public."user" set language_id = lang_id;

alter table public."user" drop column lang_id;
