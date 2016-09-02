<?php

require_once("status_base.class.php");

class Server_Status extends Status_Base
{
	public function __construct()
	{
		// include the server.php file so we can get the db connnection info
		include('/virtualhosts/lp.4.config/server.php');
		$server = new Server('3');
		$this->server = $server->Get_Server();
	}

	public function Run_Tests()
	{
		//if any fail, then FAIL

		//just connect/disconnect
		if(!$this->MySQL_Test($this->server['host'], $this->server['user'], $this->server['password'])) return FALSE;

		//open/close a temp file
		if(!$this->HD_Test("monkey")) return FALSE;


		//otherwise PASS
		return TRUE;
	}
}


?>