<?php
class Service {

	private $handler;

	public function __construct($request, $handler)
	{
		$this->handler= $handler;

	}

	public function handle()
	{
		$result= null;
		$status= $this->okStatus();
		
		try {
			$method= $this->getMethod();
			$result= $this->{$method}();
		} catch (Exception $e) {
			$status= $this->exceptionStatus($e);
			//that's nice but possibly pointless. I did not see this arrive on the client side. I don't think
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
		logMsg("getting it");
		$requestData= $this->_cleanInputs ( $_GET );
		
		$responseData= $this->handler->get($requestData);
		dump($responseData);
		
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