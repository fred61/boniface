<?php
	function redirect($url, $statusCode = 303)	{
		header('Location: ' . $url, true, $statusCode);
		die();
	}
	
	function getDateFromRequest($requestKey)
	{
		$result= new DateTime();
		if (isset($_REQUEST[$requestKey]) && is_numeric($_REQUEST[$requestKey])) {
			$result->setTimestamp($_REQUEST[$requestKey]);
		}
	
		return $result;
	}
	
?>