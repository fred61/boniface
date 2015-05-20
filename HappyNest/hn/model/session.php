<?php
class Session implements DecoratedObject {
	use DecoratorTrait;
	static $logger;
	
	const PLAYGROUP = 1;
	const ENGLISH = 2;
	const MUSIC = 3;
	const WAITING_LIST = 4;
	
	static function init()
	{
		self::$logger= new Logger(__CLASS__);
	}
	
	function __construct($dto)
	{
		$this->plainObject= $dto;
	}
	
	function isAvailableOn($weekDay)
	{
		self::$logger->debug("check availability of $weekDay against " . $this->plainObject->available_on);
		
		$result= strpos($this->plainObject->available_on, "$weekDay");
		
		if ($result === false) {
			return false;
		} else {
			return true;
		}
	}
}
Session::init();

class SessionDTO {
	public $id;
	public $name;
	public $available_on;
}

class SessionOccurence implements DecoratedObject {
	use DecoratorTrait;
	static $logger;
	
	static function init()
	{
		self::$logger= new Logger(__CLASS__);
	}
	
	
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
	
}
SessionOccurence::init();

class SessionOccurenceDTO {
	public $child_id;
	public $session_id;
	public $valid_from;
	public $weekdays;
}

class SessionOccurenceEntityAdapter implements Entity {
	use EntityTrait;

	static $logger;
	
	static function init()
	{
		self::$logger= new Logger(__CLASS__);
	}
		
		
	public $adaptee;

	function __construct($adaptee) {
		$this->primaryKey= array('child_id' =>'', 'session_id' =>'', 'valid_from' =>'');
		$this->tableName= "session_occurence";
		$this->autoPK= false;
			
		$this->adaptee= $adaptee;
	}
}
SessionOccurenceEntityAdapter::init();

//TODO primary key name and table name are static really. I'm not sure how to 
// set / use these with traits though. 
?>