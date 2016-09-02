<?php

require_once("status_base.class.php");

class Server_Status extends Status_Base
{
	public function __construct()
	{
		// include the server.php file so we can get the db connnection info
		include('/virtualhosts/dataxv3/live_config.php');
		define('DB_HOST','writer.dx.tss');
	}

	public function Run_Tests()
	{
		//if any fail, then FAIL

		//just connect/disconnect
		//if(!$this->MySQL_Test(DB_HOST, DB_USER, DB_PASS)) return FALSE;

		//run a query (it will return the result)
		//must specify schema.table in query
		//if(!$this->MySQL_Test(DB_HOST, DB_USER, DB_PASS, "select user from mysql.user")) return FALSE;

		//open/close a temp file
		if(!$this->HD_Test()) return FALSE;

		//write a temp file, and read back what you wrote
		if(!$this->HD_Test("monkey")) return FALSE;

		//otherwise PASS
		return TRUE;
	}
}

?>
