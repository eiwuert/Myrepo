<?php
/**
 * Real simple script that monitors the mail queue and 
 * makes sure that it's keeping up with the sending. And
 * if not, it lets someone know
 *
 */
define('CONDOR_DIR','/virtualhosts/condor.4.edataserver.com/');

require_once('mysqli.1.php');
require_once(CONDOR_DIR.'lib/config.php');
define('EXECUTION_MODE',MODE_LIVE);
require_once(CONDOR_DIR.'lib/mail_queue.php');
require_once(CONDOR_DIR.'lib/condor_exception.php');


$mail_queue = new Mail_Queue(EXECUTION_MODE);

$start_date = date('Ymd000000',(time() - 86400));

$hour = date('H');
//if it's between midnight and 5am 
//expect longer queue times due to 
//cronjobs being run
if($hour < 5)
{
	$end_date = date('YmdHis',(time() - 3600));
	$time_checked = "1 hour";
}
else
{
	//15 minutes ago = 900 seconds
	$end_date = date('YmdHis',(time() - 1800));
	$time_checked = "30 minutes";
}
$mails = $mail_queue->Get_Queued_Mails('queued',$start_date,$end_date);
if(is_array($mails))
{
	$count = count($mails);
}
else 
{
	$count = -1;
}
if($count > 0)
{
	$msg = "There are $count emails in queued status that are older than $time_checked.\n\n";

	// If there's emails, this should be an array
	if (is_array($mails))
	{
		for($i = 0; $i < 5; $i++)
		{
			$msg .= "Mail Queue ID: {$mails[$i]->mail_queue_id}\n";
			$msg .= "Account ID:    {$mails[$i]->account_id}\n";
			$msg .= "Document ID:   {$mails[$i]->document_id}\n";
			$msg .= "Date Created:  {$mails[$i]->date_created}\n";
			$msg .= "\n\n";
		}
	}

	$reporter = new CondorException($msg,CondorException::ERROR_EMAIL);
}
