<?php
/**
 * @desc Runs the second write for Ecash 3.0.
 * @author Brian Feaver
 */

	define('PASSWORD_ENCRYPTION', 'ENCRYPT');

	include_once('/virtualhosts/bfw.1.edataserver.com/include/modules/olp/olp.mysql.class.php');

	// Setup ecash3 database information
	$server = array( 
				"host" => "db101.clkonline.com",
				"user" => "ecash",
				"db" => "ldb_impact",
				"password" => "password",
				"port" => "3313");
	
	try
	{
		
		include_once('/virtualhosts/lib/mysqli.1.php');
	
		// Create ecash3 db connection
		$mysql = new MySQLi_1($server['host'], 
			$server['user'], 
			$server['password'], 
			$server['db'], 
			$server['port']);
		
		$olp_mysql = new OLP_MySQL($mysql);
		
		switch($argv[1])
		{
			case "create_trans":
				// Read in data from temp file
				if(is_file($argv[2]))
				{
					$serialized_data = file_get_contents($argv[2]);
				}
				
				$olp_mysql->Create_Transaction(unserialize($serialized_data));
				
				unlink($argv[2]);
				
				break;
			case "update_app_status":
				$olp_mysql->Update_Application_Status(unserialize($argv[2]), unserialize($argv[3]), unserialize($argv[4]));
				break;
			case "update_app":
				$olp_mysql->Update_Application(unserialize($argv[2]), unserialize($argv[3]));
				break;
			case "insert_comment":
				$olp_mysql->Insert_Comment(unserialize($argv[2]));
				break;
			case "document_event":
				$olp_mysql->Document_Event(unserialize($argv[2]), unserialize($argv[3]));
				break;
			case "insert_loan_action":
				$olp_mysql->Document_Event(unserialize($argv[2]), unserialize($argv[3]));
				break;
		}
		
	}
	catch (Exception $e)
	{
		
		// don't throw this exception here,
		// or we'll get a FATAL for an uncaught
		// exception!
		//throw $e;
		
		error_log($e->getMessage());
		unset($mysql);
		
	}
	
?>
