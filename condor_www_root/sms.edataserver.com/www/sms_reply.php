<?
	
	$from = (isset($_GET['from']) ? $_GET['from'] : FALSE);
	$message = (isset($_GET['message']) ? $_GET['message'] : FALSE);
	$modem = (isset($_GET['modem']) ? $_GET['modem'] : FALSE);
	
	if (($from !== FALSE) && ($message !== FALSE) && ($modem !== FALSE))
	{
		include_once 'sms.php';
		
		$sms_obj = new SMS();
		$sms_obj->SMS_Reply($from, $message, $modem);

	}

?>