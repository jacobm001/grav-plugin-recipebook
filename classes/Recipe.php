<?php
	namespace Grav\Plugin;
	use \PDO;

	class Recipe 
	{
		private $uuid;
		public $name;
		public $yields;
		public $notes;
		public $directions;
		public $ingredients;
		private $tags = array();

		public function __construct(&$db, $uuid = null)
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
				$this->tags[] = $tag;
		}

		public function add_tag($tag)
		{
			$tag = trim($tag);
			if(!in_array($tag, $this->tags) and $tag != null) 
				$this->tags[] = $tag;
		}

		public function get_tags()
		{
			return $this->tags;
		}

		public function update_recipe()
		{
			$this->db->beginTransaction();

			$stmt = $this->prepare("
				update recipes set 
					name         = :name
					, notes      = :notes
					, yields     = :yields
					, directions = :directions
				where
					uuid = :uuid;"
			);

			$stmt->bindParam(':uuid', $this->uuid);
			$stmt->bindParam(':name', $this->name);
			$stmt->bindParam(':notes', $this->notes);
			$stmt->bindParam(':yields', $this->yields);
			$stmt->bindParam(':ingredients', $this->ingredients);
			$stmt->bindParam(':directions', $this->directions);
			$stmt->execute();

			$this->update_tags();

			$this->db->commit();
		}

		private function update_tags()
		{
			$stmt = $this->db->prepare("
				delete from tags where uuid = :uuid;
			");
			$stmt->bindParam(':uuid', $this->uuid);
			$stmt->execute();

			$stmt = $this->db->prepare("
				insert into tags(uuid, tag) values (:uuid, :tags); 
			");

			$stmt->bindParam(':uuid', $this->uuid);
			$stmt->bindParam(':tags', $this->tags);
			$stmt->execute();
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
