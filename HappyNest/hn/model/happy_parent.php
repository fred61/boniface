<?php
	require_once 'conf/conf.php';
	require_once 'lib/entity.php';
	require_once 'lib/log.php';
	require_once 'lib/decorator.php';
	
	class HappyParent implements DecoratedObject, JsonSerializable {
		use DecoratorTrait;
		
		public $children= array();
			
		function __construct($dto)
		{
			$this->plainObject= $dto;
		}
		
		function __toString()
		{
			return "HappyParent";
		}
		
		public function jsonSerialize()
		{
			$a= (array)$this->unwrap();
			$a['address']= "";
			$a['children']= $this->children;
		
			return $a;
		}
		
		static function fromRequest()
		{
			$result= new HappyParent();
			
			HappyParentFactory::copyFromRequest($result);
			
			$result->children[]= HappyChild::fromRequest();
			
			$this->debugDump("HappyParent from request", $result);
				
			return $result;
		}
	}
	
	class HappyParentDTO {
		public $id;
		public $name;
		public $salutation;
		public $address;
		public $email;
		public $phone_1;
		public $phone_2;
		public $phone_3;
	}
	
	class HappyParentEntityAdapter implements Entity {
		use EntityTrait;
		
		public $adaptee;

		function __construct($adaptee)
		{
			$this->primaryKey= "id";
			$this->tableName= "parent";
			
			$this->adaptee= $adaptee;
		}
		
	}
?>