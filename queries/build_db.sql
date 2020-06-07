create table recipes(
  uuid text primary key,
  user text,
  name text,
  notes text,
  yields text,
  instruction text,
  directions text
);

create table tags (
  uuid text,
  tag text,
  primary key(uuid, tag),
  foreign key(uuid) references recipes(uuid)
);

create index indx_tags_01        on tags(uuid);
create index indx_tags_02        on tags(tag);
