select
  id
  , name
  , notes
  , yields
  , directions
  , ingredients
  , tags
from
  one_line_recipes
where
  id = ?