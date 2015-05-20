<?php
require_once 'lib/log.php';
		
class Calendar {
	static $logger;
	
	static function init()
	{
		self::$logger= new Logger(__CLASS__);
	}
	
	private $startDate;
	private $endDate;
	private $asOf;
	private $focus;
	private $TABLE_HEADERS= array("M", "T", "W", "T", "F", "S", "S");

	public function __construct($focus, $asOf) {
		$this->asOf= $asOf;
		$this->focus= $focus;
		self::$logger->debug(" date coming in: " . $asOf->format('Y-m-d H:i:s'));

		$this->asOf->setTime(0,0,0);
		self::$logger->debug(" as of date: " . $this->asOf->format('Y-m-d H:i:s'));

		$this->startDate= clone $asOf;
		$this->startDate->sub(new DateInterval("P" . ($asOf->format('j') - 1) . "D"));
		self::$logger->debug(" start date 1:" . $this->startDate->format('Y-m-d H:i:s'));
		$this->startDate->sub(new DateInterval("P" . ($this->startDate->format('N') - 1) . "D"));
		self::$logger->debug(" start date 2:" . $this->startDate->format('Y-m-d H:i:s'));
		self::$logger->debug(" start date 2u:" . $this->startDate->format('U'));

		$this->endDate= new DateTime(" last day of " . $asOf->format('F Y'));
		self::$logger->debug(" end date: " . $this->endDate->format('Y-m-d H:i:s'));

	}

	public function make_html() {
		$calNav= $this->make_calNav();
		$header= $this->make_header();
		$body= $this->make_body();

		return "<div>\n${calNav}\n</div><table id=\"calendar\">\n${header}\n${body}\n</table>";
}

		private function make_header()
		{
		$header="<tr>\n";
		foreach ($this->TABLE_HEADERS as $th) {
		$header= $header . "<th> ${th} </th>";
		}

		return $header;
		}

		private function make_body()
		{
			$body= "";
			$date= clone $this->startDate;
			$interval = new DateInterval('P1D');
		
			while ($date < $this->endDate) {
				#do one row = one week
				$row= "<tr>";
				for ($i= 0; $i < 7; $i++) {
					$row= $row . "<td>" . $this->make_cell($date) . "</td>";
					$date->add($interval);
				}
				$row= $row . "</tr>";
				$body= $body . $row . "\n";
			}
		
			return $body;
		}
		
		private function make_cell($date) {
			$result= "<a href=attendance.php?asOf=" . $date->format('U');
			$result= $result . "&calAsOf=" . $date->format('U');
			$result= $result . ">" .  $date->format('j');   
			$result= $result . "</a>";
			
			return $result;
			#@TODO un-hardwire the href
		}
		
		private function make_calNav()
		{
			$month= new DateInterval('P1M');
			$year= new DateInterval('P1Y');
		
			$prevMonth= clone $this->asOf;
			$prevMonth->sub($month);
		
			$prevYear= clone $this->asOf;
			$prevYear->sub($year);
		
			$nextMonth= clone $this->asOf;
			$nextMonth->add($month);
		
			$nextYear= clone $this->asOf;
			$nextYear->add($year);
		
			$result= "<td><a href=attendance.php?calAsOf=" . $prevYear->format('U') . "&asOf=" . $this->focus->format('U') .  ">&lt;&lt;</a></td>";
			$result= $result . "<td><a href=attendance.php?calAsOf=" . $prevMonth->format('U') . "&asOf=" . $this->focus->format('U') .  ">&lt;</a></td>";
			$result= $result . "<td style=\"width: 110px;text-align:center;\">". $this->asOf->format('F Y') . "</td>";
			$result= $result . "<td><a href=attendance.php?calAsOf=" . $nextMonth->format('U') . "&asOf=" . $this->focus->format('U') .  ">&gt;</a></td>";
			$result= $result . "<td><a href=attendance.php?calAsOf=" . $nextYear->format('U') . "&asOf=" . $this->focus->format('U') .  ">&gt;&gt;</a></td>";
		
			return "<table><tr> $result </tr></table>";
		}
	}
	Calendar::init();
?>		