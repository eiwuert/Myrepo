<?php
/**
 * Just a quick script to clean up the mail_queue table
 *
 */

//The LOCK_FILE that the send_mail cron uses.
define('SEND_CRON_LOCKFILE','/tmp/condor_send_mail.lock');
define('EXECUTION_MODE','LIVE');
define('CONDOR_DIR','/virtualhosts/condor.4.edataserver.com');

require_once('mysqli.1.php');
require_once('mysql_pool.php');
require_once(CONDOR_DIR.'/lib/config.php');
require_once(CONDOR_DIR.'/lib/condor_exception.php');

//This is basically going to check for the lock file
//We keep checking for it if it's there until it's gone
//We try 10 or somesuch times and let someone know if it
//still can't go. THEN we lock it so the send can run anymore
//and start the delete query. Once that's done we unlock it
//and quit out nicely

$attempts = 0;
do 
{
	if(file_exists(SEND_CRON_LOCKFILE))
	{
		$locked = TRUE;
		$attempts++;
		sleep(20);
		if($attempts > 10)
		{
			$x = new CondorException("Mail Queue Cleaner could not gain send_mail lock.",CondorException::ERROR_EMAIL);
			exit();
		}
	}
	else 
	{
		$locked = FALSE;
		//make sure we gain the lock, so that we don't try 
		//and send while we're deleting.
		touch(SEND_CRON_LOCKFILE);
	}
}
while($locked === TRUE);

$sql = MySQL_Pool::Connect('condor_' . EXECUTION_MODE);

$query = '
	DELETE FROM
		mail_queue
	WHERE
		date_modified <= DATE_SUB(CURDATE(), INTERVAL 30 DAY);
		
';
//In reality, the ideal solution would be to get the last couple days of data
//truncate, reinsert so that it's optimized and resets the mail_queue_id
//but this would involve a total lockdown of the table for however 
//long the process took. Not really a viable solution every night
$sql->Query($query);
if(file_exists(SEND_CRON_LOCKFILE))
{
	unlink(SEND_CRON_LOCKFILE);
}
