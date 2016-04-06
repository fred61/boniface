<?php

chdir('../../..');

require_once 'model/model.php';
require_once 'lib/service.php';
require_once 'lib/log.php';

class ParentHandler
{
	use LoggerTrait;
	
	public function get() {
		$this->debug("get parent handler");
		
		$parents= ModelFactory::getAllParents();
		$result= array();
		
		foreach($parents as $parent) {
			array_push($result, $parent);		// this way $result is an array
// 			$result[$parent->id]= $parent;		// this way $result is a map (an object on the client side)
		}
		
		$this->debugDump("parents", $parents);
		$this->debugDump("result", $result);
		
		return $result;
			
	}
	
	public function post($postData) {
		$happyParent= $this->deserialiseHappyParent($postData);
		$this->info($happyParent);
 		ModelFactory::putParent($happyParent);
		
		return $happyParent;
	}
	
	private function deserialiseHappyParent($data)
	{
		$dto= new HappyParentDTO();
		$this->gatherObjectProps($dto, $data);
		$happyParent= new HappyParent($dto);
		
		foreach($data['children'] as $childData) 
		{
			array_push($happyParent->children, $this->deserialiseHappyChild($childData));
		}
		
		return $happyParent;
	}
	
	private function deserialiseHappyChild($data)
	{
		$dto= new HappyChildDTO();
		$this->gatherObjectProps($dto, $data);
	  $happyChild= new HappyChild($dto);
	  
	  foreach($data['sessions'] as $sessionId => $sessionData) {
	  	$happyChild->sessions[$sessionId]= $this->deserialiseSessionOccurences($sessionData);
	  }
		
		return $happyChild;
	}
	
	private function deserialiseSessionOccurences($data)
	{
		$result= array();
		
		foreach($data as $occurenceData) {
			$soDTO= new SessionOccurenceDTO();
			$this->gatherObjectProps($soDTO, $occurenceData);
			$occurence= new SessionOccurence($soDTO);
			
			$sessionDTO= new SessionDTO();
			$this->gatherObjectProps($sessionDTO, $occurenceData['session']);
			$occurence->session= new Session($sessionDTO);
			
			$result[]= $occurence; 
		}
		
		return $result;
	} 
	
	private function gatherObjectProps($object, $data)
	{
		foreach($object as $prop => $dummy) {
			if (isset($data[$prop]) || array_key_exists($prop, $data)) {
				$object->{$prop}= $data[$prop];
			}
		}
	}
}

$start= microtime(true);			// parameter is get_as_float - if set to false you get a string.
ModelFactory::initialise();
$initialised= microtime(true);
$svc= new Service($_REQUEST, new ParentHandler());

RootLogger::info("about to handle");

echo $svc->handle();
$handled= microtime(true);

RootLogger::info("that's all folks");
RootLogger::info("initialised in " . ($initialised - $start));
RootLogger::info("handled in " . ($handled - $initialised));
?>