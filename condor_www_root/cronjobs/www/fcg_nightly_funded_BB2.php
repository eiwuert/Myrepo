<?php

	/***
	
	
		fcg_nightly_funded.php
		--
		Runs nightly, and sends a CSV to people who need to know.
		
	
	
	***/
	
	
	
	// Includes/Defines
	
		
		require_once('mysql.3.php');
		require_once('db2.1.php');
		require_once('debug.1.php');
		require_once('error.2.php');
		require_once('csv.1.php');
		
		// Emailing information
			define('EMAIL_FROM_NAME', 'John Hawkins');
			define('EMAIL_FROM', 'john.hawkins@thesellingsource.com');
			define('EMAIL_TO_NAME', 'Crystal');
			define('EMAIL_TO', 'crystal@fc500.com');
			
		// Site Information
			define('SITE_LICENSE_KEY', '6f906a9b36e5b77b55a3f240213ecf7b');
		
		// Debug Settings
			// setting this to true will disable mail to EMAIL_TO
			define('DEBUGMODE', FALSE);
			define('DEBUG_EMAIL', 'john.hargrove@thesellingsource.com');
	
	// Data
		// db connection
		$sql = new MySQL_3();
		Error_2::Error_Test(
			$sql->connect(NULL, "selsds001", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__))
		, TRUE);
	
		// time frame
		$yesterday = strtotime("-1 day");
		$yesterday_f = date("m-d-Y",$yesterday);
		
		$start = date("Y-m-d-00.00.00",$yesterday);
		$end = date("Y-m-d-23.59.59", $yesterday);
		
		// Filename
		$filename = "fcg_funded_" . date("m-d-Y",$yesterday) .".csv";
		
		// CSV file
		$fp = fopen("/tmp/". $filename,"w");
		$csv = new CSV(
				array(
					"forcequotes"=>TRUE,
					"flush"=>FALSE,
					"stream"=>$fp,
					"header"=>array("FIRST_NAME","LAST_NAME","ADDRESS","CITY","STATE","ZIP","PHONE","EMAIL","FUNDED_BY","APPID","FUNDED_DATE")
				)
			);
		
		
		
		
	// Code
	$query = "
	select
		rtrim(c.name_first) first_name,
		rtrim(c.name_last) last_name,
		a.street address_1,
		a.city city,
		st.name state,
		a.zip zip,	
		p1.phone_number home_phone,
		e.email_address email,
		os.name url,
		transaction.transaction_id trans_id,
		stat_info.session_id
	from transaction
	join phone p1 on (transaction.active_home_phone_id=p1.phone_id)
	join customer c on (transaction.customer_id=c.customer_id)
	join email e on (transaction.active_email_id=e.email_id)
	join address a on (transaction.active_address_id=a.address_id)
	join state st on (a.state_id=st.state_id)
	join originating_source os on (transaction.originating_source_id=os.originating_source_id)
	join stat_info on (transaction.transaction_id=stat_info.transaction_id)
	where
		transaction.date_created between '$start' and '$end'
	
		";	
	//and os.name='fastcashandgas.com'
	$recs = array();
	
		foreach ( array("ucl","pcl","ufc","d1","ca")  as $cp )
		{
			
			$db2 = new Db2_1('olp',"web_$cp","{$cp}_web");
			error_2::Error_Test ($db2->Connect (), TRUE);		
				
				
			$result = $db2->Execute($query);
			error_2::Error_Test ($result, TRUE);
			
			
			print "\n{$cp}.result: " . $result->Num_Rows();
			
			$count = 0;
			while ( $obj = $result->Fetch_Object() )
			{
				
				$query2 = "
					select
						count(*) count
					from transaction_history th
					join transaction_sub_status tss ON (th.transaction_sub_status_id=tss.transaction_sub_status_id)
					where transaction_id={$obj->TRANS_ID}
					and tss.name in ('ACTIVE')";
				
				$result2 = $db2->Execute($query2);
				error_2::Error_Test($result2,TRUE);
				
				$o = $result2->Fetch_Object();
							
				if ($o->COUNT == 0)
					continue;
				
				
				$rs = $sql->Query("olp_bb_visitor",
						"SELECT
							campaign_info.url
						FROM application
						JOIN campaign_info ON (campaign_info.application_id=application.application_id)						
						WHERE application.session_id='{$obj->SESSION_ID}'
						AND url IN('fastcashandgas.com','fastcashandfreegas.com')
						",Debug_1::Trace_Code(__FILE__,__LINE__));
		
				Error_2::Error_Test($rs, TRUE);
				
				if ( $sql->Row_Count($rs) == 0 )
				{
					continue;
				}
				
				list($url) = $sql->Fetch_Row($rs);
						
				print "\n" . $obj->URL ." -> ".$obj->SESSION_ID . " -> " . $url;
				
				$count++;
				
				// take these out so recordFromArray doesnt see them
				unset($obj->SESSION_ID);

				$recs[] = (array)$obj;
				
			}
			$total_count += $count;
		}		
		
		foreach($recs as $rec)
		{
			$csv->recordFromArray($rec);
		}
		
		$count = count($recs);
		$mailbody_text = "There were {$total_count} new fundeds for fastcashandgas.com for {$yesterday_f}";
		
		

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
		$header->subject = "FastCashAndGas.com Fundeds for $yesterday_f";
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
				
				$recipient = new StdClass();
				$recipient->type = "To";
				$recipient->name = "syoakum@41cash.com";
				$recipient->address = "syoakum@41cash.com";
				$recipients[] = $recipient;
			}
			$attachment = new StdClass ();
			$attachment->name = $filename;
			$attachment->content = base64_encode($csv_buf);
			$attachment->content_type = "text/x-csv";
			$attachment->content_length = strlen ($csv_buf);
			$attachment->encoded = "TRUE";	
			
			
			
			
				
		// Send Email via SOAP
		
			// Create the Mail Object and Send the Mail	
			include_once("prpc/client.php");
			$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
				
			// Key Line - Create the mailing (Name of mailing, headers, scheduled date, scheduled time) DO NOT USE SCHEDULING!!!
			$mailing_id = $mail->CreateMailing ("fcg_funded", $header, NULL, NULL);
		
			// Key Line - Add the package to the mailing (mailing_id, array of recipients, message, array of attachments)
			$package_id =$mail->AddPackage ($mailing_id, $recipients, $message, array ($attachment));
			
			// Key Line - Tell the server to process the mailing (send all emails)
			$result = $mail->SendMail ($mailing_id);
		
			// Debug Code - Use if you want to see the soap stuff
			// print_r ($mail->__get_wire ());
			echo " ... Mailing Id: ".$mailing_id."\n";
			echo " ... Result: ".$result."\n";
		
		
?>