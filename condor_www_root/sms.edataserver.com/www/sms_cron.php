<?
	
	$script = array_shift($argv);
	$mode = array_shift($argv);
	
	// needed for sms.config.php
	define('MODE', $mode);
	
	$base = dirname(realpath(__FILE__));
	include_once $base.'/sms.php';
		
	$sms_obj = new SMS();
	
	$response = true;
	
	while($response)
	{
		$response = $sms_obj->Scheduled_SMS();
	}

	$sms_obj->Catch_Messages_Stuck_In_Processing();
	
?>