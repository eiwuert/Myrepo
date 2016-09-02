<?php

/*                 MAIN processing code               */

function Main()
{
	global $server;
	$ach = new ACH($server);
	global $co;
	global $_BATCH_XEQ_MODE;
	$log = $server->log;
	$db = ECash_Config::getMasterDbConnection();
	$company_id = $server->company_id;

	require_once(LIB_DIR."common_functions.php");
	require_once(SQL_LIB_DIR. "util.func.php");
	
	$today = date("Y-m-d");	

	// First make sure we haven't run today already
	$run_state = Check_Process_State($db, $company_id, "nsf_mailer", $today);
	if ($run_state == 'completed') 
	{
		echo "Ran already today.\n";
		return true;
	}
	// Make sure we only continue if the rescheduling is done.
	$reschedule_state = Check_Process_State($db, $company_id, "ach_reschedule", $today);

	// A few timer symbols...
	$insufficient_funds_timer = "({$today}) Insufficient_Funds_Mailer";
	
	$server->timer->Timer_Start($insufficient_funds_timer);

	try 
	{
			$pid = Set_Process_Status($db, $company_id, 'nsf_mailer', 'started', $today);
			if(Generate_ACH_Mailer_Entries($today) != false)
			{
				$status = Upload_ACH_Mailer_File($server, $today);

				if($status === true) 
				{
					Set_Process_Status($db, $company_id, 'nsf_mailer', 'completed', $today, $pid);
				} 
				else 
				{
					Set_Process_Status($db, $company_id, 'nsf_mailer', 'failed', $today, $pid);
					echo "Unable to FTP File.\n";
				}
			}
			else
			{
				echo "Generate_ACH_Mailer_Entries returned false.\n";
				Set_Process_Status($db, $company_id, 'nsf_mailer', 'failed', $today, $pid);
			}
		} 
		catch (Exception $e) 
		{
			Set_Process_Status($db, $company_id, 'nsf_mailer', 'failed', $today, $pid);
			throw $e;
		}
		$server->timer->Timer_Stop($insufficient_funds_timer);

	return true;
}

function Upload_ACH_Mailer_File($server, $day)
{
	$log = $server->log;
	$mailer_host = eCash_Config::getInstance()->INS_FUNDS_MAILER_HOST; 
	$mailer_user = eCash_Config::getInstance()->INS_FUNDS_MAILER_USER; 
	$mailer_pass = eCash_Config::getInstance()->INS_FUNDS_MAILER_PASS; 
	
	
	// hardcoding directories like this... Yea, I know.
	$filename = $server->company . "M" . substr($day, 5, 2) . substr($day, 8, 2) . substr($day, 0, 4) . ".csv";
	$local_file = eCash_Config::getInstance()->NSF_MAILER_DIR . "/{$filename}";

	if(file_exists($local_file))
	{	
		$log->Write("Attempting to upload {$local_file}");
		
		// and now upload it
		if( ! $ftp = ftp_connect($mailer_host) )
		{
			$log->Write("Could not connect to ftp host [$mailer_host] in " . __FILE__ . " on line " . __LINE__ . ".");
			return false;
		}

		if( ! $login = ftp_login($ftp, $mailer_user, $mailer_pass) )
		{
			$log->Write("Login failure to ftp host [$mailer_host] using [$mailer_user:********] in " . __FILE__ . " on line " . __LINE__ . ".");
			return false;
		}

		if( ! $upload = ftp_put($ftp, $filename, $local_file, FTP_ASCII) )
		{
			$log->Write("Could not write contents of {$local_file} to remote host in " . __FILE__ . " on line " . __LINE__ . ".");
			return false;
		}

		ftp_close($ftp);
		$log->Write( "ACH Returns mailer file successfully uploaded to $mailer_host", LOG_INFO );
		return true;
	}
	else
	{
		$log->Write("ACH Return mailer file \"{$local_file}\" could not be opened!");
		throw new Exception("Could not open {$local_file} for upload.");
	}
}
