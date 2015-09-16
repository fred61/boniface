<?php
require_once 'lib/log.php';

interface DecoratedObject
	{
		function unwrap();
		
		function __get($name);
		function __set($name, $value);
	}
	
	trait DecoratorTrait {
		use LoggerTrait;
				
		private $plainObject;
	
		public function unwrap()
		{
			return $this->plainObject;
		}
		
		public function __get($name) {
			self::debug("getting $name");
			
// 			if ($name == "plainObject") {
// 				return $this->plainObject;
// 			} else {
//TODO cleanup
				$a= (array)$this->plainObject;
				self::debug("got " . $a[$name]);
				return $a[$name];
// 			}
		}
		
		public function __set($name, $value) {
			self::debug("setting $name to $value");
			$this->plainObject->{$name}= $value;
		}
	}
	
?>
