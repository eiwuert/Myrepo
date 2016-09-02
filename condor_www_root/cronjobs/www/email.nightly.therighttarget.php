<?php

	//============================================================
	// The Right Target Lead Confirmation Cronjob
	// Real Time Posts from Blackbox
	// Runs Nightly - Grabs data from (olp_bb_visitor - blackbox_state) 
	// Sends Confirmation Email with Number of Records for that Day
	// - myya perez(myya.perez@thesellingsource.com), 03-25-2005
	//============================================================
	
	
	// INCLUDES / DEFINES
	//============================================================	

	require_once("mysql.3.php");
	require_once("diag.1.php");
	require_once("lib_mode.1.php");
	require_once("prpc/client.php");

	$date 	= date("m-d-Y", strtotime("-1 day")); 
	$result = array();
	
	$yesterday  = date("Ymd", strtotime("-1 day"));
	$today = date("Ymd");
	
	$start 	= $yesterday."000000";
	$end = $today."000001";
	
	//testing values
	//echo '<pre>';
	//$start = "20050607000000";
	//$end  = "20050607235959";

	
	// SELECT DATA
	//============================================================
	
	$sql = new MySQL_3();
	$sql->Connect("BOTH", "selsds001", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__));

	$query_bb1_1 = "	
		SELECT
			application_id
		FROM
			blackbox_state
		WHERE
			date_created BETWEEN '$start' AND '$end'
			AND bb_trt IS NOT NULL
		";

	$query_bb1_2 = "	
		SELECT
			application_id
		FROM
			blackbox_state
		WHERE
			date_created BETWEEN '$start' AND '$end'
			AND bb_trtdd IS NOT NULL
		";		

	$query_bb1_3 = "	
		SELECT
			application_id
		FROM
			blackbox_state
		WHERE
			date_created BETWEEN '$start' AND '$end'
			AND bb_trtdd2 IS NOT NULL
		";	
		
		
	$query_bb2_1 = "	
		SELECT
			application_id
		FROM
			blackbox_post
		WHERE
			date_created BETWEEN '$start' AND '$end'
			AND winner = 'trt'
			AND success = 'TRUE'
		";
		
	$query_bb2_2 = "	
		SELECT
			application_id
		FROM
			blackbox_post
		WHERE
			date_created BETWEEN '$start' AND '$end'
			AND winner = 'trtdd'
			AND success = 'TRUE'
		";
		
	$query_bb2_3 = "	
		SELECT
			application_id
		FROM
			blackbox_post
		WHERE
			date_created BETWEEN '$start' AND '$end'
			AND winner = 'trtdd2'
			AND success = 'TRUE'
		";			

	$result[0] = $sql->Query("olp_bb_visitor",$query_bb1_1, Debug_1::Trace_Code(__FILE__,__LINE__));
	$result[1] = $sql->Query("olp_bb_visitor",$query_bb1_2, Debug_1::Trace_Code(__FILE__,__LINE__));
	$result[2] = $sql->Query("olp_bb_visitor",$query_bb1_3, Debug_1::Trace_Code(__FILE__,__LINE__));
	$result[3] = $sql->Query("olp",$query_bb2_1, Debug_1::Trace_Code(__FILE__,__LINE__));
	$result[4] = $sql->Query("olp",$query_bb2_2, Debug_1::Trace_Code(__FILE__,__LINE__));
	$result[5] = $sql->Query("olp",$query_bb2_3, Debug_1::Trace_Code(__FILE__,__LINE__));	
	
	// GET COUNT
	//============================================================	
	
	
	foreach ($result as $key => $rs)
	{
		$i = 0;
		while ($row = $sql->Fetch_Array_Row($rs))
		{
			$app_id[$key][$i] = $row['application_id'];
			$i++;
		}
		$count[$key] = count($app_id[$key]);
	}

	print "\r\n: : TRT : :\r\n";
	print "\r\nCOUNT BB 1: $count[0]\r\n";
	print "\r\nCOUNT BB 2: $count[3]\r\n";
	
	$total_trt = $count[0] + $count[3];
	print "\r\nTRT TOTAL: $total_trt";
	
	print "\r\n\r\n";
	
	print "\r\n: : TRTDD : :\r\n";
	print "\r\nCOUNT BB 1: $count[1]\r\n";
	print "\r\nCOUNT BB 2: $count[4]\r\n";
	
	$total_trtdd = $count[1] + $count[4];
	print "\r\nTRTDD TOTAL: $total_trtdd";	
	
	print "\r\n\r\n";
	
	print "\r\n: : TRTDD2 : :\r\n";
	print "\r\nCOUNT BB 1: $count[2]\r\n";
	print "\r\nCOUNT BB 2: $count[5]\r\n";
	
	$total_trtdd2 = $count[2] + $count[5];
	print "\r\nTRTDD2 TOTAL: $total_trtdd2";	
	
	print "\r\n\r\n";	

	// BUILD EMAIL
	//============================================================		

	$header = (object)array
	(
		"port"			 => 25,
		"url"			 => "maildataserver.com",
		"subject"		 => "Nightly Lead Confirmation The Right Target",
		"sender_name"	 => "John Hawkins",
		"sender_address" => "john.hawkins@thesellingsource.com"
	);
	
	$recipient = array
	(
		(object)array("type" => "to", "name" => "Josh","address" => "josh@therighttarget.com"),
		(object)array("type" => "cc", "name" => "Laura Gharst","address" => "laura.gharst@thesellingsource.com"),
		(object)array("type" => "cc", "name" => "Programmer","address" => "myya.perez@thesellingsource.com"),
	);
	
	$message = (object)array
	(
		"text" => "
COUNT CONFIRMATION

DATE: $date 

TRT TOTAL: $total_trt  
TRTDD TOTAL: $total_trtdd
TRTDD2 TOTAL: $total_trtdd2"
			
		//"text" => "There were $count test records from $today."
	);
	

	// SEND EMAIL
	//============================================================
	
	$mail = new Prpc_Client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
	$mail_id = $mail->CreateMailing("TSS_TRT_NIGHTLY", $header, NULL, NULL);
	$package_id = $mail->AddPackage($mail_id, $recipient, $message, array($attachment));
	$sender = $mail->SendMail($mail_id);
	
	print "\r\nEMAILS HAVE BEEN SENT.\r\n";


?>