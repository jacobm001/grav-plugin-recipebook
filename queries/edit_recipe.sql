update recipes
	set name     = :name
	, notes      = :notes
	, yields     = :yields
	, directions = :directions
 where
 	uuid = :uuid;