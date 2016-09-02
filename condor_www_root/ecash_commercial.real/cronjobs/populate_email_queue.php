<?php

require_once(LIB_DIR . "/common_functions.php");
require_once(SERVER_CODE_DIR . "/email_queue.class.php");
require_once(SERVER_CODE_DIR . "/email_queue_query.class.php");
require_once(SQL_LIB_DIR . "/application.func.php");
require_once(SERVER_MODULE_DIR . "/admin/docs_config.class.php");
require_once(LIB_DIR . "/Document/DeliveryAPI/Condor.class.php");

/**
 * Populates Incoming Email Queue with incoming email documents from Condor.
 *
 * This script should be called using ecash_engine.php, and will pull emails
 * for the company specified in the cron using ecash_engine.php's cli syntax.
 */
function Main()
{
	global $server;
	$company_id = $server->company_id;
	$db = ECash_Config::getMasterDbConnection();

	$start_date = getLastProcessTime($db, 'populate_email_queue', 'completed');

	if (NULL === $start_date)
	{
		$start_date = '20080701000000';
	}
	else if (EXECUTION_MODE !== 'LIVE') // local and rc time will be off, so...
	{
		$start_date = substr($start_date, 0, 8) . '000000';
	}

	$start_date = date('YmdHis', strtotime('-6 hours', strtotime($start_date)));

	$pid = Set_Process_Status($db, $company_id, 'populate_email_queue', 'started');
	ECash::getLog()->Write("populate email queue [start:{$start_date}]");
	$email_documents = eCash_Document_DeliveryAPI_Condor::Prpc()->Get_Incoming_Documents($start_date, NULL, NULL, TRUE, 'EMAIL');
	ECash::getLog()->Write(print_r($email_documents, true));

	if ( !is_array($email_documents) )
	{
		Set_Process_Status($db, $company_id, 'populate_email_queue', 'failed', NULL, $pid);
		return;
	}

	$request = new stdClass();
 	$eq = new Incoming_Email_Queue($server, $request);
 	var_dump($email_documents);
	foreach ($email_documents as $email)
	{
		$eq->Add_To_Email_Queue($company_id, $email->archive_id, FALSE, $email->recipient, $email->sender);
	}

	Set_Process_Status($db, $company_id, 'populate_email_queue', 'completed', NULL, $pid);
}

?>
