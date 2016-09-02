<?php
/**
 * Basically check the "bounces" account 
 * grab each mail that's in it, rip the "dispatch_id" 
 * out of the From: header then add it away
 */

define('BOUNCE_SERVER','mail1.condoremailservices.com');
define('BOUNCE_PORT','110');
define('BOUNCE_USER','condor');
define('BOUNCE_PASS','test.condor');
define('BOUNCE_MAILBOX','INBOX');
define('LOCK_FILE','/tmp/condor_process_bounces.lock');
define('MODE','RC');
define('MAX_REATTEMPTS',4);

require_once 'Mail.php';
require_once 'Mail/mimeDecode.php';
require_once 'Mail/IMAPv2.php';
require_once 'Console/Getopt.php';
require_once '../lib/mail_queue.php';
require_once '../lib/config.php';
require_once '../lib/condor.class.php';
require_once '../lib/security.php';


function Connect_To_Bounce_Account()
{
	$client  = new Mail_IMAPv2();
	$url = 'pop3://'.urlencode(BOUNCE_USER).':'.
		urlencode(BOUNCE_PASS).'@'.BOUNCE_SERVER.':'.BOUNCE_PORT.'/'.BOUNCE_MAILBOX.'#notls';
	if(!$client->connect($url))
	{
		throw new Exception ("Could not connect to bounce account.");
	}
	return $client;
}

function Get_Mail_Queue()
{
	$mode = MODE;
	$sql = MySQL_Pool::Connect('condor_' . $mode);
	if(!$sql instanceof MySQLi_1)
	{
		throw new Exception("could not connect to $mode database.");
	}
	return new Mail_Queue($mode, $sql);
}

function Check_Bounces()
{
	//Grab New Emails
	//Default Params
	$mail = Connect_To_Bounce_Account();
	$params = array();
	$params['include_bodies'] = true;
	$params['decode_bodies'] = true;
	$params['decode_headers'] = true;

	//Get Message Count
	$msg_count = $mail->messageCount();
	
	if($msg_count == 0)
	{
//		echo("No messages");
		exit(0);
	}
	$mail_queue = Get_Mail_Queue();
	for($mid = 1; $mid <= $msg_count; $mid++)
	{
		$header = $mail->getRawHeaders($mid);
		$body = $mail->getRawMessage($mid);
//		echo("Checking thing");
		$params['input'] = $header . $body;
		$structure = Mail_mimeDecode::decode($params);
	
		if(isset($structure->headers['x-queue-id']))
		{
			$queue_id = intval(trim($structure->headers['x-queue-id']));
		}
		if(isset($structure->headers['x-dispatch-id']))
		{
			$dispatch_id = intval(trim($structure->headers['x-dispatch-id']));
		}
		$attempts = $mail_queue->Get_History_Count($dispatch_id);
		if($attempts < MAX_REATTEMPTS)
		{
			$mail_queue->Update_Status($queue_id,'requeued', 'bounce');
		}
		else 
		{
			$mail_queue->Update_Status($queue_id,'failed','bounced');
		}
		$mail->delete($mid);	
	}
	//Run expunge for the hell of it
	$mail->expunge();
}

Check_Bounces();
