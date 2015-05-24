<?php
	namespace Log;
	
	const DEBUG= 1;

		
	$level=0;	
	/* TODO this is obviously not the spirit of Log4j. */
	
	function debugOn() {
		global $level;
		
		$level= 2;
	}
	
	function debugOff() {
		global $level;
		
		$level= 1;
	}
	
	function debug($msg) {
		global $level;
		if ($level > DEBUG) {
			always($msg);
		}
	}
	
	function debugDump(&$obj) {
		global $level;
		if ($level > DEBUG) {
			alwaysDump($obj);
		}
	}
	
	function always($msg)
	{
		echo "<pre>" . $msg . "</pre>";
	}
	
	function alwaysDump(&$obj)
	{
		echo "<pre>"; var_dump($obj); echo "</pre>";
	}

?>