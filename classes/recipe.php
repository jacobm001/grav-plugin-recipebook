<?php
	class Recipe implements JsonSerializable {
		private $id;
		private $name;
		private $yields;
		private $notes;
		private $ingredients = array();
		private $directions;
		private $tags = array();

		public function __construct($id, $name, $yields, $notes, $directions)
		{
			$this->id          = $id;
			$this->name        = $name;
			$this->yields      = $yields;
			$this->notes       = $notes;
			$this->directions  = $directions;
			$this->ingredients = array();
			$this->tags        = array();
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

		public function set_ingr($ingr_str)
		{
			if( $ingr_str != "" )
				$this->ingredients = explode('||', $ingr_str);
		}

		public function add_ingr($ingr)
		{
			$this->ingredients[] = $ingr;
		}

		public function get($req)
		{
			switch($req) {
				case 'id':
					return $this->id;
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
				'id'          => $this->id,
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