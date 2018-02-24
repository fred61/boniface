<?php

	require_once('lib/log4php/Logger.php');
	
	Logger::configure('conf/log4php.xml');
	
	trait LoggerTrait {
		static private $logger;
	
		static private function getLogger()
		{
			if (!isset(self::$logger)) {
				self::$logger= Logger::getLogger(__CLASS__);
			}
			return self::$logger;
		}
	
		static private function debug($msg, $throwable= NULL)
		{
			$logger= self::getLogger();
			$logger->debug($msg, $throwable);
		}
	
		static private function info($msg, $throwable= NULL)
		{
			$logger= self::getLogger();
			$logger->info($msg, $throwable);
		}
		
		static private function warn($msg, $throwable= NULL)
		{
			$logger= self::getLogger();
			$logger->warn($msg, $throwable);
		}

		static private function error($msg, $throwable= NULL)
		{
			$logger= self::getLogger();
			$logger->error($msg, $throwable);
		}
		
		static private function debugDump($msg, $object)
		{
			$logger= self::getLogger();
			$logger->debug($msg);
			$logger->debug($object);
		}
	}
	
	class RootLogger {
		use LoggerTrait {
			debug as public;
		  info as public;
		  warn as public;
		  error as public;
		  debugDump as public;
		}
	}
	
	
?>
