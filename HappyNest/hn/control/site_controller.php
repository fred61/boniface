<?php
	require_once 'lib/lib.php';
	
	if (session_id() == "") {
		session_start();
	}
	
	class SiteController {
		static function mark() {
			$_SESSION['backlink']= $_SERVER['REQUEST_URI'];
		}
		
		static function back() {
			if (isset($_SESSION['backlink'])) {
				redirect($_SESSION['backlink']);
			}
		}
		
		static function getAsOf()
		{
			$asOf= getDateFromRequest('asOf');
			
// 			$logger->debug(" asOf: [" . $asOf->format("Y-m-d H:i:s") . "]");
//TODO re-enable this once I have made logging into a trait
			return $asOf;
		}
		
		static function getCalAsOf()
		{
			$calAsOf= getDateFromRequest('calAsOf');
// 			$logger->debug(" calAsOf: [" . $calAsOf->format("Y-m-d H:i:s") . "]");
			//TODO re-enable this once I have made logging into a trait
					
			return $calAsOf;
		}
		
		static function getParent()
		{
			if (!array_key_exists('id', $_REQUEST) || !is_numeric($_REQUEST['id'])) {
				$happyParent= ModelFactory::makeNewParent();
			} else {
				$id= $_REQUEST['id'];
// 				$logger->debug("got a parent ID: " . $id);
				$happyParent= ModelFactory::getSingleParent($id);
			
// 				$logger->debugDump("happyParent", $happyParent);
//TODO re-enable this once I have made logging into a trait
			}

			return $happyParent;
		}
		
	}