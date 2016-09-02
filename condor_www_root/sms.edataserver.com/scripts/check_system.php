<?

	defined('SMS_DEBUG')                 || define('SMS_DEBUG', false);
	defined('SMS_QUEUED_WARNING_COUNT')  || define('SMS_QUEUED_WARNING_COUNT', 400);
	defined('SMS_HOURS_WITHOUT_MESSAGE') || define('SMS_HOURS_WITHOUT_MESSAGE', 3);
	
	// email addresses to send alerts to
	$send_to = array(
		  'don.adriano@sellingsource.com'
		, 'donadriano@gmail.com'
		, 'david.hickman@sellingsource.com'
	);
	
	$script = isset($argv[0]) ? $argv[0] : 'check_system.php';
	$mode   = isset($argv[1]) ? strtoupper($argv[1]) : 'unknown';
	$hours  = isset($argv[2]) ? $argv[2] :  SMS_HOURS_WITHOUT_MESSAGE;

	defined('MODE') || define('MODE', $mode);  // required to make sms config choose correct database
	
	$base = dirname(realpath(__FILE__));
	require_once($base . '/../www/sms.php');  // cannot include this until MODE is set.
	require_once('logsimple.php');

	if ( SMS_DEBUG || $mode == 'LOCAL' ) $send_to = array('david.hickman@sellingsource.com');
	
	$error = false;
		
	$sms_obj = new SMS();
	
	$count_outgoing_queued = $sms_obj->Count_Outgoing_Queued();
	if ( !is_numeric($count_outgoing_queued) || $count_outgoing_queued > SMS_QUEUED_WARNING_COUNT ) $error = true;

	$unsuccessful_modems = $sms_obj->Get_Unsuccessful_Modems($hours);
	if ( !is_array($unsuccessful_modems) || count($unsuccessful_modems) > 0 ) $error = true;

	$msg = "mode=$mode, hours=$hours, count_outgoing_queued=$count_outgoing_queued, unsuccessful_modems=" . logsimpledump($unsuccessful_modems,false) . ', send_to=' . logsimpledump($send_to,false);
	logsimplewrite($msg);
	
	if ( $error )
	{
		// send the error message
		$subject = 'SMS Warning (time=' . date('Y-m-d H:i:s') . ", mode=$mode, hours=$hours)";
		
		foreach ($send_to as $address)
		{
			mail($address, $subject, $msg);
		}
	}

	
?>