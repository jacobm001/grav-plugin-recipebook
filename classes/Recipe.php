<?php
	namespace Grav\Plugin;
	use \PDO;

	class Recipe
	{
		private $uuid;
		public $user;
		public $name;
		public $yields;
		public $notes;
		public $directions;
		public $ingredients;
		private $tags = [];

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
				$stmt->bindParam(':trial_uuid', $trial_uuid, PDO::PARAM_STR);
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

			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			foreach( $rows as $row ) {
				$this->tags[] = $row['tag'];
			}
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

		public function build_from_post($post)
		{
			$this->name        = $post['name'];
        	$this->notes       = $post['notes'];
        	$this->yields      = $post['yields'];
        	$this->ingredients = $post['ingredients'];
        	$this->directions  = $post['directions'];

        	// set the tags
        	$tags = explode(',', $post['tags']);
        	foreach( $tags as $tag )
	            $this->add_tag($tag);
		}

		public function set_user($user)
		{
			$this->user = $user;
		}

		public function save_recipe()
		{
			$this->db->beginTransaction();

			$stmt = $this->db->prepare("
				insert into recipes(uuid, user, name, notes, yields, ingredients, directions)
				  values(:uuid, :user, :name, :notes, :yields, :ingredients, :directions);"
			);

			$stmt->bindParam(':uuid', $this->uuid);
			$stmt->bindParam(':name', $this->name);
			$stmt->bindParam(':notes', $this->notes);
			$stmt->bindParam(':yields', $this->yields);
			$stmt->bindParam(':ingredients', $this->ingredients);
			$stmt->bindParam(':directions', $this->directions);
			$stmt->execute();

			$this->save_tags();

			$this->db->commit();
		}

		public function update_recipe()
		{
			$this->db->beginTransaction();

			$stmt = $this->db->prepare("
				update recipes set
					name          = :name
					, notes       = :notes
					, yields      = :yields
					, ingredients = :ingredients
					, directions  = :directions
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

			$this->delete_db_tags();
			$this->save_tags();

			$this->db->commit();
		}

		private function delete_db_tags()
		{
			$stmt = $this->db->prepare("
				delete from tags where uuid = :uuid;
			");
			$stmt->bindParam(':uuid', $this->uuid);
			$stmt->execute();
		}

		private function save_tags()
		{
			// $this->db->beginTransaction();

			foreach( $this->tags as $tag) {
				$stmt = $this->db->prepare("
					insert into tags(uuid, tag) values (:uuid, :tag);
				");

				$stmt->bindParam(':uuid', $this->uuid);
				$stmt->bindParam(':tag', $tag);
				$stmt->execute();
			}

			// $this->db->commit();
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

		public function get_slug()
		{
			return $this->uuid;
		}
	}
?>
