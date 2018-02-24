<?php
	require_once 'lib/lib.php';
	require_once 'lib/log.php';
	
	if (session_id() == "") {
		session_start();
	}
	
	class SiteController {
		use LoggerTrait;
		
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
			
			self::debug(" asOf: [" . $asOf->format("Y-m-d H:i:s") . "]");
			return $asOf;
		}
		
		static function getCalAsOf()
		{
			$calAsOf= getDateFromRequest('calAsOf');
			self::debug(" calAsOf: [" . $calAsOf->format("Y-m-d H:i:s") . "]");
					
			return $calAsOf;
		}
		
		static function getParent()
		{
			if (!array_key_exists('id', $_REQUEST) || !is_numeric($_REQUEST['id'])) {
				$happyParent= ModelFactory::makeNewParent();
			} else {
				$id= $_REQUEST['id'];
				self::debug("got a parent ID: " . $id);
				$happyParent= ModelFactory::getSingleParent($id);
			
				self::debugDump("happyParent", $happyParent);
			}

			return $happyParent;
		}
		
	}