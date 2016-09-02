#!/usr/local/bin/php
<?php
// *************************************************
// Version 1.0.0
// NMS WEEKLY REPORT :: APPS & PREQUALS
// By John Hargrove
// 10/26/2004 - SellingSource.com
// Descended from NMS MONTHLY REPORT
// *************************************************
// *************************************************

// *************************************************
// DEFINE INCLUDES/REQUIRES 
// *************************************************
	require_once ("/virtualhosts/lib/mysql.3.php");
	
	// David Bryant
	function convert_timestamp ($timestamp, $adjust="") {
		$timestring = substr($timestamp,0,8)." ".
				substr($timestamp,8,2).":".
				substr($timestamp,10,2).":".
				substr($timestamp,12,2);
		return strtotime($timestring." $adjust");
	}
		
	
// *************************************************
// DEFINE CONSTANTS & VARIABLES
// *************************************************
// One should only need to adjust these Vars to customize this script.
	
			
	
	//  Kill The Timeout
		set_time_limit(0);
		
	// CONSTANTS
		define ("ROOT_PATH", realpath ("./")."/");			// Determine root path
		$it_is_live = TRUE;					      			// Sets server environment

	// CONTROL STRUCTURES
		$report = "prequals";								// Sets initial report type
		$running = TRUE;									// Initializes reporting process

	// Determine Environment and Set Vars accordingly
		switch ($it_is_live) {
			case TRUE:
			// VARS - Database Connection - PRODUCTION
				$host_type = "BOTH";
				$host_read = "selsds001";
				$host_login = "sellingsource";
				$host_password = "password";
				$host_database = "ucl_visitor";
				$TEST_INDICATOR = "";	
				
			// Email Recipient(s) - PRODUCTION
				$email_r_type_1 = "to";
				$email_r_name_1 = "Administrator";
				$email_r_address_1 = "admin@dcei.com";		
				/*			
				$email_r_type_2 = "cc";
				$email_r_name_2 = "John Hawkins";
				$email_r_address_2 = "john.hawkins@thesellingsource.com";	
				*/
				$email_r_type_2 = "cc";
				$email_r_name_2 = "Jennifer Quade";
				$email_r_address_2 = "jennifer.quade@thesellingsource.com";	
				
				$email_r_type_3 = "cc";
				$email_r_name_3 = "Celeste Christman";
				$email_r_address_3 = "celeste.christman@thesellingsource.com";				
				/*
				$email_r_type_3 = "bcc";
				$email_r_name_3 = "John Hargrove";
				$email_r_address_3 = "john.hargrove@thesellingsource.com";					
				*/
				// Uncomment and define vars as needed to add additional recipients
				/*
				$email_r_type_4 = "to";
				$email_r_name_4 = "";
				$email_r_address_4 = "";	
				*/			
			break;
			
			default:
			// VARS - Database Connection - TEST
				$host_type = "BOTH";
				$host_read = "ds001.ibm.tss";
				$host_login = "sellingsource";
				$host_password = "password";
				$host_database = "ucl_visitor";	
				$TEST_INDICATOR = " :: TESTING";	

			// Email Recipient(s) - TEST
				$email_r_type_1 = "to";
				$email_r_name_1 = "David Bryant";
				$email_r_address_1 = "david.bryant@thesellingsource.com";	

				$email_r_type_2 = "cc";
				$email_r_name_2 = "John Hawkins";
				$email_r_address_2 = "john.hawkins@thesellingsource.com";	

				$email_r_type_3 = "bcc";
				$email_r_name_3 = "Todd Huish";
				$email_r_address_3 = "todd.huish@thesellingsource.com";					
				break;		
		}
		
		// Initialize Query Object
			$sql = new MySQL_3 ();
		
		// Open DB Connection
			$link_id = $sql->Connect ($host_type, $host_read, $host_login, $host_password, Debug_1::Trace_Code (__FILE__, __LINE__));
			
	// VARS - Relevant Dates
	
		// Generates the appropriate dates for defining the query range.
			$months_back = 1;																					// Sets how many months back to pull the extract
			$currentdate = date("Ymd");																		// Sets current date for display
			$daterangeopen = strtotime("-14 days");
			$daterangeclose = strtotime("-7 days");
			$date_open = (date('D F j, Y',$daterangeopen));														// Resets first date in extract for display
			$date_close = (date('D F j, Y',$daterangeclose));													// Resets first date in extract for display 
			$extractperiod_txt = (date('m/d/Y',$daterangeopen) ." -> ".date('m/d/Y',$daterangeclose));
			$extractperiod_num = (date('mdY',$daterangeopen)."-".date('mdY',$daterangeclose));
				
// *************************************************
// END DEFINE CONSTANTS & VARIABLES				 
// *************************************************
							 
// *************************************************
// BUILD EMAIL REPORT AND SEND
// *************************************************
// NOTE: Unless adding Recipients or testing, no
//       edits should be required below this line.
//       Then, only uncommenting is required.
// *************************************************

	echo "\r\n\r\nDotCom Endeavors\r\nMonthly Order Report Generator\r\n\r\n";
	echo "Running ...\r\n";
	echo "Source DB: ".$host_read."\r\n";
	echo "Current Date: ".$currentdate."\r\n";
	echo "Date Open: ".$date_open."\r\n";
	echo "Date Close: ".$date_close."\r\n";
	
// Run Querys and Write Report Files as CSV
// *************************************************
	WHILE ($running == TRUE) {
#
		SWITCH ($report){
			
			// Pulls All Prequal Applications
			case "prequals":
					$query = "
						SELECT 
							campaign_info.application_id as id,
							personal.email as email_address, 
							CONCAT_WS(' ',personal.first_name,personal.last_name) as full_name,
							campaign_info.promo_sub_code,
							campaign_info.url,
							campaign_info.modified_date as date
						FROM 
							campaign_info, 
							personal 
						WHERE
							campaign_info.modified_date
							BETWEEN '".date('Y-m-d',$daterangeopen)."' AND '".date('Y-m-d',$daterangeclose)."'
							AND campaign_info.application_id=personal.application_id
							AND campaign_info.url 
								IN ('maxoutloan.com', 'udrivehome.com','gocredix.com','speedycashadvance.com','autorepairloans.com','ineedbeermoney.com')
							ORDER BY date,campaign_info.url
					";
							
					// Build Attachment
						$attachment1 = Build_CSV ($query, $sql,"olp_bb_partial", $report, $currentdate, $extractperiod_num);
					// Reset Vars For Next Loop
						$report = "complete";
						$query = "";			
					break;
				
				// Pulls All Completed Applications
				case 'complete':
					$query = "
						SELECT 
							application.application_id as id, 
							application.created_date as date, 
							campaign_info.url,
							CONCAT_WS(' ',personal.first_name,personal.last_name) as full_name,
							personal.email as email_address, 
							campaign_info.promo_sub_code,
							bbp.date_created
						FROM
							application
						JOIN 
							campaign_info on application.application_id=campaign_info.application_id 
						JOIN 
							personal on application.application_id=personal.application_id 
						JOIN
							blackbox_post bbp on application.application_id=bbp.application_id
						WHERE
							application.created_date	BETWEEN '".date('Y-m-d',$daterangeopen)."' AND '".date('Y-m-d',$daterangeclose)."'
							AND campaign_info.url in ('maxoutloan.com', 'udrivehome.com','gocredix.com','speedycashadvance.com','autorepairloans.com','ineedbeermoney.com') 
							AND bbp.winner IN ('ca','ucl','pcl','ufc','d1')
							AND bbp.success = 'TRUE'
							GROUP BY application.application_id 
							ORDER BY campaign_info.created_date, campaign_info.url
						";
					
					// Build Attachment
						$attachment2 = Build_CSV ($query, $sql, "olp_bb_visitor", $report, $currentdate, $extractperiod_num);
					// Reset Vars For Next Loop
						$report = "stop";
						$query = "";
					break;

			// KILL THE PROCESS			
			default:
				echo "\r\n\r\nAll CSV files have been generated.\r\n\r\n";
				$running = FALSE;
				break;
		}
	}
	// end while
	
	
	
// *************************************************		
// BUILD CSV FUNCTION
// *************************************************	
	function Build_CSV ($query, $sql, $host_database, $report, $currentdate, $extractperiod_num)
	{
		// Run The Query	
			$query_output = $sql->Query ($host_database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		// Obtain Row Count	
			//$app_count = $sql->Row_Count($query_output);
			$app_count=0;
			$removed_count=0;
			
		// Strip Dupes From Result Set [By Todd H.]				
			while ($data = $sql->Fetch_Object_Row($query_output))
			{

				if ($report == "prequals")
				{
					$attachment_raw->{"ap".$data->id} = date('m/d/Y', convert_timestamp($data->date)).", ".$data->url.", ".strtolower($data->email_address).", ".ucwords(strtolower(str_replace(",","",$data->full_name))).", ".$data->promo_sub_code."\r\n";
					$app_count++;
				}	
				else
				{
					// added 11-04-2004 By John Hargrove
					// Remove leads that are not first tier from this list.. 
					if( $data->bb_ucl || $data->bb_pcl || $data->bb_ca || $data->bb_ufc || $data->bb_d1 )
					{						
						$attachment_raw->{"ap".$data->id} = date('m/d/Y', convert_timestamp($data->date)).", ".$data->url.", ".strtolower($data->email_address).", ".ucwords(strtolower(str_replace(",", ".", $data->full_name))).", ".$data->promo_sub_code."\r\n";
						$app_count++;
					}
					else
					{
						$removed_count++;
					}
				}
			}
		// end while

		 	echo "\r\n";
			echo "DCE had ".$app_count." ".$report." orders.";
			echo "\r\n$removed_count were rejected.";
			echo "\r\n";
			
					
		// Write Report DataSet				
			$attachment_new = "APP DATE, DOMAIN NAME, EMAIL, FULL NAME, PROMO_SUB_CODE\r\n"; 			// SETS HEADERS FOR THE CSV FILE COLUMNS
			foreach ($attachment_raw as $data_rows)
			{
				$attachment_new .= $data_rows;															// WRITES CSV FILE TO MEMORY
			}
			
			// take attachment data and write it to a file
			$fp=fopen("/tmp/".$extractperiod_num."_DCE_".$report."_apps.csv","w");
			fwrite($fp,$attachment_new);
			fclose($fp);	
			
		// Build Attachment [Only if there is data available]
			if ($app_count != 0) 	
			{		
				$attachment_csv = new StdClass ();
				$attachment_csv->name = $extractperiod_num."_DCE_".$report."_apps.csv";
				$attachment_csv->content = base64_encode($attachment_new);
				$attachment_csv->content_type = "text/x-csv";
				$attachment_csv->content_length = strlen ($attachment_new);
				$attachment_csv->encoded = "TRUE";	
			}				
			
		// Return Values					
			return $attachment_csv;			
	}
	
// BUILD EMAIL REPORT AND SEND
// *************************************************	
	// VARS - Email Generation		
		// Email Headers
			// $email_smtp_server = "mail.sellingsource.com";
			$email_port = 25;
			$email_url = "sellingsource.com";
			$email_s_name = "John Hawkins";
			$email_s_address = "johnh@sellingsource.com";	
			$email_subject = "DotCom Endeavors :: Weekly Order Extracts :: [".$extractperiod_txt.$TEST_INDICATOR."]";
				
		// Email Content	
			$mailbody_text = "Good morning, \r\n\r\n
							  Attached, you will find the following files:\n\t"
							  .$extractperiod_num."_DCE_complete_apps.csv\n\t"
							  .$extractperiod_num."_DCE_prequal_apps.csv\n\n
							  These files are database extracts which represent the 
							  Prequal and Completed Applications placed with NMS during "
							  .$extractperiod_txt.". \r\n\r\n;
							  If you have any questions about this report, please contact
							  me at the email address or phone number listed below.  \r\n \r\n; 
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
							  Attached, you will find the following files:<ul><li>"
							  .$extractperiod_num."_DCE_complete_apps.csv</li><li>"
							  .$extractperiod_num."_DCE_prequal_apps.csv</li></ul>
							  These files are database extracts which represent the 
							  Prequal and Completed Applications placed with NMS during "
							  .$extractperiod_txt.". 
							  <br><br>
							  If you have any questions about this report, please contact
							  me at the email address or phone number listed below.  
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
		$recipient1->type = $email_r_type_1;
		$recipient1->name = $email_r_name_1;
		$recipient1->address = $email_r_address_1;

		// Build the second recipient
		$recipient2 = new StdClass ();
		$recipient2->type = $email_r_type_2;
		$recipient2->name = $email_r_name_2;
		$recipient2->address = $email_r_address_2;

		// Build the third recipient
		$recipient3 = new StdClass ();
		$recipient3->type = $email_r_type_3;
		$recipient3->name = $email_r_name_3;
		$recipient3->address = $email_r_address_3;

		// NOTE: By default, Recipients 3-4 are empty
		/*					
		// Build the fourth recipient
		$recipient4 = new StdClass ();
		$recipient4->type = $email_r_type_4;
		$recipient4->name = $email_r_name_4;
		$recipient4->address = $email_r_address_4;
		*/
	
	// Build Recipient List
	$recipients = array ($recipient1, $recipient2, $recipient3, $recipient4);

	// Build Email Message
	$message = new StdClass ();
	$message->text = $mailbody_text;
	$message->html = $mailbody_html;
	
	// Send Email via SOAP
	
		// Create the Mail Object and Send the Mail	
		include_once("prpc/client.php");
		$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
			
		// Benchmark
		list ($sm, $ss) = explode (" ", microtime ());
	
		// Key Line - Create the mailing (Name of mailing, headers, scheduled date, scheduled time) DO NOT USE SCHEDULING!!!
		$mailing_id = $mail->CreateMailing ("survey_report", $header, NULL, NULL);
	
		// Key Line - Add the package to the mailing (mailing_id, array of recipients, message, array of attachments)
		$package_id =$mail->AddPackage ($mailing_id, $recipients, $message, array ($attachment1,$attachment2));
		
		/*
		// LOOP to allow multiple recipients
		for ($i=0; $i<10; $i++)
		{
		$package_id =$mail->AddPackage ($mailing_id, array ($recipient1), $message, array ());
		echo "Package Id: ".$package_id."\n";
		}
		*/
	
		// Key Line - Tell the server to process the mailing (send all emails)
		 $result = $mail->SendMail ($mailing_id);
	
		// Debug Code - Use if you want to see the soap stuff
		// print_r ($mail->__get_wire ());
		echo " ... Mailing Id: ".$mailing_id."\n";
		echo " ... Result: ".$result."\n";
		echo " ... Recipients: \n";
		//Debug_1::Raw_Dump ($recipients);
		
		list ($em, $es) = explode (" ", microtime ());
	
		echo "\n";
		echo " ... Process Time: ".(((float)$es + (float)$em) - ((float)$ss + (float)$sm));
		echo "\n";
		echo "Process Completed.";
		echo "\n\n";
?>