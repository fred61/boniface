<?php
	require_once 'lib/lib.php';
	require_once 'lib/log.php';
	require_once 'model/model.php';
	require_once 'control/site_controller.php';

	$logger= new Logger();
	
	function makeWeekdayColumns($childIndex, $session, $asOf, $happyChild)
	{
		global $logger;
		$result= "";
		
		$sessions= $happyChild->getSessions($asOf);
		$logger->debugDump("sessions as of  " . $asOf->format('Y-m-d'), $sessions);
		
		if (isset($sessions) && isset($sessions[$session->id]))
		{
			$sessionOccurence= $sessions[$session->id];
		} else {
			$sessionOccurence= null;
		}
		
		
		for($i= 1; $i <= 5; $i++) {
			$result= $result . "<td>";
		
			if (isset($sessionOccurence) && $sessionOccurence->isOnWeekday($i)) {
				$ctrlValue= 'checked';
			} else {
				$ctrlValue= '';
			}
			
			if ($session->isAvailableOn($i)) {
				$result= $result . '<input type=checkbox onclick="setValidFrom(\'' . SessionDataTranslator::makeValidFromControlName($childIndex, $session->id); 
				$result= $result . '\')"  name="' . SessionDataTranslator::makeCheckboxControlName($childIndex, $session->id, $i) . '" ' . $ctrlValue . '>';
			} else {
				$result= $result . "&nbsp;";
			}
			$result= $result . "</td>";
		}
				
		return $result;
	}
	
	function makeValidFromInput($childIndex, $sessionId, $asOf, $child)
	{
		$sessions= $child->getSessions($asOf);
		if (isset($sessions) && isset($sessions[$sessionId])) {
			$sessionValidFrom= $sessions[$sessionId]->valid_from;
		} else {
			$sessionValidFrom= "";
		}
		$controlName= SessionDataTranslator::makeValidFromControlName($childIndex, $sessionId);
		
		$result= "<input type=\"text\" name=\"$controlName\"  id=\"$controlName\" value=\"$sessionValidFrom\">";

		return $result;
	}
	
	class SessionDataTranslator
	{
		const CB_CONTROL_NAME_PATTERN= "child_%d_session_%d_day_%d";
// 		const VF_CONTROL_NAME_PATTERN= ""child_${childIndex}_session_${sessionId}_valid_from";"
		const VF_CONTROL_NAME_PATTERN= "child_%d_session_%d_valid_from";
				
		static function makeCheckboxControlName($childIndex, $sessionId, $sessionDay)
		{
			return sprintf(self::CB_CONTROL_NAME_PATTERN, $childIndex, $sessionId, $sessionDay);
		}
		
		static function makeValidFromControlName($childIndex, $sessionId)
		{
			return sprintf(self::VF_CONTROL_NAME_PATTERN, $childIndex, $sessionId);
		}
		
		static function getCheckboxIndices($controlName) {
			$result= array();
			
			$parsedValues= sscanf($controlName, self::CB_CONTROL_NAME_PATTERN, $result['childIdx'], $result['sessionId'], $result['sessionDay']);
			
			if ($parsedValues != 3) {
				return NULL;
			} else {
				return $result;
			}
		}
	}
	
	function sessionDataFromPost()
	{
		global $sessions;
		$result= array_fill(0, $_POST['childCount'], null);
		
		foreach($_POST as $requestKey => $value) {
			$sessionIndices= SessionDataTranslator::getCheckboxIndices($requestKey);
			
			if ($sessionIndices != null) {
				$childIdx= $sessionIndices['childIdx'];
				$sessionId= $sessionIndices['sessionId'];
				$sessionDay= $sessionIndices['sessionDay'];
				
				if (is_null($result[$childIdx])) {
					$result[$childIdx]= array();
				}
				if (isset($result[$childIdx][$sessionId])) {
					$result[$childIdx][$sessionId]['days']= $result[$childIdx][$sessionId]['days'] . "," . $sessionDay;
				} else {
					$result[$childIdx][$sessionId] = array(
							'validFrom' => $_POST[SessionDataTranslator::makeValidFromControlName($childIdx, $sessionId)]
						 ,'days'      => $sessionDay);
				}
			}
		}
		
		for($i= 0; $i < $_POST['childCount']; $i++) {
			foreach($sessions as $session) {
				$sessionId= $session->id;
				$vf= $_POST["child_${i}_session_${sessionId}_valid_from"];
					
				if ($vf <> '' && !isset($result[$i][$session->id])) {
					$result[$i][$sessionId] = array(
							'validFrom' => $vf
							,'days'     => "");
				}
			}
		}
		
		
		return $result;
	}
	
	$logger->infoDump("Request", $_REQUEST);
	
	$asOf= SiteController::getAsOf();
	
	ModelFactory::initialise();
	$sessions= ModelFactory::getAllSessions();
	
	$happyParent= SiteController::getParent();
	
	if (array_key_exists('action', $_POST)) {
		$logger->debug("copy from request");
		ModelFactory::copyFromRequest($happyParent);
		
		$sessionData= sessionDataFromPost();
		
		$logger->debugDump("session data from request", $sessionData);
		
		for($i= 0; $i < $_POST['childCount']; $i++) {
			if (array_key_exists($i, $happyParent->children)) {
				ModelFactory::copyFromRequest($happyParent->children[$i], "child", $i);
				
				$happyParent->children[$i]->applySessionData($sessionData[$i], $asOf);
			} 
			// else a new HappyChild was created by JS on the page so make a new HappyChild and copy
		}
		$logger->debugDump("happy parent after processing", $happyParent);
		
		if ($_POST['action'] == "Save") {
			ModelFactory::putParent($happyParent);
		}
		
 		SiteController::back();
	}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Happy Nest Parents</title>
    <link rel="stylesheet" type="text/css" href="hnstyle.css">
    <script type="text/javascript">
    	function setValidFrom(targetId)
    	{
        	console.exception("set valid from");
        	var targetElement= document.getElementById(targetId);
        	var sourceElement= document.getElementById("asOf");

        	targetElement.value= sourceElement.value;
			}
    </script>
  </head>
  <body>
  	<form method="post">
  		<div>
  			<h2>Parent</h2>
  			<input type="hidden" name="id" value="<?= $happyParent->id?>">
  			<input type="hidden" name="asOf" id="asOf" value="<?= $asOf->format('Y-m-d')?>">
	  		<table>
	  			<tr>
	  				<td>Name:</td><td><input type="text" name="name" value="<?= $happyParent->name?>" size=60></td>
	 				</tr>
	  			<tr>
	  				<td>Salutation:</td><td><input type="text" name="salutation" value="<?= $happyParent->salutation?>"></td>
	 				</tr>
	 				<tr>
	  				<td style="vertical-align:top">Address:</td><td><textarea name="address" rows="6" cols="150" style="width: 300px"><?= $happyParent->address?></textarea></td>
	 				</tr>
	  			<tr>
	  				<td>Email:</td><td><input type="text" name="email" value="<?= $happyParent->email?>"></td>
	 				</tr>
	  			<tr>
	  				<td>Phone 1:</td><td><input type="text" name="phone_1" value="<?= $happyParent->phone_1?>"></td>
	 				</tr>
	  			<tr>
	  				<td>Phone 2:</td><td><input type="text" name="phone_2" value="<?= $happyParent->phone_2?>"></td>
	 				</tr>
	  			<tr>
	  				<td>Phone 3:</td><td><input type="text" name="phone_3" value="<?= $happyParent->phone_3?>"></td>
	 				</tr>
	 				</table>
	 		</div>
	 		<div>
  			<h2>Children</h2>
  			<input type="hidden" name="childCount" value="<?= count($happyParent->children) ?>">
  			<?php foreach($happyParent->children as $index=>$child) { $logger->debugDump("child", $child); ?>
			    <table class="edit_children">
			    	<tr>
			    		<th>&nbsp;</th>
			    		<th>&nbsp;</th>
			    		<th>&nbsp;</th>
			    		<th>M</th>
			    		<th>T</th>
			    		<th>W</th>
			    		<th>T</th>
			    		<th>F</th>
			    		<th>Starting from</th>
			    	</tr>
				  	<tr>
				  		<td>Name:</td>
				  		<td><input type="text" name="child_name_<?= $index?>" value="<?= $child->name?>"></td>
				  		<td class="padded"><?= $sessions[1]->name ?></td>
				  		<?= makeWeekdayColumns($index, $sessions[1], $asOf, $child)?>
				  		<td><?= makeValidFromInput($index, 1, $asOf, $child)?></td>
				  	</tr>
				  	<tr>
				  		<td>Date of Birth:</td>
				  		<td><input type="date" name="child_date_of_birth_<?= $index?>" value="<?= $child->date_of_birth?>"></td>
				  		<td class="padded"><?= $sessions[2]->name ?></td>
				  		<?= makeWeekdayColumns($index, $sessions[2], $asOf, $child)?>
				  		<td><?= makeValidFromInput($index, 2, $asOf, $child)?></td>
				  	</tr>
				  	<tr>
				  		<td>Nickname:</td>
				  		<td><input type="text" name="child_nickname_<?= $index?>" value="<?= $child->nickname?>"></td>
				  		<td class="padded"><?= $sessions[3]->name ?></td>
				  		<?= makeWeekdayColumns($index, $sessions[3], $asOf, $child)?>
				  		<td><?= makeValidFromInput($index, 3, $asOf, $child)?></td>
				  	</tr>
				  	<tr>
				  		<td>Start Date:</td>
				  		<td><input type="text" name="child_start_date_<?= $index?>" value="<?= $child->start_date?>"></td>
				  		<td class="padded"><?= $sessions[4]->name ?></td>
				  		<?= makeWeekdayColumns($index, $sessions[4], $asOf, $child)?>
				  		<td><?= makeValidFromInput($index, 4, $asOf, $child)?></td>
				  	</tr>
				  	<tr>
				  		<td>Leave Date:</td>
				  		<td><input type="text" name="child_leave_date_<?= $index?>" value="<?= $child->leave_date?>"></td>
				  		<td colspan="7">&nbsp;</td>
				  		</tr>
			  		</table>
 				<?php }?>
	 			</div>
			<div style="text-align:left;max-width=100%">
				<input type="submit" name="action" value="Save">
				<input type="submit" name="action" value="Cancel">
		  </div>
  	</form>
	  </body>
</html> 	