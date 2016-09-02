<?php

	//============================================================
	// AME Enterprises Lead Confirmation Cronjob
	// Real Time Posts from Blackbox
	// Runs Nightly - Grabs data from (olp - blackbox_state)
	// Sends Confirmation Email with Number of Records for that Day
	// - myya perez(myya.perez@thesellingsource.com), 03-25-2005
	//============================================================


	// INCLUDES / DEFINES
	//============================================================

	define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

	require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
	require_once("mysql.3.php");
	require_once("diag.1.php");
	require_once("lib_mode.1.php");
	require_once("prpc/client.php");
	
	$mode = 'LIVE';

	$date 	= date("m-d-Y", strtotime("-1 day"));
	$result = array();

	$yesterday  = date("Ymd", strtotime("-1 day"));
	$today = date("Ymd");

	$start 	= $yesterday."000000";
	$end = $today."000001";


	// SELECT DATA
	//============================================================

	$query_bb2_1 = "
		SELECT
			application_id,winner
		FROM
			blackbox_post
		WHERE
			date_created BETWEEN '$start' AND '$end'
			AND winner IN ('ame','amedd')
			AND success = 'TRUE'
		";

	$sql = new MySQL_3();
	
	if ($mode == 'RC')
	{
		$sql->Connect("BOTH", "db101.ept.tss:3317", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__));
		$result = $sql->Query("rc_olp",$query_bb2_1, Debug_1::Trace_Code(__FILE__,__LINE__));
	}
	else
	{
		$sql->Connect("BOTH", "writer.olp.ept.tss", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__));
		$result = $sql->Query("olp",$query_bb2_1, Debug_1::Trace_Code(__FILE__,__LINE__));
	}



	// GET COUNT
	//============================================================

	$i = 0;
	while ($row = $sql->Fetch_Array_Row($result))
	{
		switch ($row['winner']) {

			CASE "AME":
				$app_id_ame[] = $row['application_id'];
			break;

			CASE "AMEDD":
				$app_id_amedd[] = $row['application_id'];
			break;

		}
		$i++;
	}

    $msg  =  "AME COUNT CONFIRMATION DATE: $date\r\n";
	$msg .=  "\r\nAME Total: ".  count($app_id_ame)."\r\n";
	$msg .=  "\r\nAME_DD Total: ".  count($app_id_amedd)."\r\n";
	$msg .=  "\r\nGRAND TOTAL: $i\r\n\r\n";
	$email_msg = str_replace("\r\n","<br>",$msg);
	print($msg);



	// BUILD EMAIL
	//============================================================


	$header = array
	(
		"sender_name"	=> "John Hawkins <john.hawkins@thesellingsource.com>",
		"site_name" 	=> "maildataserver.com",
		"date" 	     	=> $date,
		"total_count"   => count($app_id_ame),
		"total_dd_count"=> count($app_id_amedd),
		"grand_total"   => count($i)
	);
 	$recipients = array
 	(
		array("email_primary_name" => "AME",   			"email_primary" => "dlambright@sbcglobal.net"),
 		array("email_primary_name" => "Hope",			"email_primary" => "Hope.Pacariem@partnerweekly.com"),
		//array("email_primary_name" => "Programmer",   	"email_primary" => "adam.englander@sellingsource.com")
	);

	$tx = new OlpTxMailClient(false,$mode);
	for($i=0; $i<count($recipients); $i++)
	{
		/*$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/ole_smtp.1.php");
		$result = $mail->Ole_Send_Mail("CRON_EMAIL_NIGHTLY_AME", 17176, $data);*/
		
		$data = array_merge($recipients[$i], $header);
		
		try 
		{
			$result = $tx->sendMessage('live', 'CRON_EMAIL_NIGHTLY_AME', $data['email_primary'], '', $data);
		
		}
		catch(Exception $e)
		{
			$result = FALSE;
		}
		
		if($result)
		{
			print "\r\nEMAIL HAS BEEN SENT TO: ".$recipients[$i]['email_primary']." .\r\n";
		}
		else
		{
			print "\r\nERROR SENDING EMAIL TO: ".$recipients[$i]['email_primary']." .\r\n";
		}
	}


?>
