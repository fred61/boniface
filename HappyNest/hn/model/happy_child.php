<?php
require_once 'conf/conf.php';
require_once 'lib/entity.php';
require_once 'lib/decorator.php';
require_once 'lib/log.php';

class HappyChild  implements DecoratedObject {
	use DecoratorTrait;
	
	public $parent;

	function __construct($dto)
	{
		$this->plainObject= $dto;
		$this->sessions= array();
	}
	
	public $sessions;			// array of SessionOccurence objects indexed by session id

	function applySessionData($sessionData, $asOf)
	{
		//sessionData: array of (validFrom, days) pairs indexed by session id
		foreach($sessionData as $sessionId=>$session)
		{
			if (!isset($this->sessions[$sessionId]) && $session['days'] != "") {
				// no sessions of this type at all yet and days are set, create new session
				$this->debug("new session: $sessionId");
				$this->sessions[$sessionId]= array();
				$this->createSession($sessionId, $session['days'], $session['validFrom']);
			} else {
				$this->debug("update of session $sessionId");
				//let's find out if all we need to do is change the days of the occurence
				$so= $this->getCurrentOccurence($this->sessions[$sessionId], $asOf);
				if (is_null($so)) {
					$so= $this->getNearestFutureOccurence($this->sessions[$sessionId], $asOf);
				}
				$this->debug("current occurence as of " . $asOf->format('Y-m-d'));
				$this->debug($so);
				
				if ($session['validFrom'] == $so->valid_from) {
					$this->debug('valid from matches, it\'s an update');
					$so->weekdays= $session['days'];
					$so->touch(); 
				} else {
					$this->debug('valid from mismatch, so:' . $so->valid_from . "; sd: " . $session['validFrom']);
					$this->createSession($sessionId, $session['days'], $session['validFrom']);
				}
			}
		}
	}
	
	function createSession($sessionId, $sessionDays, $validFrom)
	{
		$dto= new SessionOccurenceDTO();
		$dto->child_id= &$this->plainObject->id;
		//note: $this->id is not going to work. If $this is a new HappyChild, then ID is obviously not set at this point.
		//	also, I need to use the DTO to assign the reference because PHP won't let me do that through the magic method (fairy nuff) 
		
		$dto->session_id= $sessionId;
		$dto->valid_from= $validFrom;
		$dto->weekdays= $sessionDays;
		$so= new SessionOccurence($dto);
		$so->setNew();
		
		array_push($this->sessions[$sessionId], $so);
	}
	
	function getCurrentSessions($asOf)
	{
		$result= array();
		
		foreach($this->sessions as $sessionOccurences)
		{
			$so= $this->getCurrentOccurence($sessionOccurences, $asOf);
			if (!is_null($so)) {
				$result[$so->session_id]= $so;
			}
		}
		
		return $result;
	}
	
	function getNearestSessions($asOf)
	{
		$result= array();
		
		foreach($this->sessions as $sessionOccurences)
		{
			$so= $this->getCurrentOccurence($sessionOccurences, $asOf);
			if (is_null($so)) {
				$so= $this->getNearestFutureOccurence($sessionOccurences, $asOf);
			} 
			
			if (!is_null($so)) {
				$result[$so->session_id]= $so;
			}
		}
		
		return $result;
			}
	
	private function getCurrentOccurence($sessionOccurences, $asOf)
	{
		$result= null;
		
		foreach($sessionOccurences as &$so) {
			$vf= new DateTime($so->valid_from);
			$vt= new DateTime($so->valid_to);
		
			if ((is_null($so->valid_from) || $vf <= $asOf) && (is_null($so->valid_to) || $vt >= $asOf))
			{
				$result= &$so;
			}
		}
		
		return $result;
	}

	private function getNearestFutureOccurence($sessionOccurences, $asOf)
	{
		$result= null;
		
		foreach($sessionOccurences as &$so) {
			$vf= new DateTime($so->valid_from);
			$vt= new DateTime($so->valid_to);
		
			if ((is_null($so->valid_from) || $vf > $asOf) && (is_null($so->valid_to) || $vt >= $asOf))
			{
				$result= &$so;
			}
		}
		
		return $result;
	}

	static function fromRequest()
	{
		$result= new HappyChild();
		HappyParentFactory::copyFromRequest($result, "child");

		if (is_null($result->name)) {
			$result= NULL;
		} else {
			$date= new DateTime($result->date_of_birth);
			$result->date_of_birth= $date->format('Y-m-d');
		}
		$this->debugDump("result", $result);
		return $result;
	}
}

class HappyChildDTO {
	public $id;
	public $name;
	public $parent_id;
	public $date_of_birth;
	public $nickname;
	public $start_date;
	public $leave_date;
}

class HappyChildEntityAdapter implements Entity {
	use EntityTrait;

	public $adaptee;

	function __construct($adaptee) {
		$this->primaryKey= "id";
		$this->tableName= "child";
			
		$this->adaptee= $adaptee;
	}
}
?>