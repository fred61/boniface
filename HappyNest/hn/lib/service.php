<?php
require_once 'lib/log.php';

class Service {
	use LoggerTrait;
	
	private $handler;

	public function __construct($request, $handler)
	{
		$this->handler= $handler;
		//TODO check that handler implements methods needed or stub them out  
		
		RootLogger::info("constructed");

	}

	public function handle()
	{
		self::debug("handling");
		
		$result= null;
		$status= $this->okStatus();
		
		try {
			$method= $this->getMethod();
			self::info("method is $method");
			$result= $this->{$method}();
		} catch (Exception $e) {
			self::error("exception in handler method", $e);
			$status= $this->exceptionStatus($e);
			$result= $e;
			//TODO that's nice but possibly pointless. I did not see this arrive on the client side. I don't think
			// the text in the status makes it anywhere: once the PHP engine (or wawa) sees the 500 status, it
			// appears to be it: it appends "Server Error" and that is that.
		}
		
		header("HTTP/1.1 " . $status);
		return $result;
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
		self::debug("getting it");
		$requestData= $this->_cleanInputs ( $_GET );
		
		$responseData= $this->handler->get($requestData);
		self::debugDump("", $responseData);
		
		return $this->makeResult($responseData);
	}
	
	private function post()
	{
		self::debug("posting it");
		
		$rest_json = file_get_contents("php://input");
		$_POST = json_decode($rest_json, true);
		
		self::debug($_POST);

		$requestData= $this->_cleanInputs($_POST);
		
		$responseData= $this->handler->post($requestData);
		
		return $this->makeResult($responseData);
	}
	
	//TODO extend for put and delete

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
	
	private function makeResult($responseData)
	{
		$result= json_encode($responseData, JSON_PRETTY_PRINT);
		
		if (!$result) {
			throw new Exception("JSON encoding response data failed: " . json_last_error() . "; " . json_last_error_msg());
		} else {
			return $result;
		}
	}
	
	private function okStatus()
	{
		return "200 OK";
	}
	
	private function exceptionStatus($e)
	{
		return "500 Internal Error: " + $e->getMessage();
	}

}

?>