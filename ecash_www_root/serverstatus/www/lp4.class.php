<?php

require_once("status_base.class.php");

class Server_Status extends Status_Base
{
	function Run_Tests()
	{
		//do whatever tests you please here
		//return TRUE or 1 for "PASS"
		//return FALSE, 0, "" for "FAIL"

		//open/close a temp file
		if(!$this->HD_Test()) return FALSE;

		//PASS
		return TRUE;
	}
}

?>