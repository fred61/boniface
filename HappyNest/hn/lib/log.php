<?php
register_shutdown_function('flushLog');

$buffer;

function flushLog()
{
	global $buffer;
	
	if (strlen($buffer) > 0) {
		echo '<script type="text/javascript">';
		echo $buffer;
		echo '</script>';
	}
}


class Logger {
	const OFF= 0;
	const DEBUG= 1;
	const INFO= 2;
	const WARN= 3;
	const ALWAYS= 4;

	static $levels= array(
			'edit_parent'  => self::DEBUG
		 ,'dump'         => self::INFO
		 ,'attendance'   => self::DEBUG
		 ,'HappyChild'   => self::DEBUG
		 ,'ModelFactory' => self::INFO
	);
	
	static $methods= array(
			 self::DEBUG => 'log'
			,self::INFO  => 'info'
			,self::WARN  => 'warn'
			,self::ALWAYS => 'error'
	);
	
	static $loggers= array();
	
	static $globalLevel= self::INFO;
	
	static function flushAll()
	{
		foreach(self::$loggers as $logger)
		{
			$logger->flush();
		}
	}

//TODO this is not thought out. this means that if I call a method of HappyChild in edit_parent.php, 
//	the level of edit_parent is used, not that of HappyChild as I expect.
//	One way of improving that is changing this from a namespace to a class and having Loggers in the rest
//  of my code. Loggers can construct themselves with a name and they take basename[wawa] as a default. 

	//TODO this doesn't work so well in practice. It's a nice idea to log to the JS console, but the current 
	// implementation clearly loses log entries (don't know how) and it re-orders log entries because loggers have
	// their flush methods called in any old order.

	private $name;
	
	function __construct($name= NULL)
	{
		if (is_null($name)) {
			$this->name= basename($_SERVER['PHP_SELF'], '.php');
		} else {
			$this->name= $name;
		}
		
		array_push(self::$loggers, $this);
	}
	
	function always($msg) {
	  $this->log(self::ALWAYS, $msg);
	}
	
	function warn($msg) {
	  $this->log(self::WARN, $msg);
	}
	
	function info($msg) {
	  $this->log(self::INFO, $msg);
	}
	
	function debug($msg) {
	  $this->log(self::DEBUG, $msg);
	}
	
	function alwaysDump($msg, $obj) {
	  $this->dump(self::ALWAYS, $msg, $obj);
	}
	
	function warnDump($msg, $obj) {
	  $this->dump(self::warn, $msg, $obj);
	}
	
	function infoDump($msg, $obj) {
	  $this->dump(self::INFO, $msg, $obj);
	}
	
	function debugDump($msg, $obj) {
	  $this->dump(self::DEBUG, $msg, $obj);
	}

// 	private $buffer;

	private function log($level, $msg)
	{
		global $buffer;
		
	  if ($level >= $this->getLevel()) {
	  	$method= self::getMethod($level);
	  	$buffer= $buffer . 'console.' . $method . '("' . addslashes(addcslashes($msg, "\r\n\t")) . '");' . "\n";
	  }
	}

	private function dump($level, $msg, &$obj)
	{
		global $buffer;
		
	  if ($level >= $this->getLevel()) {
	  	$method= self::getMethod($level);
	  	
		  ob_start();
		
		  $out= "$msg: ";
		
		  foreach (explode("\n", var_export($obj, true)) as $s) {
		    //echo 'console.info("' . addslashes($s) . '");'. "\n";
		    $out= $out . addslashes(addcslashes($s, "\r\n\t")) . '\n\\' . "\n";
		  }
		
		  $buffer= $buffer. 'console.' . $method . '("' . $out . '");' . "\n";
		
		  ob_end_clean();
	  }
	}

	private function getMethod($level) {
		if (isset($methods[$level])) {
			return self::$methods[$level];
		} else {
			return 'log';
		}
	}

	private function getLevel()
	{
		if (isset(self::$levels[$this->name])) {
			return self::$levels[$this->name];
		} else {
			return self::$globalLevel;
		}
	}
}

?>
