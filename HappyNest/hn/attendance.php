<?php
  require_once 'lib/classicLog.php';
  require_once 'lib/calendar.php';
  require_once 'model/model.php';
  require_once 'control/site_controller.php';
  require_once 'control/attendance_controller.php';
  
  class AttendanceView {
  	public $allSessions;
  	private $attendanceTable;
  	private $rowCounts;
  	private $maxRowCount;
  	private $asOf;
  	
  	function makeTable() {
  		$res= "";
  	
  		$header= "<tr>\n";
  		foreach ($this->attendanceTable['header'] as $cell) {
  			$header= "$header<th>$cell</th>";
  		}
  		$header= "$header</tr>";
  	
  		$body= "";
  		$rowInd= 0;
  		while ($rowInd < $this->maxRowCount) {
  			$row= "<tr>\n";

  			for ($i= 0; $i < 6; $i++) {
  				if (!isset($this->attendanceTable['body'][$i][$rowInd])) {
  					$row= $row . "<td>&nbsp;</td>";
  				} else {
  					$elem= $this->attendanceTable['body'][$i][$rowInd];
  					$cell= $this->makeChildCell($elem['child'], $elem['session'], $this->asOf);
  					$row= $row . '<td class="' . $cell['class'] . '">' . $cell['content'] . "</td>";
  				}
  				$row= $row . "</td>";
  			}
  			$row= "$row\n</tr>";
 				$body="$body$row";
  			$rowInd= $rowInd + 1;
  		}
  		$res= "<table id=\"attendance\">\n$header\n$body\n</table>";
  		
  		return $res;
  	}
  	
  	function makeShowSessionControls()
  	{
  		$result= "";
  		
  		$allSessions= ModelFactory::getAllSessions();
  		$showSessions= AttendanceController::getSessionsToShow();
  		
  		foreach($allSessions as $session) {
  			if (isset($showSessions[$session->id])) {
  				$ctrlValue= 'checked';
  			} else {
  				$ctrlValue= '';
  			}
  		  $result= $result . '<label><input type="checkbox" name="session_' . $session->id . '"';
  		  $result= $result . ' ' . $ctrlValue . '>' . $session->name . '</label>';
			}
			
			return $result;
  		  		
  	}
  	
		function initialise($asOf)
		{
			$this->attendanceTable['header']= array();
			$this->attendanceTable['body']= array_fill(0, 6, array());
			$this->rowCounts= array_fill(0, 6, 0);
			
			$this->asOf= $asOf;
			
			$this->buildBody();
			$this->buildHeader();
		}
		
		private function buildHeader()
		{
			$colDates= array();
			
			$asOfDayOfWeek= $this->asOf->format('N');
			$monday= clone $this->asOf;
			if ($asOfDayOfWeek == "7" || $asOfDayOfWeek == "6") {
				$monday->add(new DateInterval('P' . (8 - $asOfDayOfWeek) . 'D'));
			} else {
				$monday->sub(new DateInterval('P' . ($asOfDayOfWeek - 1) . 'D'));
			}
			//$logger->debug(" monday: [" . $monday->format("Y-m-d H:i:s") . "]");
				
			for ($i= 0; $i < 5; $i++) {
				$tsSpec= $i - $asOfDayOfWeek ;
				$colInterval= DateInterval::createFromDateString("$tsSpec days");
				$colDates[$i]= clone $monday;
				$colDates[$i]->add(new DateInterval("P${i}D"));
			}
			$colDates[5]= clone $monday;
			
			
			for ($i= 0; $i < 5; $i++) {
				$tsSpec= $i - $asOfDayOfWeek ;
				$colInterval= DateInterval::createFromDateString("$tsSpec days");
				$colDates[$i]= clone $monday;
				$colDates[$i]->add(new DateInterval("P${i}D"));
			}
			$colDates[5]= clone $monday;
			
			for ($i=0; $i < 5; $i++) {
				$this->attendanceTable['header'][$i]= $colDates[$i]->format('l') . "<br>" . $colDates[$i]->format('d.m.Y') . "<br><span style='font-weight:normal'>(" . $this->rowCounts[$i] . ")</span>";
			}
			$this->attendanceTable['header'][5]= 'unassigned';
				
		}
		
		private function buildBody()
		{
			ModelFactory::initialise();
			$this->allSessions= ModelFactory::getAllSessions();
				
			$showSessions= AttendanceController::getSessionsToShow();
				
			foreach (ModelFactory::getAllParents() as $happyParent)
			{
				foreach($happyParent->children as $happyChild) {
					$sessions= $happyChild->getCurrentSessions($this->asOf);
					//$logger->debugDump("sessions as of " . $this->asOf->format('Y-m-d'), $sessions);
						
					if (is_null($sessions) || count($sessions) == 0) {
						//$logger->debug("parent " . $happyParent->name . " child . " . $happyChild->nickname . " no sessions");
						$this->addChildToTable(6, $happyChild, null);
					} else {
						foreach($sessions as $sessionOccurence) {
							//$logger->debug("parent " . $happyParent->name . " child . " . $happyChild->nickname . " sessionOccurence " . $sessionOccurence->weekdays);
							if (isset($showSessions[$sessionOccurence->session_id])) {
								$days= $sessionOccurence->weekdays;
								if ($days == "") {
									if ($sessionOccurence->session_id == Session::WAITING_LIST) {
										$this->addChildToTable(6, $happyChild, null);
									}
								} else { 
									foreach(explode(',', $days) as $day) {
										$this->addChildToTable($day, $happyChild, $this->allSessions[$sessionOccurence->session_id]);
									}
								}
							}
						}
					}
				}
			}
			
			foreach($this->attendanceTable['body'] as &$column) {
				usort($column, function ($el1, $el2) {
					$sessionId1= 0;
					$sessionId2= 0; 
					$childName1= "";
					$childName2= "";
					
					if (isset($el1['session'])) {
						$sessionId1= $el1['session']->id;
					}
					
					if (isset($el2['session'])) {
						$sessionId2= $el2['session']->id;
					}
					
// 					Log\always("$sessionId1 $sessionId2");
					
					if ($sessionId1 > $sessionId2) {
						return 1;
					} else if ($sessionId1 < $sessionId2) {
						return -1;
					} else {
						if (isset($el1['child'])) {
							$childName1= $el1['child']->name;
						}
						if (isset($el2['child'])) {
							$childName2= $el2['child']->name;
						}
						
// 						Log\always("$childName1 $childName2");
						
						if ($childName1 > $childName2) {
							return 1;
						} else if ($childName1 < $childName2) {
							return -1;
						} else {
							return 0;
						}
					}
				});
// 				Log\always("after sorting");
// 				Log\alwaysDump($column);
				
			}
		}
		
		private function addChildToTable($day, $child, $session)
		{
			$colInd= $day - 1;
			 
			array_push($this->attendanceTable['body'][$colInd], array('child' => $child, 'session' => $session));
			$this->rowCounts[$colInd]= $this->rowCounts[$colInd] + 1;
			
			if ($this->rowCounts[$colInd] > $this->maxRowCount) {
				$this->maxRowCount= $this->rowCounts[$colInd];
			}
		}

		private function makeChildCell(&$child, &$session, $asOf) {
			global $logger;
			 
			if (is_null($session)) {
				$tdClass= null;
			} else {
				$tdClass= strtolower(str_replace(' ', '_', $session->name));
			}
			$tdContents= "<a href=edit_parent.php?id=" . $child->parent_id . "&asOf=" . $asOf->format('U') .">". $child->nickname . "</a>";
			 
			return array('class' => $tdClass, 'content' => $tdContents);
		}
  }
  
  SiteController::mark();
  
  $asOf= AttendanceController::getAsOf();

  $view= new AttendanceView();
  $view->initialise($asOf);
?>
<html lang="en" >
  <head>
  	<meta charset="utf-8">
  	<title>Happy Nest Attendance</title>
    <link rel="stylesheet" type="text/css" href="hnstyle.css">
  	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.16/angular.min.js"></script>
  	<script src="lib/calendar.js"></script>
  	</head>
  <body>
    <nav id="calendar" ng-app="calendarApp" ng-controller="CalendarCtrl">
    	<div>
    		<table>
    			<tr>
    				<td class="clickable" ng-click="prevYear()">&lt;&lt;<td>
    				<td class="clickable"ng-click="prevMonth()">&lt;<td>
    				<td  style="width: 110px;text-align:center;">
    					{{ asOf.toLocaleString('en-GB', {year: 'numeric', month:'long'}) }}
    				<td>
    				<td  class="clickable" ng-click="nextMonth()">&gt;<td>
    				<td  class="clickable" ng-click="nextYear()">&gt;&gt;<td>
    			</tr>
    		</table>
	  		<table id="calendar">
    			<tr>
    				<th>M</th>
    				<th>T</th>
    				<th>W</th>
    				<th>T</th>
    				<th>F</th>
    				<th>S</th>
    				<th>S</th>
    			</tr>
	  		<tr ng-repeat="row in datesTable()">
	  				<td  class="clickable" ng-class="{asOfDateCell : pageAsOf.sameDay(cell), todayDateCell : today.sameDay(cell) }" ng-repeat="cell in row" ng-click="setAsOf(cell)">
	  					{{ cell.getDate() }}
	  				</td>
	  			</tr>
	  		</table>
	  	</div>
    </nav>
    <nav id="sessionTypes">
    	<form method="post">
    		<?= $view->makeShowSessionControls() ?>
    		<input type="submit" name="action" value="Show">
    	</form>
    </nav>
		<div style="text-align:center">
      <h1>Happy Nest Attendance</h1>
    </div>
    <div>
      <?= $view->makeTable() ?>
    </div>
  </body>
</html>