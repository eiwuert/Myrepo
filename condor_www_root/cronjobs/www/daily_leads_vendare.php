<?php

	/***
	
	
		weekly_leads_vendare.php
		--
		Runs every Monday AM, and sends a CSV to people who need to know.
	
	
	***/
	
	
	
	// Includes/Defines
	
		
		require_once('mysql.3.php');
		require_once('debug.1.php');
		require_once('error.2.php');
		require_once('csv.1.php');
		
		// Emailing information
			define('EMAIL_FROM_NAME', 'John Hawkins');
			define('EMAIL_FROM', 'john.hawkins@sellingsource.com');
			define('EMAIL_TO_NAME', 'Vendare');
			define('EMAIL_TO', 'json@vendaremedia.com');
			
		// Site Information
			//define('SITE_LICENSE_KEY', '6f906a9b36e5b77b55a3f240213ecf7b');
		
		// Debug Settings
			// setting this to true will disable mail to EMAIL_TO
			define('DEBUGMODE', FALSE);
			define('DEBUG_EMAIL', 'keith.mcmillen@sellingsource.com');
	
	// Data
		// db connection
		$sql = new MySQL_3();
		Error_2::Error_Test(
		$sql->connect(NULL, "selsds001", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__))
		, TRUE);
	
		// time frame - one week
		$lastweek = strtotime("-1 day");
		$lastweek_f = date("m-d-Y",$lastweek);
		$yesterday = strtotime("-1 day");
		$yesterday_f = date("m-d-Y",$yesterday);
		
		$start = date("Ymd000000",$lastweek);
		$end = date("Ymd235959", $yesterday);
		echo "date range: ".$start. " - ".$end."\n";
		
		// Filename
		$filename = "vendare_leads_" . date("m-d-Y",$yesterday) .".csv";
		
		// CSV file
		$fp = fopen("/tmp/". $filename,"w");
		$csv = new CSV(
				array(
					"forcequotes"=>TRUE,
					"flush"=>TRUE,
					"stream"=>$fp,
					"header"=>array("AC","VS","IP","AD","EMAIL","FIRSTNAME","LASTNAME","ADDRESS1","CITY","STATE","ZIPCODE","PHONE","EXTERNAL_ID")
				)
			);
		
		
		
		
	// Code
		$query = "
		SELECT vendare_ac,segmentcode,ip_address,email,first,last,address,city,state,zip,phone,id  
		FROM `datran`
		left outer join datran_company_xref on ref_datran = datran.id
		left outer join datran_groups on datran.datran_group=datran_groups.gid
		where date_create > $start and date_create < $end
		and ref_company = 2;
			";	
		$recs = array();
		echo "SQL: ".$query."\n";

		$rs = $sql->Query("oledirect2",$query);
		
		Error_2::Error_Test($rs, TRUE);
				
		if ( $sql->Row_Count($rs) == 0 )
		{
			echo "No Records - dumping out";
			continue;
		}
		$field_list = array("vendare_ac","segmentcode","ip_address","email","first","last","address","city","state","zip","phone","id");			
		//echo "Field List: ";var_dump($field_list);
		while ($obj=$sql->Fetch_Object_Row($rs))
		{
			$recs[] = (array)$obj;
			//echo "Rec: <pre>";var_dump($obj);
			//$csv->recordFromArray(array($obj));
			$total_count++;
			echo " Knt: ".$total_count;
		}
		echo "\nRecs created: ".$total_count."\n";
		foreach($recs as $rec)
		{
			$csv->recordFromArray($rec,$field_list);
		}
		
		$count = count($recs);
		$mailbody_text = "There were {$total_count} new fundeds for vendare for {$lastweek_f}";
		
		

		$csv_buf = $csv->_buf;
		$csv->flush();
		fclose($fp);
		
		
		print "\n";
		
		
	// Build Email Message
		$recipients=array();
		
		// build msg		
		$message = new StdClass ();
		$message->text = $mailbody_text;

		// build header		
		$header = new StdClass ();
		$header->smtp_server = $email_smtp_server;
		$header->port = 25;
		$header->url = "sellingsource.com";
		$header->subject = "Vendare Leads for $lasteweek_f";
		$header->sender_name = EMAIL_FROM_NAME;
		$header->sender_address = EMAIL_FROM;
		
		// Build Email Recipient(s)
	
			// Build the primary recipient
			$recipient1 = new StdClass ();
			$recipient1->type = "To";
			$recipient1->name = "Programmer";
			$recipient1->address = DEBUG_EMAIL;
			
			$recipients[] = $recipient1;
			
			if ( !DEBUGMODE )
			{
				$recipient = new StdClass();
				$recipient->type = "To";
				$recipient->name = EMAIL_TO_NAME;
				$recipient->address = EMAIL_TO;
				$recipients[] = $recipient;
				
				//$recipient = new StdClass();
				//$recipient->type = "To";
				//$recipient->name = "syoakum@41cash.com";
				//$recipient->address = "syoakum@41cash.com";
				//$recipients[] = $recipient;
			}
			$attachment = new StdClass ();
			$attachment->name = $filename;
			$csvbuf = file_get_contents("/tmp/".$filename);
			$attachment->content = base64_encode($csvbuf);
			$attachment->content_type = "text/x-csv";
			$attachment->content_length = strlen ($csvbuf);
			$attachment->encoded = "TRUE";	
			
			
			
			
				
		// Send Email via SOAP
		
			// Create the Mail Object and Send the Mail	
			include_once("prpc/client.php");
			$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
				
			// Key Line - Create the mailing (Name of mailing, headers, scheduled date, scheduled time) DO NOT USE SCHEDULING!!!
			$mailing_id = $mail->CreateMailing ("vendare_leads", $header, NULL, NULL);
		
			// Key Line - Add the package to the mailing (mailing_id, array of recipients, message, array of attachments)
			$package_id =$mail->AddPackage ($mailing_id, $recipients, $message, array ($attachment));
			
			// Key Line - Tell the server to process the mailing (send all emails)
			$result = $mail->SendMail ($mailing_id);
		
			// Debug Code - Use if you want to see the soap stuff
			// print_r ($mail->__get_wire ());
			echo " ... Mailing Id: ".$mailing_id."\n";
			echo " ... Result: ".$result."\n";
		
		
?>
