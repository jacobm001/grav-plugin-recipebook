create table recipes(
  uuid text primary key,
  user text,
  name text,
  notes text,
  yields text,
  directions text
);

create table tags (
  uuid text,
  tag text,
  primary key(uuid, tag),
  foreign key(uuid) references recipes(uuid)
);

create table ingredients (
  uuid text,
  ingredient text,
  primary key(uuid, ingredient),
  foreign key(recipe_id) references recipes(id)
);

create index indx_tags_01        on tags(uuid);
create index indx_tags_02        on tags(tag);
create index indx_ingredients_01 on ingredients(uuid);

CREATE VIEW one_line_recipes as
  select
    recipes.uuid
    , recipes.id
    , recipes.name
    , recipes.notes
    , recipes.yields
    , recipes.directions
    , (
      select
        group_concat(ingredients.ingredient, '||')
      from
        ingredients
      where
        ingredients.uuid = recipes.uuid
    ) as ingredients
    , (
      select
        group_concat(tags.tag, '||')
      from
        tags
      where
        tags.uuid = recipes.uuid
    ) as tags
  from
    recipes
  order by
    lower(recipes.name);
