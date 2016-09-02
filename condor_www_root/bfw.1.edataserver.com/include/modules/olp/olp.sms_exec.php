<?php

/**
	@publicsection
	@public
	@brief Send SMS Class for OLP

	This is the command line script responsible for insertion into the SMS database
	for the agreed event.  Other sms cron events (due date, XX minutes after agreeing, etc.)
	can be found in the cron directory.

    @version
        1.0 2005-12-15 - Norbinn Rodrigo (Initial revision)

*/

require_once '/virtualhosts/bfw.1.edataserver.com/include/code/OLP_Applog_Singleton.php';

require_once('/virtualhosts/lib/mysql.4.php');
require_once('/virtualhosts/lib/send_sms.1.php');
require_once('/virtualhosts/bfw.1.edataserver.com/include/code/server.php');

$license = $_SERVER['argv'][1];
$mode = $_SERVER['argv'][2];
$cell_phone = $_SERVER['argv'][3];
$property_short = $_SERVER['argv'][4];
list($promo_id, $promo_sub_code) = explode('_p_', $_SERVER['argv'][5]);

$server = Server::Get_Server('LIVE', 'SITE_TYPES');
$database = $server['db'];
$sql = new MySQL_4($server['host'], $server['user'], $server['password']);
$sql->Connect();


$sms = new Send_SMS();
if (!$status=$sms->SMS_Agreed($sql, $license, $promo_id, $promo_sub_code, $cell_phone, $mode, $property_short))
{
	// Send message only if it is a valid number
	if ($sms->valid['is_valid'] === TRUE && strlen(preg_replace("/0/", "", $cell_phone))>0)
	{
		require_once('/virtualhosts/lib5/prpc/client.php');
		unset($data);
		$data = array(
			"sender_name"	=> "SMS Alerts <no-reply@sellingsource.com>",
			"subject"		=> "SMS Agreed Failed",
			"cell_phone"		=> $cell_phone
		);
		$recipients = array(
			//array(	"email_primary_name" => "Norbinn Rodrigo",
			//		"email_primary" => "norbinn.rodrigo@sellingsource.com"),
			array(	"email_primary_name" => "Ray Lopez",
					"email_primary" => "raymond.lopez@sellingsource.com"),			
			array(	"email_primary_name" => "Mike Genatempo",
					"email_primary" => "mike.genatempo@sellingsource.com"),
		);
		
		// Instantiate the prpc_client out here so it doesn't get called for every recipient
		require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
		$tx = new OlpTxMailClient(false);
		
		$mail_failed = false;
        $last_data = null;
		foreach ($recipients as $recipient)
		{
			$senddata = array_merge($recipient, $data);
			try 
			{
				$result = $tx->sendMessage('live','SMS_ALERT_AGREE_INSERT_FAIL',
					$data['email_primary'],'',$data);
			}
			catch (Exception $e)
			{
				$mail_failed = true;
				$last_data = $senddata;
			}
				
		}
        if($mail_failed) 
        {
            $ole_applog = OLP_Applog_Singleton::Get_Instance(APPLOG_OLE_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE, APPLOG_UMASK);
            $ole_applog->Write("OLE Send Mail failed. Last message: \n" . print_r($last_data,true) . "\nCalled from " . __FILE__ . ":" . __LINE__);
        }
	}
}

?>
