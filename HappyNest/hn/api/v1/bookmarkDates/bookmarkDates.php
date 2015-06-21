<?php
require_once 'model/model.php';

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

class Service {
	
	private $handler;
	
	public function __construct($request, $handler)
	{
		$this->handler= $handler;
		
	}

	public function handle()
	{
		$method= $this->getMethod();
		return $this->{$method}();
	}
	
	private function getMethod()
	{
		$result= $_SERVER ['REQUEST_METHOD'];
		if ($result == 'POST' && array_key_exists ( 'HTTP_X_HTTP_METHOD', $_SERVER )) {
			if ($_SERVER ['HTTP_X_HTTP_METHOD'] == 'DELETE') {
				$result = 'DELETE';
			} else if ($_SERVER ['HTTP_X_HTTP_METHOD'] == 'PUT') {
				$result = 'PUT';
			} else {
				throw new Exception ( "Method not recognised: " .  $_SERVER ['HTTP_X_HTTP_METHOD']);
			}
		}
		
		return $result;
	}
	
	private function get()
	{
		logMsg("getting it");
		$requestData= $this->_cleanInputs ( $_GET );
		$responseData= $this->handler->get($requestData);
		dump($responseData);
		
		//header ( "HTTP/1.1 " . $status . " " . $this->_requestStatus ( $status ) );
		header ( "HTTP/1.1 200 OK");
		return json_encode ( $responseData );
		
	}
	
	 private function _cleanInputs($data) {
		$clean_input = Array ();
		if (is_array ( $data )) {
			foreach ( $data as $k => $v ) {
				$clean_input [$k] = $this->_cleanInputs ( $v );
			}
		} else {
			$clean_input = trim ( strip_tags ( $data ) );
		}
		return $clean_input;
	}
	
}

class BookmarkDateHandler
{
	public function get() {
		logMsg("handling it");
		
// 		return array(
// 	    (object)array("date" => (new DateTime("2015-09-17"))->format(DateTime::W3C), "text" => "Start English Club"),
// 	    (object)array("date" => (new DateTime("2015-08-19"))->format(DateTime::W3C), "text" => "Start Playgroup")
// 		);
		return ModelFactory::getBookmarkDates();
	}
}

//TODO there is no error handling here at all. 
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