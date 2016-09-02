#!/usr/bin/php
<?php
/*
 * This script will register an event to the transaction register.  This is only safe for 
 * NON-ACH items in a Live environment.  ACH or QC items will not send the transaction out
 * or create the ach or ecld table entries, etc.  It should be safe for testing purposes
 * in RC environments though.
 *
 * This utility uses the current configuration based on the ../www/config.php file.
 */

	require_once(realpath("../www/config.php"));
	require_once("mini-server.class.php");
	require_once(LIB_DIR . 'common_functions.php');
	require_once(SQL_LIB_DIR . 'scheduling.func.php');
	require_once(COMMON_LIB_DIR . 'pay_date_calc.3.php');
	require_once("acl.3.php");
	
	// Mandatory: Company_ID and Event_ID
	if($argc > 2) 
	{ 
		$company_id = $argv[1];
		$event_id = $argv[2]; 
	
		$log = new Applog('repair', 5000000, 20);
		$mysqli = MySQLi_1e::Get_Instance();
	
		$server = new Server($log, $mysqli, $company_id);
	
	}
	else
	{
		Usage($argv);
	}
	
	// Optional Date
	if($argc > 3) 
	{
		$date_current = validate_date($argv[3]);
	}
	else
	{
	        $date_current = date("Y-m-d");
	}
	
	try 
	{
		echo "Registering Event ID: $event_id to Transaction Register for $date_current\n";
		$tids = Record_Scheduled_Event_To_Register_Pending($date_current, NULL, $event_id);
	}
	catch (Exception $e) {
		echo $e->getMessage();
		echo $e->getTrace();
		die();
	}
	echo "Returned the following transaction ID's: ";
	foreach($tids as $tid) { echo "$tid "; }
	echo "\n";
	
	function Usage($argv)
	{
		echo "Usage: {$argv[0]} [company_id] [event_schedule_id] [date]\n";
		exit;
	}
	
	/**
	 * Very simple date validation.. Returns a date in 'Y-m-d' format
	 * if valid, else dies.
	 *
	 * @param string $date
	 * @return string
	 */
	function validate_date($date)
	{
		if($unixtime = strtotime($date))
		{
			return date('Y-m-d', $unixtime);
		}
		else
		{
			die("Unable to use this date!  $date\n");
		}
		
	}