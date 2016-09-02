<?php

require_once('status_base.class.php');

class Server_Status extends Status_Base
{
	function Server_Status()
	{
	}

	function Run_Tests()
	{
		//DEFAULT TESTS: Apache, PHP, HD Temp File
		
		//if any fail, then FAIL

		//open/close a temp file
		if(!$this->HD_Test()) return FALSE;

		//write a temp file, and read back what you wrote
		if(!$this->HD_Test("monkey")) return FALSE;

		//return TRUE or 1 for "PASS"
		//return FALSE, 0, "" for "FAIL"
		return TRUE;
	}
}

?>
