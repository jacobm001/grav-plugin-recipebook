create table recipes(
  id integer primary key autoincrement,
  uuid text,
  user text,
  name text,
  notes text,
  yields text,
  directions text
);

create table tags (
  id integer primary key autoincrement,
  recipe_id integer,
  tag text,
  foreign key(recipe_id) references recipes(id)
);

create table ingredients (
  id integer primary key autoincrement,
  recipe_id integer,
  ingredient text,
  foreign key(recipe_id) references recipes(id)
);

create index indx_tags_01        on tags(recipe_id);
create index indx_ingredients_01 on ingredients(recipe_id);

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
        ingredients.recipe_id = recipes.id
    ) as ingredients
    , (
      select
        group_concat(tags.tag, '||')
      from
        tags
      where
        tags.recipe_id = recipes.id
    ) as tags
  from
    recipes
  order by
    lower(recipes.name);
