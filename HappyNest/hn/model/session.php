<?php
require_once 'lib/log.php';

class Session implements DecoratedObject, JsonSerializable {
	use DecoratorTrait;
	
	const PLAYGROUP = 1;
	const ENGLISH = 2;
	const MUSIC = 3;
	const WAITING_LIST = 4;
	
	function __construct($dto)
	{
		$this->plainObject= $dto;
	}
	
	function isAvailableOn($weekDay)
	{
		$this->debug("check availability of $weekDay against " . $this->plainObject->available_on);
		
		$result= strpos($this->plainObject->available_on, "$weekDay");
		
		if ($result === false) {
			return false;
		} else {
			return true;
		}
	}
	
	public function jsonSerialize()
	{
		$a= (array)$this->unwrap();
		
		return $a;
	}
}

class SessionDTO {
	public $id;
	public $name;
	public $available_on;
	public $cost;
}

class SessionOccurence implements DecoratedObject, JsonSerializable {
	use DecoratorTrait;
	
	public $session;
	public $valid_to;
	private $isTouched= false;
	private $isNew    = false;
	
	function isOnWeekday($weekDay)
	{
		$result= strpos($this->plainObject->weekdays, "$weekDay");
		
		if ($result === false) {
			return false;
		} else {
			return true;
		}
	}
	
	function touch()
	{
		$this->isTouched= true;
	}
	
	function setNew()
	{
		$this->isNew= true;
	}
	
	function isTouched()
	{
		return $this->isTouched;
	}
	
	function isNew()
	{
		return $this->isNew;
	}
	
	function __construct($dto)
	{
		$this->plainObject= $dto;
	}
	
	public function jsonSerialize()
	{
		$a= (array)$this->unwrap();
		$a['valid_to']= $this->valid_to;
		$a['session']= $this->session;
		
		return $a;
	}
}

class SessionOccurenceDTO {
	public $child_id;
	public $session_id;
	public $valid_from;
	public $weekdays;
}

class SessionOccurenceEntityAdapter implements Entity {
	use EntityTrait;

	public $adaptee;

	function __construct($adaptee) {
		$this->primaryKey= array('child_id' =>'', 'session_id' =>'', 'valid_from' =>'');
		$this->tableName= "session_occurence";
		$this->autoPK= false;
			
		$this->adaptee= $adaptee;
	}
}

//TODO primary key name and table name are static really. I'm not sure how to 
// set / use these with traits though. 
?>