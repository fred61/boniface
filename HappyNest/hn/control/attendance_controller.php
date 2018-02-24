<?php
	require_once 'model/model.php';
	
	class AttendanceController {
		
		static function getAsOf() {
			$asOf= getDateFromRequest('asOf');

			if (isset($_POST['action'])) {
				$action= $_POST['action'];
			
				if ($action == ">") {
					$asOf= $asOf->add(new DateInterval('P7D'));
				} else if ($action == "<") {
					$asOf= $asOf->sub(new DateInterval('P7D'));
				}
			}
			
			return $asOf;
				
		}
		
		static function getSessionsToShow()
		{
			$allSessions= ModelFactory::getAllSessions();
			
			$result= array();
			 
			if (isset($_POST['action']) && $_POST['action'] == 'Show') {
				foreach($allSessions as $sessionId=>$session) {
					$ctrlName= "session_${sessionId}";
					if (isset($_POST[$ctrlName])) {
						$result[$sessionId]= '';
					}
				}
			} else {
				$result[1] = '';	// only show playgroup
			}
		
			return $result;
		}
		
	}
