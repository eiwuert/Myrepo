<?php

require_once("status_base.class.php");

class Server_Status extends Status_Base
{
	function Server_Status()
	{
		//create connections, etc. here
	}
	
	function Run_Tests()
	{
		//do whatever tests you please here
		//return TRUE or 1 for "PASS"
		//return FALSE, 0, "" for "FAIL"

		//if any fail, then FAIL

		//just connect/disconnect
		if(!$this->MySQL_Test("localhost", "root", "")) return FALSE;

		//run a query (it will return the result)
		//must specify schema.table in query
		if(!$this->MySQL_Test("localhost", "root", "", "select * from mysql.user")) return FALSE;

		//open/close a temp file
		if(!$this->HD_Test()) return FALSE;

		//write a temp file, and read back what you wrote
		if(!$this->HD_Test("monkey")) return FALSE;

		//otherwise PASS
		return TRUE;
	}
}

?>