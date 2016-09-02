<?
	
	$message_id = getHttpVar('message_id');
	$status     = getHttpVar('status');
	$receiver   = getHttpVar('receiver');
	$sender     = getHttpVar('sender');
	$message    = getHttpVar('message');
	
	include_once 'sms.php';
		
	$sms_obj = new SMS();
	$sms_obj->Update_Message_Status($message_id, $status);


	function getHttpVar( $var, $default='' ) {
		return
		'POST' == $_SERVER['REQUEST_METHOD']
			? (isset($_POST[$var]) ? trim($_POST[$var]) : $default)
			: (isset($_GET[$var])  ? trim($_GET[$var])  : $default);
	}
?>
sms_status_update.php(ok)
