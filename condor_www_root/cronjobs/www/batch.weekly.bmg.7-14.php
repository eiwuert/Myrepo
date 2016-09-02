<?php

	/*****************************************************/
	// Weekly cronjob for BMG
	// runs monday mornings, sends data to a couple people
	// and sends counts to a couple of accounting people
	// scrubs against funded emails
	// scrubs against previously sent email addresses (retention time? unknown at this point)
	// - john hargrove(john.hargrove@thesellingsource.com), 11-10-2004
	/*****************************************************/
	
	
	// Includes/Defines
	/*****************************************************/
	require_once('csv.1.php');
	require_once('mysql.3.php');
	require_once("error.2.php");
	require_once("ftp.2.php");
       
	
	
	// Data
	/*****************************************************/

	$begin	=	strtotime(date("m/d/Y")." -14 days");
	$end		=	strtotime(date("m/d/Y")." -7 days");
	
	$start	=	date("Y-m-d",$begin);
	$end		=	date("Y-m-d",$end);
	
	// for the filename
	$_start	=	$start;
	$_end	=	$end;
	
	$sql		=	new MySQL_3();
	
	
	// Code
	/*****************************************************/
	
	// grab our records
	$sql->Connect("BOTH", "selsds001", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__));

	$query_partial = "
			SELECT
				p.first_name,
				p.last_name,
				r.address_1,
				r.city,
				r.state,
				r.zip,
				p.home_phone,
				p.email,
				p.application_id
			FROM
				personal p
			JOIN
				residence r ON p.application_id=r.application_id
			JOIN
				campaign_info c ON p.application_id=c.application_id
			WHERE
				c.modified_date BETWEEN '$start' AND '$end'
				AND r.state!='CA'
			";

	$query_full = "
			SELECT
				p.first_name,
				p.last_name,
				r.address_1,
				r.city,
				r.state,
				r.zip,
				p.home_phone,
				p.email,
				p.application_id
			FROM
				application ap
			JOIN
				personal p ON ap.application_id=p.application_id
			JOIN
				residence r ON ap.application_id=r.application_id
			WHERE
				ap.created_date BETWEEN '$start' AND '$end'
				AND r.state!='CA'
			";

	$rs_partial = $sql->Query("olp_bb_partial", $query_partial, Debug_1::Trace_Code(__FILE__,__LINE__));
	$rs_full = $sql->Query("olp_bb_visitor", $query_full, Debug_1::Trace_Code(__FILE__,__LINE__));
	
	
	$headers_partial = array(
		"first_name",
		"last_name",
		"home_phone",
		"email");
	
	$headers_full = array(
		"first_name",
		"last_name",
		"address_1",
		"city",
		"state",
		"zip",
		"home_phone",
		"email");
		
	$suffix_partial="partial.csv";
	$suffix_full = "full.csv";
	
	$filename_partial = "/tmp/BMG_weekly_{$_start}-{$_end}_{$suffix_partial}";
	$filename_full = "/tmp/BMG_weekly_{$_start}-{$_end}_{$suffix_full}";
	
	$filename_p_ = "BMG_weekly_{$_start}-{$_end}_{$suffix_partial}";
	$filename_f_ = "BMG_weekly_{$_start}-{$_end}.csv";
	
	$fp_partial = fopen($filename_partial,"w");
	$csv_partial = new CSV(
			array(
				"stream" => $fp_partial,
				"header" => $headers_partial,
				"forcequotes" => true,
				"flush" => false
			)
		);
	
	$fp_full = fopen($filename_full,"w");
	$csv_full = new CSV(
			array(
				"stream" => $fp_full,
				"header" => $headers_full,
				"forcequotes" => true,
				"flush" => false
			)
		);
	
		
	
	// scrub
	$dupecount=0;
	$goodcount=0;
	$fundedcount=0;
	$totalcount=0;
	
	print "\n\nWorking...\n";
	$recs_all = array();
	
	while ($row = $sql->Fetch_Array_Row($rs_partial))
	{
		$q =  "SELECT email FROM nms_funded WHERE email='".$row['email']."'";
		
		$res = $sql->Query("scrubber",$q,Debug_1::Trace_Code(__FILE__,__LINE__));
		
		if($sql->Row_Count($res)>0)
		{
			$fundedcount++;
		}
		else
		{
			$recs_all[] = array("partial",$row);
			$goodcount++;
		}
		$totalcount++;
	}


	while ($row = $sql->Fetch_Array_Row($rs_full))
	{
		$q =  "SELECT email FROM nms_funded WHERE email='".$row['email']."'";
		
		$res = $sql->Query("scrubber",$q,Debug_1::Trace_Code(__FILE__,__LINE__));
		
		if($sql->Row_Count($res)>0)
		{
			$fundedcount++;
		}
		else
		{
			$recs_all[] = array("full",$row);
			$goodcount++;
		}
		$totalcount++;
	}
	$baddata=0;

	
	
	// evil fake seed data. will only go once. in the future it will bounc off bmg_sent a lot
	$recs_all[] = array
	(
		"full",
		array
		(
			'first_name'=>'Sally',
			'last_name'=>'Gambrell',
			'address_1'=>'3535 N boulder',
			'city'=>'las vegas',
			'state'=>'NV',
			'zip'=>'89121',
			'home_phone'=>'7024331818',
			'email'=>'sally_8998@yahoo.com',
			'application_id'=>'1972555'
		)
	);
	

	$final++;
	foreach($recs_all as $args)
	{
		list($type,$row) = $args;
		$email = $row['email'];

		if($row['address_1']==''||$row['city']==''||$row['state']==''||$row['zip']=='')
		{
			$goodcount--;
			$baddata++;
		}
		else
		{
			// insert into sent table, and add it to our CSV
			$q = "
				INSERT INTO
					bmg_sent
				SET
					application_id='{$row['application_id']}',first_name='{$row['first_name']}',last_name='{$row['last_name']}',phone_number='{$row['home_phone']}',email_address='$email'";
			$rs = $sql->Query("lead_generation", $q, Debug_1::Trace_Code(__FILE__,__LINE__));
			
			if ( $sql->Affected_Row_Count($rs) > 0 )
			{
				$csv_full->recordFromArray($row);
				$final++;
			}
			else
			{
				$goodcount--;
				$dupecount++;
			}
		}
	}
	
	print "\n\n\tTotal:\t\t$totalcount\n\tFunded:\t\t$fundedcount\n\tDuplicate:\t$dupecount\n\tBad Data:\t$baddata\n\tGood:\t\t$goodcount\n\n";
			

	$csv_partial->flush();
	$csv_full->flush();

	$ftp_client = new FTP();
	$ftp_client->server = "ftp.sellingsource.com";
	$ftp_client->user_name = "bmg";
	$ftp_client->user_password = "password";
	
	$ftp_client->do_Connect($ftp_client);
	
	$ftp_client->file = 
		
		array(
			"$filename_full,/$filename_f_"
		);
	
	
	if (!$ftp_client->do_Put($ftp_client, true, false))
	{
		print "\nftp upload failed hardcore.";	
	}

		
	
// BUILD EMAIL REPORT AND SEND
// *************************************************	
	// VARS - Email Generation		
		// Email Headers
			// $email_smtp_server = "mail.sellingsource.com";
			$email_port = 25;
			$email_url = "sellingsource.com";
			$email_s_name = "John Hawkins";
			$email_s_address = "john.hawkins@thesellingsource.com";	
			$email_subject = "BMG Weekly Leads for [{$_start} -> {$_end}]";
				
		// Email Content	
			$mailbody_text = "Good morning, \r\n\r\n
							  Your files have been uploaded to the ftp server. The file names are:\n\t"
							  ."{$filename_f_}\n\n
							  These files are database extracts which represent the 
							  Prequal and Completed Applications placed during "
							  ."{$_start} -> {$_end}\r\n\r\n;
							  Regards, \r\n \r\n;
							  John Hawkins \r\n;
							  Director of Technical Services \r\n;
							  SellingSource.com \r\n;
							  e: johnh@sellingsource.com \r\n;
							  p: 800.391.1178 \r\n;
							  o: 702.407.0707
							 ";
							  
			$mailbody_html = "<html><body>
							  Good morning, <br><br>
							  Your files have been uploaded to the ftp server. The file names are:<ul><li>"
							  ."{$filename_f_}</li></ul>
							  These files are database extracts which represent the 
							  Prequal and Completed Applications placed during "
							  ." {$_start} -> {$_end}  
							  <br><br><br>
							  Regards, 
							  <br><br>
							  John Hawkins <br>
							  Director of Technical Services <br>
							  <a href='http://sellingsource.com'>SellingSource.com</a> <br>
							  e: johnh@sellingsource.com <br>
							  p: 800.391.1178 <br>
							  o: 702.407.0707
							  </body></html>							 
							 ";
			$report_text = "Good morning,\r\n\r\n
							This is a notification email to inform you that the weekly BMG batch was completed and 
							leads have been sent.  There were {$final} records. This is for the daterange: {$_start} -> {$_end}\r\n
							  Regards, \r\n \r\n;
							  John Hawkins \r\n;
							  Director of Technical Services \r\n;
							  SellingSource.com \r\n;
							  e: john.hawkins@thesellingsource.com \r\n;
							  p: 800.391.1178 \r\n;
							  o: 702.407.0707";
			$report_html = "<html><body>
							Good morning,<br><br>
							This is a notification email to inform you that the weekly BMG batch was completed and 
							leads have been sent.  There were {$final} records. This is for the daterange: {$_start} -> {$_end}<br><br>
							  <br><br><br>
							  Regards, 
							  <br><br>
							  John Hawkins <br>
							  Director of Technical Services <br>
							  <a href='http://sellingsource.com'>SellingSource.com</a> <br>
							  e: john.hawkins@thesellingsource.com <br>
							  p: 800.391.1178 <br>
							  o: 702.407.0707						
						</body></html>";
			
	
	// Build Email Header
	$header = new StdClass ();
	$header->smtp_server = $email_smtp_server;
	$header->port = $email_port;
	$header->url = $email_url;
	$header->subject = $email_subject;
	$header->sender_name = $email_s_name;
	$header->sender_address = $email_s_address;
	
	
	// Build Email Recipient(s)

		// Build the primary recipient
		$recipient1 = new StdClass ();
		$recipient1->type = "To";
		$recipient1->name = "Joel";
		$recipient1->address = "JOEL@THEBMGGROUP.COM";// "john.hargrove@thesellingsource.com"; //"jhawkins@sellingsource.com";
		
/*brian.rauch@thesellingsource.com
pamelas@partnerweekly.com
celeste.christman@thesellingsource.com
laura.gharst@partnerweekly.com
john.hawkins@thesellingsource.com*/
		
		$r = new StdClass();
		$r->type = "To";
		$r->name = "Brian R.";
		$r->address = "brian.rauch@thesellingsource.com";
				
		$r0 = new StdClass();
		$r0->type = "To";
		$r0->name = "Pamela S.";
		$r0->address = "pamelas@partnerweekly.com";
		
		$r1 = new StdClass();
		$r1->type = "To";
		$r1->name = "Celeste C.";
		$r1->address = "celeste.christman@thesellingsource.com";
		
		$r2 = new StdClass();
		$r2->type = "To";
		$r2->name = "Laura G.";
		$r2->address = "laura.gharst@partnerweekly.com";
				
		$r3 = new StdClass();
		$r3->type = "To";
		$r3->name = "John Hawkins";
		$r3->address = "john.hawkins@thesellingsource.com";


	// Build Recipient List
	$recipients = array ($recipient1);
	$recipients_b = array ($r,$r0,$r1,$r2,$r3);

	// Build Email Message
	$message = new StdClass ();
	$message->text = $mailbody_text;
	$message->html = $mailbody_html;
	
	$msg_report = new StdClass();
	$msg_report->text = $report_text;
	$msg_report->html = $report_html;
	
	// Send Email via SOAP
	
		// Create the Mail Object and Send the Mail	
		include_once("prpc/client.php");
		$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
			

		// Key Line - Create the mailing (Name of mailing, headers, scheduled date, scheduled time) DO NOT USE SCHEDULING!!!
		$mailing_id = $mail->CreateMailing ("survey_report", $header, NULL, NULL);
	
		// Key Line - Add the package to the mailing (mailing_id, array of recipients, message, array of attachments)
		$package_id = $mail->AddPackage ($mailing_id, $recipients, $message);
		$package_id_b = $mail->AddPackage ($mailing_id, $recipients_b, $msg_report);
	
		// Key Line - Tell the server to process the mailing (send all emails)
		 $result = $mail->SendMail ($mailing_id);
	
		// Debug Code - Use if you want to see the soap stuff
		// print_r ($mail->__get_wire ());
		echo " ... Mailing Id: ".$mailing_id."\n";
		echo " ... Result: ".$result."\n";
		echo " ... Recipients: \n";	
	
	fclose($fp_partial);
	fclose($fp_full);
	
	
	
?>