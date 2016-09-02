<?php
/**
 * This script handles incoming Callbacks from Hylafax.
 * 
 * @author Brian Feaver
 * @copyright Copyright 2006 The Selling Source, Inc.
 */

require_once('automode.1.php');
require_once('../lib/condor.class.php');

$dispatch_id = $_GET['dispatch_id'] ? $_GET['dispatch_id'] : FALSE;
$status = $_GET['status'] ? $_GET['status'] : FALSE;
$type = $_GET['type'] ? $_GET['type'] : NULL;

if($dispatch_id && $status)
{
	// We don't like magic...
	if(get_magic_quotes_gpc())
	{
		$dispatch_id = stripslashes($dispatch_id);
		$status = stripslashes($status);
	}
	
	// Dispatch ID should be a number, so let's convert it to an integer
	$dispatch_id = intval($dispatch_id);
	
	$mode = new Auto_Mode();
	$mode = $mode->Fetch_Mode($_SERVER['HTTP_HOST']);
	
	$condor = new Condor($mode);
	
	if ($type == NULL)
	{
		$condor->Update_Status($dispatch_id, $status);
	}
	else
	{
		$condor->Update_Status($dispatch_id, $status, $type);
	}
}
?>
