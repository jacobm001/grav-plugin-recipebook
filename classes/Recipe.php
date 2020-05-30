<?php
	namespace Grav\Plugin;
	use \PDO;

	class Recipe 
	{
		private $uuid;
		private $name;
		private $yields;
		private $notes;
		private $directions;
		private $ingredients;
		private $tags = array();

		public function __construct($uuid = null)
		{
			$this->db = $db;
			
			if( is_null($uuid) ) {
				$this->uuid = $this->make_new_uuid();
				return;
			}
			else {
				$this->uuid = $uuid;
				$this->populate_recipe();
				$this->populate_tags();
			}
		}

		private function make_new_uuid()
		{
			while( True ) {
				$trial_uuid = uniqid();
				
				$stmt = $this->db->prepare('select 1 from recipes where uuid = :trial_uuid;');
				$stmt->bindParam(':trial_uuid', $trial_uuid, PDO::PARAM_INT);
				$stmt->execute();

				if( $stmt->fetch() == false )
					return $trial_uuid;
			}
		}

		private function populate_recipe()
		{
			$qry = "
				select 
					user, name, notes, yields, ingredients, directions
				from
					recipes
				where
					uuid = :uuid;
			";

			$stmt = $this->db->prepare($qry);
			$stmt->bindParam(':uuid', $this->uuid, PDO::PARAM_STR);
			$stmt->execute();

			$res = $stmt->fetch(PDO::FETCH_ASSOC);

			$this->user        = $res['user'];
			$this->name        = $res['name'];
			$this->notes       = $res['notes'];
			$this->yields      = $res['yields'];
			$this->ingredients = $res['ingredients'];
			$this->directions  = $res['directions'];
		}

		private function populate_tags()
		{
			$qry = "
				select tag
				from tags
				where uuid = :uuid;
			";

			$stmt = $this->db->prepare($qry);
			$stmt->bindParam(':uuid', $this->uuid);
			$stmt->execute();

			$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
			foreach( $res as $tag )
			{
				$this->tags[] = $tag;
			}
		}

		public function set_tag($tag_str)
		{
			if( $tag_str != "" )
				$this->tags = explode('||', $tag_str);
		}

		public function add_tag($tag)
		{
			if(!in_array($tag, $this->tags) and $tag != null) 
				$this->tags[] = $tag;
		}

		public function get($req)
		{
			switch($req) {
				case 'uuid':
					return $this->uuid;
				case 'name':
					return $this->name;
				case 'yields':
					return $this->yields;
				case 'notes':
					return $this->notes;
				case 'ingredients':
					return $this->ingredients;
				case 'directions':
					return $this->directions;
				case 'tags':
					return $this->tags;
				default:
					return -17;
			}
		}

		public function jsonSerialize() 
		{
			return array(
				'uuid'        => $this->uuid,
				'name'        => $this->name,
				'yields'      => $this->yields,
				'notes'       => $this->notes,
				'ingredients' => $this->ingredients,
				'directions'  => $this->directions,
				'tags'        => $this->tags
			);
		}
	}
?>