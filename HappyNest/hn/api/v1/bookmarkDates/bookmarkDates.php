<?php

chdir('../../..');

require_once 'model/model.php';
require_once 'lib/service.php';
require_once 'lib/log.php';

class BookmarkDateHandler
{
	use LoggerTrait;
	
	public function get() {
		$this->debug("handling it");
		
		return ModelFactory::getBookmarkDates();
	}
}

$start= microtime(true);
ModelFactory::initialise();
$initialised= microtime(true);
$svc= new Service($_REQUEST, new BookmarkDateHandler());

echo $svc->handle();
$handled= microtime(true);

RootLogger::info("that's all folks");
RootLogger::info("initialised in " . ($initialised - $start));
RootLogger::info("handled in " . ($handled - $initialised));
?>