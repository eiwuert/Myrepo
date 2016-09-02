<?php

	/***
	
	
		report.weekly.state-counts.php
		-- 
		runs monday morning and checks BB submits from the last week,
		creating a tally of what states the customers reside in
	
	
	
	***/
	
	
	
	// Include/Define
	// =====================================================
	require_once('mysql.3.php');
	require_once('debug.1.php');
	require_once('error.2.php');
	
	define ( 'DB_HOST', 'selsds001' );
	define ( 'DB_USER', 'sellingsource' );
	define ( 'DB_PASS', 'password' );
	define ( 'DB_NAME', 'olp' );
	
	
	// Data
	// =====================================================
	$sql = new MySQL_3();
	$con = $sql->connect(NULL, DB_HOST, DB_USER, DB_PASS, Debug_1::Trace_Code(__FILE__,__LINE__));
	
	Error_2::Error_Test($con, TRUE);
	
	$t_start	=	strtotime("-7 days");
	$t_end		=	strtotime("-1 day");
	
	$start		=	date("Ymd000000", $t_start);
	$end		=	date("Ymd235959", $t_end);
	
	
	// Code
	// =====================================================
	
	
	$query = "
			SELECT
				r.state,
				COUNT(r.state) count
			FROM
				application a
			JOIN
				residence r USING (application_id)
			WHERE
				a.created_date BETWEEN '$start' AND '$end'
			GROUP BY r.state
			ORDER BY count DESC";

	$r = $sql->query(DB_NAME, $query, Debug_1::Trace_Code(__FILE__,__LINE__));
	Error_2::Error_Test($r, TRUE);
	$output = "\n\n" . date("m-d-Y", $t_start) . " -> " . date("m-d-Y", $t_end) . "\nBlackbox leads broken down by state.\n";
	
	$i = 0;
	while ( $row = $sql->Fetch_Object_Row($r) )
	{
		$i++;
		$output .= sprintf("\n%d: {$row->state}-{$row->count}", $i);
		$total += $row->count;
	}
	
	$output .= "\n\nTOTAL RECORDS: $total\n";
	
// BUILD EMAIL REPORT AND SEND
// *************************************************	
	// VARS - Email Generation		
		// Email Headers
			// $email_smtp_server = "mail.sellingsource.com";
			$email_port = 25;
			$email_url = "sellingsource.com";
			$email_s_name = "John Hawkins";
			$email_s_address = "john.hawkins@thesellingsource.com";	
			$email_subject = "Weekly Blackbox Submits by State";
				
		// Email Content	
			$mailbody_text = $output;

	// Build Email Header
	$header = new StdClass ();
	$header->port = $email_port;
	$header->url = $email_url;
	$header->subject = $email_subject;
	$header->sender_name = $email_s_name;
	$header->sender_address = $email_s_address;
	
	
	// Build Email Recipient(s)

		// Build the primary recipient
		$recipient1 = new StdClass ();
		$recipient1->type = "To";
		$recipient1->name = "Laura G.";
		$recipient1->address = "laura.gharst@partnerweekly.com";
		//$recipient1->name = "Keith G.";
		//$recipient1->address = "keith.mcmillen@thesellingsource.com";
	
	// Build Recipient List
	$recipients = array ($recipient1);

	// Build Email Message
	$message = new StdClass ();
	$message->text = $mailbody_text;
	
	// Send Email via SOAP
	
		// Create the Mail Object and Send the Mail	
		include_once("prpc/client.php");
		$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
			
			// Key Line - Create the mailing (Name of mailing, headers, scheduled date, scheduled time) DO NOT USE SCHEDULING!!!
		$mailing_id = $mail->CreateMailing ("survey_report", $header, NULL, NULL);
	
		// Key Line - Add the package to the mailing (mailing_id, array of recipients, message, array of attachments)
		$package_id =$mail->AddPackage ($mailing_id, $recipients, $message, NULL);
		
			// Key Line - Tell the server to process the mailing (send all emails)
		 $result = $mail->SendMail ($mailing_id);
	
		echo " ... Mailing Id: ".$mailing_id."\n";
		echo " ... Result: ".$result."\n";

		echo "\n";
		echo "Process Completed.";
		echo "\n\n";	
		
?>
