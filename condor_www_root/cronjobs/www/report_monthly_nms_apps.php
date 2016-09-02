#!/usr/local/bin/php
<?php
// *************************************************
// Version 1.0.2
// NMS MONTHLY REPORT :: APPS & PREQUALS
// By Doug Daulton and Todd Huish
// 01/12/2004 - SellingSource.com
// *************************************************
// Set on crontab to the 1st of each month @ 2AM
// *************************************************

// *************************************************
// DEFINE INCLUDES/REQUIRES 
// *************************************************
	require_once ("/virtualhosts/lib/mysql.3.php");
	require_once('/virtualhosts/bfw.1.edataserver.com/include/code/crypt_config.php');
	require_once('/virtualhosts/bfw.1.edataserver.com/include/code/crypt.singleton.class.php');


	
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
		
		$crypt_config 	= Crypt_Config::Get_Config('LIVE');
		$cryptSingleton 	= Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);	
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
							
				$email_r_type_2 = "cc";
				$email_r_name_2 = "John Hawkins";
				$email_r_address_2 = "john.hawkins@thesellingsource.com";	
	
				$email_r_type_3 = "bcc";
				$email_r_name_3 = "David Bryant";
				$email_r_address_3 = "david.bryant@thesellingsource.com";					

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
			$currentdate =  date("Ymd");																		// Sets current date for display
			$daterangeopen = mktime (0,0,0,date("m")-$months_back, 1, date("Y"));								// Sets first date in extract for query
			$daterangeclose = mktime (0,0,0,date("m")-$months_back, date("t",$daterangeopen), date("Y")); 		// Sets last date in extract for query
			$extractperiod_txt = (date('F Y',$daterangeopen));													// Sets extract period for display
			$extractperiod_num = (date('Ym',$daterangeopen));													// Sets extract period for screen echo
			$date_open = (date('D F j, Y',$daterangeopen));														// Resets first date in extract for display
			$date_close = (date('D F j, Y',$daterangeclose));													// Resets first date in extract for display 
				
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

	echo "<br><br>DotCom Endeavors<br />Monthly Order Report Generator<br><br>";
	echo "Running ...<br>";
	echo "Source DB: ".$host_read."<br>";
	echo "Current Date: ".$currentdate."<br>";
	echo "Extract Period (num): ".$extractperiod_num."<br>";
	echo "Extract Period (txt): ".$extractperiod_txt."<br>";
	echo "Date Open: ".$date_open."<br>";
	echo "Date Close: ".$date_close."<br>";
	
// Run Querys and Write Report Files as CSV i need to set a date range on a query using two variables ... here is what I have so far ...
// *************************************************
	WHILE ($running == TRUE) {
				
		SWITCH ($report){
			
			// Pulls All Prequal Applications
			case "prequals":
					$query = "
						SELECT 
							applications.unique_id, 
							applications.application_id as id, 
							base.email as email_address, 
							base.full_name as full_name, 
							applications.created_date as date, 
							applications.type as type, 
							promo_sub_code, 
							url 
						FROM 
							applications, 
							site_info, 
							base 
						WHERE 
							applications.created_date 
									BETWEEN '".date('Y-m-d', $daterangeopen)."' 
									AND '".date('Y-m-d', $daterangeclose)."' 
							AND applications.application_id=site_info.application_id 
							AND applications.unique_id=base.unique_id 
							AND applications.type IN('VISITOR', 'QUALIFIED')";
						
					if (!$_GET['all_results']) { 
					$query .= " 
							AND url 
								IN ('maxoutloan.com', 'udrivehome.com','gocredix.com','speedycashadvance.com','autorepairloans.com','ineedbeermoney.com')";
					}
					$query .=" 
							ORDER BY date, url";					
							
					// Build Attachment
						$attachment1 = Build_CSV ($query, $sql, $host_database, $report, $currentdate, $extractperiod_num);
						 		
					// Reset Vars For Next Loop
						$report = "complete";
						$query = "";			
					break;
				
				// Pulls All Completed Applications
				case 'complete':
					$query = "
						SELECT 
							applications.application_id as id, 
							DATE_FORMAT(applications.created_date, '%m/%d/%Y') as date, url, 
							first_name as first, 
							last_name as last, 
							email as email_address, 
							promo_sub_code from applications 
						JOIN 
							site_info on applications.application_id = site_info.application_id 
						JOIN 
							personal_encrypted on applications.application_id = personal_encrypted.application_id 
							WHERE 
								url in ('maxoutloan.com', 'udrivehome.com','gocredix.com','speedycashadvance.com','autorepairloans.com','ineedbeermoney.com') 
								AND applications.created_date 
									BETWEEN '".date('Y-m-d', $daterangeopen)."' 
									AND '".date('Y-m-d', $daterangeclose)."' 
								AND type in ('PROSPECT', 'APPLICANT', 'CUSTOMER', 'DOA') ";
					
					if (!$_GET['all_results']) {
					$query .= " 
								AND url 
									IN ('maxoutloan.com', 'udrivehome.com','gocredix.com','speedycashadvance.com','autorepairloans.com','ineedbeermoney.com') ";
					}
					$query .=" 
								GROUP BY applications.application_id 
								ORDER BY date, url";
					
					// Build Attachment
						$attachment2 = Build_CSV ($query, $sql, $host_database, $report, $currentdate, $extractperiod_num);
						
					// Reset Vars For Next Loop
						$report = "stop";
						$query = "";
					break;

			// KILL THE PROCESS			
			default:
				echo "<br><br>All CSV files have been generated.<br><br>";
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
			$app_count = $sql->Row_Count($query_output);
		 	echo "<br />";
			echo "DCE had ".$app_count." ".$report." orders in ".$extractperiod_num.".";
			echo "<br />";
			
		// Strip Dupes From Result Set [By Todd H.]				
			while ($data = $sql->Fetch_Object_Row($query_output))
			{
				if ($report == "prequals")
				{
					$attachment_raw->{"ap".$data->id} = date('m/d/Y', strtotime($data->date)).", ".$data->url.", ".strtolower($data->email_address).", ".ucwords(strtolower(str_replace(",","",$data->full_name))).", ".$data->promo_sub_code."\r\n";
				}

				else
				{
					$attachment_raw->{"ap".$data->id} = $data->date.", ".$data->url.", ".strtolower($data->email_address).", ".ucwords(strtolower(str_replace(",", ".", $data->first)))." ".ucwords(strtolower(str_replace(",", ".", $data->last))).", ".$data->promo_sub_code."\r\n";
				}
			}
		// end while
		
		// Write Report DataSet				
			$attachment_new = "APP DATE, DOMAIN NAME, EMAIL, FULL NAME, PROMO_SUB_CODE\r\n"; 			// SETS HEADERS FOR THE CSV FILE COLUMNS
			foreach ($attachment_raw as $data_rows)
			{
				$attachment_new .= $data_rows;															// WRITES CSV FILE TO MEMORY
			}
			
		// Build Attachment [Only if there is data available]
			if ($app_count != 0) 	
			{		
				$attachment_csv = new StdClass ();
				$attachment_csv->name = $extractperiod_num."_DCE_".$report."_apps.csv";
				$attachment_csv->content = base64_encode ($attachment_new);
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
			$email_subject = "DotCom Endeavors :: Monthly Order Extracts :: [".$extractperiod_txt.$TEST_INDICATOR."]";
				
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
		Debug_1::Raw_Dump ($recipients);
		
		list ($em, $es) = explode (" ", microtime ());
	
		echo "\n";
		echo " ... Process Time: ".(((float)$es + (float)$em) - ((float)$ss + (float)$sm));
		echo "\n";
		echo "Process Completed.";
		echo "\n\n";
?>
