<?php
	ini_set ('magic_quotes_runtime', 0);
	ini_set ('implicit_flush', 1);
	ini_set ('output_buffering', 0);
	ob_implicit_flush ();
	list ($ss, $sm) = explode (" ", microtime ());
	// Make sure we keep running even if user aborts
	ignore_user_abort (TRUE);

	// Let it run forever
	set_time_limit (0);

	// Database connectivity
	require_once ("/virtualhosts/lib/mysql.3.php");
	require_once ("/virtualhosts/lib/error.2.php");
	//mail
	require_once('/virtualhosts/lib/prpc/client.php');





	define ("HOST", "selsds001");
	define ("USER", "sellingsource");
	define ("PASS", "%selling\$_db");
	define ("VISITOR_DB", "lp");
	// Build the sql object
	$sql = new MySQL_3 ();

	// Try the connection
	$result = $sql->Connect ("BOTH", HOST, USER, PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	
	//############################################
	//kill visitor info and fully completed apps
	//############################################
	$query = "select min(modifed_date) as mindate from session_site";
	$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	$session = $sql->Fetch_Object_Row ($result);
	
	$day = $session->mindate;
	$stamp = mktime(0,0,0,substr($day,4,2),substr($day, 6,2),substr($day, 0, 4));
 
	$start_date = date("YmdHis", $stamp);
	$end_date = date("YmdHis", strtotime("+1 day", $stamp));
	$limit_date = date("YmdHis", strtotime("-7 days"));
	$current_date = date ("Y-m-d-H-i-s");

	$query = "select * from `session_site` where modifed_date between '".$start_date."' AND '".$end_date."'";
	$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);

	$total_found = $sql->Row_Count($result);
	$c = 0;

	
	while ($session = $sql->Fetch_Object_Row ($result))
	{
		$delete_array[] = $session->session_id;
	}
	
	
	if(is_array ($delete_array) && ($end_date < $limit_date))
		{
			foreach ($delete_array as $ses_id)
			{
				$query = "delete from `session_site` where session_id = '".$ses_id."'";
				$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test ($result, TRUE);
				$c++;
				
			}
		}
		

	$query = "OPTIMIZE TABLE `session_site`";
	echo $query."\n";
	$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	
	//setup the mail 
	
	$header = new StdClass ();
	$header->subject = 'lp session_site update ';
	$header->sender_name = 'noreply';
	$header->sender_address = 'mel.leonard@thesellingsource.com';
	
	$recipient1 = new StdClass ();
	$recipient1->type = 'to';
	$recipient1->name = '';
	$recipient1->address = 'mel.leonard@thesellingsource.com';
	
	$recipients = array ($recipient1);
	
	$message = new StdClass ();
	$message->text = "Daily lp session scrub ".$start_date. " and ". $end_date ."\r\n";
	$message->text .= "Total of ".$total_found. " rows found.\r\n";
	$message->text .="Total of ".$c. " rows deleted on " . $current_date . ".\r\n";
	
	
	$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
	
	$mailing_id = $mail->CreateMailing ("lp session scrubber update", $header, NULL, NULL);
	$package_id =$mail->AddPackage ($mailing_id, $recipients, $message);
	$result = $mail->SendMail ($mailing_id);

?>