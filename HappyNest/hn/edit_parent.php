<?php
	require_once 'lib/lib.php';
	require_once 'lib/log.php';
	require_once 'model/model.php';
	require_once 'control/site_controller.php';

	$logger= new Logger();
	
	function makeWeekdayColumns($session, $asOf, $happyChild)
	{
		global $logger;
		$result= "";
		
		$sessions= $happyChild->getNearestSessions($asOf);
		$logger->debugDump("sessions as of  " . $asOf->format('Y-m-d'), $sessions);
		
		if (isset($sessions) && isset($sessions[$session->id]))
		{
			$sessionOccurence= $sessions[$session->id];
		} else {
			$sessionOccurence= null;
		}
		
		$shadowCtrlValue= "";
		
		for($i= 1; $i <= 5; $i++) {
			$result= $result . "<td>";
		
			if (!$session->isAvailableOn($i)) {
				$result= $result . "&nbsp;";
				$shadowCtrlValue.= "n";
			} else {
				if (isset($sessionOccurence) && $sessionOccurence->isOnWeekday($i)) {
					$ctrlValue= 'checked';
					$shadowCtrlValue.= "y";
				} else {
					$ctrlValue= '';
					$shadowCtrlValue.= "n";
				}
			
				$result= $result . '<input type="checkbox" name="" value="' . $i . '" ' . $ctrlValue . ">";
			}
			$result= $result . "</td>";
		}
		
		$result= $result . '<td><input type="hidden" name="session_days[' . $session->id . '][]" value="' . $shadowCtrlValue . '"></td>'; 
				
		return $result;
	}
	
	function makeValidFromInput($session, $asOf, $child)
	{
		$sessions= $child->getNearestSessions($asOf);
		if (isset($sessions) && isset($sessions[$session->id])) {
			$sessionValidFrom= $sessions[$session->id]->valid_from;
		} else {
			$sessionValidFrom= "";
		}
		
		$result= '<input type="text" name="session_valid_from[' . $session->id . '][]" value="' . $sessionValidFrom . '">';

		return $result;
	}
	
	function convertSessionDays($sessionDaysYN)
	{
		$result= "";
		
		for($i= 0; $i < strlen($sessionDaysYN); $i++) {
			if ($sessionDaysYN[$i] == 'y') {
				$result= $result . ($i+1) . ","; 
			}
		}
		$result= rtrim($result, ",");
		
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
		
		for($i= 0; $i < count($_POST['child_name']); $i++) {
			if (!isset($happyParent->children[$i])) {
				$happyChild= new HappyChild(new HappyChildDTO());
					
				$happyParent->children[$i]= $happyChild;
				$happyChild->parent= $happyParent;
			}
			ModelFactory::copyFromRequest($happyParent->children[$i], "child", $i);

			// session data is an array, indexed by session ID.
			// elements are object style arrays: keys are validFrom and days.
			
			$sessionData= array();
			foreach($sessions as $session) {
				$vf= $_POST['session_valid_from'][$session->id][$i];
				$sd= convertSessionDays($_POST['session_days'][$session->id][$i]);
				if ($vf != '' || $sd != '') {
					$sessionData[$session->id]['validFrom']= $vf;
					$sessionData[$session->id]['days']= $sd;
				} 
			}
			
			$logger->alwaysDump("session data", $sessionData);
			$happyParent->children[$i]->applySessionData($sessionData, $asOf);
				
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
    	window.onload = function() {

				for (var i= 0; i < document.forms.length; i++) {
					var form= document.forms[i];
					console.log("form: " + form.name);

					for (var j= 0; j < form.elements.length; j++) {
						var element= form.elements[j];

						if (element.type == 'checkbox') {
							linkControls(element);
						}
					}
				}
    	};

    	function linkControls(element)
    	{
				// find related controls: shadowControl stores checkBox states, validFromControl is self-explanatory
				var grandParent= element.parentElement.parentElement;
				// sanity check - this should be a table row
				if (grandParent.nodeName == "TR") {
					var inputs= grandParent.getElementsByTagName("input");
					for (k= 0; k < inputs.length; k++) {
						if (inputs[k].type == "hidden") {
							element.shadowControl= inputs[k];
							element.addEventListener("click", checkBoxHandler);
						}
						if (inputs[k].type == "text" && inputs[k].name.indexOf('session_valid_from') == 0) {
							element.validFromControl= inputs[k];
							element.addEventListener("click", setValidFrom);
						}
					}
				}
			}

    	function checkBoxHandler()
    	{
        	var values= this.shadowControl.value.split("");
        	var index= this.value - 1;

        	if (this.checked) {
            	values[index]= "y";
        	} else {
            	values[index]= "n";
        	}

        	this.shadowControl.value= values.join("");
			}
    	
    	function setValidFrom(targetId)
    	{
        	var sourceElement= document.getElementById("asOf");

        	this.validFromControl.value= sourceElement.value;
			}

			function addChild()
			{
				console.exception("adding child");
				
				var container= document.getElementById("childContainer");
				var childDiv= container.firstElementChild.cloneNode(true);

				var inputElements= childDiv.getElementsByTagName("input");
				
				for(var i= 0; i < inputElements.length; i++)
				{
					var element= inputElements[i];
					console.exception("got an element: " + element);

					if (element.type == "text") {
						element.value= "";
					} else if (element.type == "checkbox") {
						linkControls(element);
						element.checked= false;
					} else if (element.type == "hidden") {
						if (element.name.indexOf("child_id") == 0) {
							element.value="";
						} else if (element.name.indexOf("session_days") == 0) {
							element.value="nnnnn";
						}
					}
				} 
			
				container.appendChild(childDiv);

				return false;
			}
    </script>
  </head>
  <body>
  	<form method="post">
  		<div>
  			<div class="header2">Parent</div>
  			<input type="hidden" name="id" value="<?= $happyParent->id?>">
  			<input type="hidden" name="asOf" id="asOf" value="<?= $asOf->format('Y-m-d')?>">
	  		<table>
	  			<tr>
	  				<td>Name:</td><td><input type="text" name="name" value="<?= $happyParent->name?>" size=60></td>
	 				</tr>
	  			<tr>
	  				<td>Salutation:</td><td><input type="text" name="salutation" value="<?= $happyParent->salutation?>" size=60></td>
	 				</tr>
	 				<tr>
	  				<td style="vertical-align:top">Address:</td><td><textarea name="address" rows="6" cols="150" style="width: 376px"><?= $happyParent->address?></textarea></td>
	 				</tr>
	  			<tr>
	  				<td>Email:</td><td><input type="text" name="email" value="<?= $happyParent->email?>" size=60></td>
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
  			<div class="header2">Children<input style="padding-left: 3mm;" type="image" src="img/b_insrow.png" onclick="return addChild();"></div>
  			<div id="childContainer">
  				<div>
		  			<?php foreach($happyParent->children as $index=>$child) { $logger->debugDump("child", $child); ?>
		  				<input type="hidden" name="child_id[]" value="<?= $child->id?>">
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
					    		<th></th>		<!-- empty column for shadow control -->		
					    		<th>Starting from</th>
					    	</tr>
						  	<tr>
						  		<td>Name:</td>
						  		<td><input type="text" name="child_name[]" value="<?= $child->name?>"></td>
						  		<td class="padded"><?= $sessions[1]->name ?></td>
						  		<?= makeWeekdayColumns($sessions[1], $asOf, $child)?>
						  		<td><?= makeValidFromInput($sessions[1], $asOf, $child)?></td>
						  	</tr>
						  	<tr>
						  		<td>Date of Birth:</td>
						  		<td><input type="date" name="child_date_of_birth[]" value="<?= $child->date_of_birth?>"></td>
						  		<td class="padded"><?= $sessions[2]->name ?></td>
						  		<?= makeWeekdayColumns($sessions[2], $asOf, $child)?>
						  		<td><?= makeValidFromInput($sessions[2], $asOf, $child)?></td>
						  	</tr>
						  	<tr>
						  		<td>Nickname:</td>
						  		<td><input type="text" name="child_nickname[]" value="<?= $child->nickname?>"></td>
						  		<td class="padded"><?= $sessions[3]->name ?></td>
						  		<?= makeWeekdayColumns($sessions[3], $asOf, $child)?>
						  		<td><?= makeValidFromInput($sessions[3], $asOf, $child)?></td>
						  	</tr>
						  	<tr>
						  		<td>Start Date:</td>
						  		<td><input type="text" name="child_start_date[]" value="<?= $child->start_date?>"></td>
						  		<td class="padded"><?= $sessions[4]->name ?></td>
						  		<?= makeWeekdayColumns($sessions[4], $asOf, $child)?>
						  		<td><?= makeValidFromInput($sessions[4], $asOf, $child)?></td>
						  	</tr>
						  	<tr>
						  		<td>Leave Date:</td>
						  		<td><input type="text" name="child_leave_date[]" value="<?= $child->leave_date?>"></td>
						  		<td colspan="7">&nbsp;</td>
						  		</tr>
					  		</table>
		 				<?php }?>
		 			</div>
	 			</div>
	 		</div>
			<div style="text-align:left;max-width=100%">
				<input type="submit" name="action" value="Save">
				<input type="submit" name="action" value="Cancel">
		  </div>
  	</form>
	  </body>
</html> 	