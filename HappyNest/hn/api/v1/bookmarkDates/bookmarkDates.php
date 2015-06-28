<?php
require_once 'model/model.php';
require_once 'lib/service.php';

$logOn= false;

function logMsg($text)
{
	global $logOn;
	if ($logOn) {
		echo "<PRE>";
		echo $text;
		echo "</PRE>";
	}
}

function dump($expression)
{
	global $logOn;
	if ($logOn) {
		echo "<PRE>";
		var_dump($expression);
		echo "</PRE>";
	}
}


class BookmarkDateHandler
{
	public function get() {
		logMsg("handling it");
		
		return ModelFactory::getBookmarkDates();
	}
}

$start= microtime(true);
ModelFactory::initialise();
$initialised= microtime(true);
$svc= new Service($_REQUEST, new BookmarkDateHandler());

echo $svc->handle();
$handled= microtime(true);

logMsg("that's all folks");
logMsg("initialised in " . ($initialised - $start));
logMsg("handled in " . ($handled - $initialised));
?>