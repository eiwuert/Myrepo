<?php

require_once("status_base.class.php");
require_once("maintenance_mode.php");

class Server_Status extends Status_Base
{
	//A file to touch when we send an alert related to the condor
	//thing so that it's not unstoppable
	const CONDOR_MOUNT_ALERT_FILE = '/tmp/condor_mount_alert';
	public function __construct()
	{
		// include the server.php file so we can get the db connnection info
		// We're including it so that we don't fail on the require, see the class_exists() below
		include('../../bfw.1.edataserver.com/include/code/server.php');
	}

	public function Run_Tests()
	{
		//Check for maintenace mode. If it's on then just return TRUE so the servers aren't taken out of the loop
	   $maintenance_mode = new Maintenance_Mode();
		if(!$maintenance_mode->Is_Online())
		{
			return TRUE;
		}
		
		//if any fail, then FAIL
		
		if(class_exists('Server'))
		{
			$this->server = Server::Get_Server('LIVE', 'BLACKBOX', NULL);
		}
		else
		{
			error_log('server.php include() probably failed.');
			return FALSE;
		}
		if($this->condorMountTest() === false)
		{
			return FALSE;
		}

		//just connect/disconnect
		if(!$this->MySQL_Test($this->server['host'], $this->server['user'], $this->server['password'])) return FALSE;

		//run a query (it will return the result)
		//must specify schema.table in query
		//if(!$this->MySQL_Test($this->server['host'], $this->server['user'], $this->server['password'], "select user from mysql.user")) return FALSE;
		// Modified by [AuMa] per Brian F.'s Instructions on Mantis 7294
		if(!$this->MySQL_Test($this->server['host'], $this->server['user'], $this->server['password'], "SELECT value FROM olp.failover_data WHERE name = 'MAINTENANCE_MODE'")) return FALSE;
		//open/close a temp file
		if(!$this->HD_Test()) return FALSE;

		//write a temp file, and read back what you wrote
		if(!$this->HD_Test("monkey")) return FALSE;

		//otherwise PASS
		return TRUE;
	}
	
	/**
	 * Tests to see if the condor mount is there
	 * Also tries to send an alert if it can
	 *
	 * @return boolean
	 */
	private function condorMountTest()
	{
		include('../../condor.4.edataserver.com/lib/config.php');
		if(!defined('CONDOR_DIR'))
		{
			define('CONDOR_DIR','/virtualhosts/condor.4.edataserver.com');
			define('CONDOR_ROOT_DIR','/data');
		}
		define('EXECUTION_MODE','RC');
		include(CONDOR_DIR.'/lib/condor_exception.php');
		try 
		{
			$mnt = trim(shell_exec('df | grep '.CONDOR_ROOT_DIR.' | wc -l'));
			if($mnt < 1)
			{
				$srv = trim(`hostname`);
				if(file_exists(self::CONDOR_MOUNT_ALERT_FILE))
				{
					clearstatcache();
					$t = filemtime(self::CONDOR_MOUNT_ALERT_FILE);
					if(time() > ($t + 3600))
					{
						//If it's been over an hour, change the modtime on the file
						//and send an alert
						touch(self::CONDOR_MOUNT_ALERT_FILE);
						
						$msg = 'Mount '.CONDOR_ROOT_DIR.' does not exist. (Hostname: '.$srv.')';
						if(class_exists('CondorException'))
						{
							$x = new CondorException($msg,CondorException::ERROR_MOUNT);
						}
						else 
						{
							//prolly should do something
						}
						
					}
				}
				//The file didn't exist so send an alert and touch the file
				//so we know not to send anymore for a while.
				else 
				{
					touch(self::CONDOR_MOUNT_ALERT_FILE);
					$msg = 'Mount '.CONDOR_ROOT_DIR.' does not exist. (Hostname: '.$srv.')';
					if(class_exists('CondorException'))
					{
						$x = new CondorException($msg,CondorException::ERROR_MOUNT);
					}
				}
				return false;
			}
			else 
			{
				//The mounts there, if the file exists, just rmeove it
				if(file_exists(self::CONDOR_MOUNT_ALERT_FILE))
				{
					unlink(self::CONDOR_MOUNT_ALERT_FILE);
				}
			}
		}
		catch (Exception $e)
		{
			return false;
		}
	}
}

?>
