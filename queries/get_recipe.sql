select
  uuid
  , name
  , notes
  , yields
  , directions
  , ingredients
  , tags
from
  one_line_recipes
where
  uuid = ?