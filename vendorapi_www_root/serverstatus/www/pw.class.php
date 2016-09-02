<?php

	require_once('status_base.class.php');

	class Server_Status extends Status_Base
	{
		
		// MySQL connection information
		const MYSQL_HOST = 'writer.pw.tss';
		const MYSQL_USER = 'user';
		const MYSQL_PASS = 'partnerweekly';
		
		// load average threshold
		const LOAD_THRESHOLD = 20;
		
		public function Run_Tests()
		{
			
			// if any fail, then FAIL, but assume we pass
			$result = TRUE;
			
			// test MySQL connection status -- do we really want to take a server
			// out of the load balancer if the MySQL server is down?
			if ($result) $result = $this->MySQL_Test(self::MYSQL_HOST, self::MYSQL_USER, self::MYSQL_PASS);
			
			// write a random string to a temp file and read it back
			$random = md5(microtime());
			if ($result) $result = ($this->HD_Test($random) === $random);
			
			// check our load average and make sure it's within a reasonable limit
			if ($result) $result = $this->Load_Test(self::LOAD_THRESHOLD);
			
			return $result;
			
		}
		
	}
	
?>
