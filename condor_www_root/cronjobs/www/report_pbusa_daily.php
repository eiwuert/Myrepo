#!/usr/local/bin/php
<?php
// *************************************************
// Version 1.1.0
// PREMIUM BENEFITS -DAILY REPORTING (CRON)
// 09/08/03 - SellingSource.com
// Doug Daulton
// *************************************************
// Set on crontab to run everyday of midnight
// *************************************************

// *************************************************
// DEFINE INCLUDES/REQUIRES 
// *************************************************
	require_once ("/virtualhosts/lib/mysql.3.php");
	
// *************************************************
// DEFINE CONSTANTS & VARIABLES
// *************************************************
// One should only need to adjust these Vars to customize this script.

	// CONSTANTS
		define ("ROOT_PATH", realpath ("./")."/");

	/*	
	// VARS - Database Connection - DEV
		$host_type = "BOTH";
		$host_read = "localhost";
		$host_login = "root";
		$host_password = "";
		$host_database = "premiumbenefitsusa_com";		
	*/
	
	// VARS - Database Connection - PRODUCTION
		$host_type = "BOTH";
		$host_read = "read1.iwaynetworks.net";
		$host_login = "sellingsource";
		$host_password = "password";	
		$host_database = "premiumbenefitsusa_com";

	// VARS - Query String	
		// This query grabs the required data and writes it to a 
		// CSV (comma seperated values) file to be e-mailed
		$currentdate =  date("mdy");

		// THESE FIELDS ARE NOT USED IN THE FORMAT PROVIDED BY BB
			// email, bank_name, bank_address, bank_city, bank_state, bank_zip,  
			// routing_number, account_number, account_type, send_info	
		
		$query = "
			SELECT  
				order_id, first_name, last_name, address, city, state, zip, hm_phone, wk_phone, submit_date 
			FROM 
				orders
			WHERE 
				submit_date = CURDATE()-1";

	// VARS - Email Generation
		
		// Email Headers
			//$email_smtp_server = "mail.sellingsource.com";
			$email_port = 25;
			$email_url = "sellingsource.com";
			$email_s_name = "SellingSource.com Reports";
			$email_s_address = "reports@sellingsource.com";				
				
		// Email Recipient(s)
			/*
			// TESTING EMAIL (NON-CLIENT)
			$email_r_type_1 = "to";
			$email_r_name_1 = "Doug Daulton";
			$email_r_address_1 = "ddaulton@ursastudios.com";		
			*/ 

			// PRODUCTION EMAIL Addresses (CLIENT)
			$email_r_type_1 = "to";
			$email_r_name_1 = "Edward Cross";
			$email_r_address_1 = "ecross@41cash.com";				
			
			$email_r_type_2 = "to";
			$email_r_name_2 = "Best Benefits";
			$email_r_address_2 = "data@bestbenefits.com";	

			$email_r_type_3 = "cc";
			$email_r_name_3 = "John Hawkins";
			$email_r_address_3 = "johnh@sellingsource.com";	
			
			$email_r_type_4 = "bcc";
			$email_r_name_4 = "Doug Daulton";
			$email_r_address_4 = "dougd@sellingsource.com";


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

	echo "\n";
	echo "Running Survey Report Generator! \n";

// Run Query and Write Report File
// *************************************************

	// Initialize Query Object
		$sql = new MySQL_3 ();
	
	 	// THESE FIELDS ARE NOT USED IN THE FORMAT PROVIDED BY BB
		// email, bank_name, bank_address, bank_city, bank_state, bank_zip,  
		// routing_number, account_number, account_type, send_info	
		
	// Open DB Connection
		$link_id = $sql->Connect ($host_type, $host_read, $host_login, $host_password, Debug_1::Trace_Code (__FILE__, __LINE__));
		$query_output = $sql->Query ($host_database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		$order_count = $sql->Row_Count ($query_output);
		$data1 = "ID #,"
				."Last Name,"
				."First Name,"
				."Address1,"
				."Address2,"
				."City,"
				."State,"
				."Zip,"
				."Phone,"
				."Group,"
				."Effective Date,"
				."Sponsor/Agent ID,"
				."Optional Data 1,"
				."Optional Data 2,"
				."Account #,"
				."Account Exp,"
				."Method of Payment,"
				."Payee Last,"
				."Payee First,"
				."Payee Address1,"
				."Payee Address2,"
				."Payee City,"
				."Payee State,"
				."Payee Zip,"
				."Comp Card,"
				."Sales Type,"
				."Original Group #,"
				."Bank #,"
				."Account Type,"
				."No Market,"
				."Filler"	
				."\r\n";
					
		while (FALSE !== ($temp = $sql->Fetch_Object_Row ($query_output)))
		{
			$data1 	.= $temp->order_id."," 		// ID #
				   	.$temp->last_name."," 		// Last Name
				  	.$temp->first_name.","		// First Name
					.$temp->address.","			// Address1
					."," 					// Address2 			(not in DB provided for standard file format)
					.$temp->city.","			// City
					.$temp->state.","			// State
					.$temp->zip.","			// Zip
					.$temp->hm_phone.","		// Phone
					."," 					// Group 			(not in DB provided for standard file format)
					."," 					// Effective Date 		(not in DB provided for standard file format)
					."," 					// Sponsor/Agent ID 		(not in DB provided for standard file format)
					.$temp->wk_phone."," 		// Optional Data 1		(Additional Phone No.)
					.$temp->submit_date.","		// Optional Data 2 		(Date Submitted)
					.$temp->account_number.","		// Account #
					."," 					// Account Exp		(not in DB provided for standard file format)
					."," 					// Method of Payment	(not in DB provided for standard file format)
					."," 					// Payee Last			(not in DB provided for standard file format)
					."," 					// Payee First			(not in DB provided for standard file format)
					."," 					// Payee Address1		(not in DB provided for standard file format)
					."," 					// Payee Address2		(not in DB provided for standard file format)
					."," 					// Payee City			(not in DB provided for standard file format)
					."," 					// Payee State			(not in DB provided for standard file format)
					."," 					// Payee Zip			(not in DB provided for standard file format)
					."," 					// Comp Card			(not in DB provided for standard file format)
					."," 					// Sales Type			(not in DB provided for standard file format)
					."," 					// Original Group #		(not in DB provided for standard file format)
					."," 					// Bank #			(not in DB provided for standard file format)
					."," 					// Account Type		(not in DB provided for standard file format)
					."," 					// No Market			(not in DB provided for standard file format)
					."," 					// Filler			(not in DB provided for standard file format)
					."\r\n";
					
		//	Debug_1::Raw_Dump ($temp);
		}
		
		// DEBUGGING DUMPS
			
			echo "<br /><br />";	 
			echo " ... Link ID: ".$link_id."<br />";
			echo " ... Query: "; 
			Debug_1::Raw_Dump ($query);
			echo "<br /><br />";
			Debug_1::Raw_Dump ($result);
			echo "<br /><br />";	
			echo " ... Query Output: <br /><br /> "; 
			Debug_1::Raw_Dump ($data1);
			echo "<br /><br />";
			echo " ... Order Count:  ".$order_count; 
			echo "<br /><br />";
			
			
// BUILD EMAIL REPORT AND SEND
// *************************************************

	// DEFINE EMAIL CONTENT BASED ON ORDER COUNT
	
		$mailbody_url_1 = "premiumbenefitsusa.com";

		SWITCH ($order_count)
		{
			// EMAIL BODY IF THERE ARE NO NEW ORDERS
			case 0 :				
				$email_subject = "premiumbenefitsusa.com :: No Orders  :: ".$currentdate;
				$mailbody_text = "Good morning, \r\n\r\n;
							  No new orders were registered in the database
			   				  for ".$mailbody_url_1." within the last 24 hours. \r\n;
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
							  Good morning, <br /><br />
							  No new orders were registered in the database
							  for <a href='http://".$mailbody_url_1."'>".$mailbody_url_1."</a> 
							  within the last 24 hours.<br /><br />
							  If you have any questions about this report, please contact
							  me at the email address or phone number listed below. <br /><br /> 
							  Regards, <br /><br />
							  John Hawkins <br />
							  Director of Technical Services <br />
							  <a href='http://sellingsource.com'>SellingSource.com</a> <br />
							  e: johnh@sellingsource.com <br />
							  p: 800.391.1178 <br />
							  o: 702.407.0707
							  </body></html>						 
							 ";	
			break;

			// EMAIL BODY IF THERE ARE RESULTS	
			default :
				$email_subject = "premiumbenefitsusa.com :: ".$order_count." New Orders :: ".$currentdate;					
				$mailbody_text = "Good morning, \r\n\r\n;
							  Attached, you will find ".$currentdate."_pbusa_weekly_report.csv.
							  This file is a database extract which represents the ".$order_count." new 
							  orders placed at ".$mailbody_url_1." within the last 24 hours. \r\n;
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
							  Good morning, <br /><br />
							  Attached, you will find <b>".$currentdate."_pbusa_weekly_report.csv</b>.
							  This file is a database extract which represents the <b>".$order_count."</b> new 
							  orders placed at <a href='http://".$mailbody_url_1."'>".$mailbody_url_1."</a> 
							  within the last 24 hours.<br /><br />
							  If you have any questions about this report, please contact
							  me at the email address or phone number listed below. <br /><br /> 
							  Regards, <br /><br />
							  John Hawkins <br />
							  Director of Technical Services <br />
							  <a href='http://sellingsource.com'>SellingSource.com</a> <br />
							  e: johnh@sellingsource.com <br />
							  p: 800.391.1178 <br />
							  o: 702.407.0707
							  </body></html>						 
							 ";				
				// Define  Email Attachments
					$attachment_file = $currentdate."_pbusa_weekly_report.csv";
					$attachment_content_type = "text/x-csv";	
			break;
		}	
			
			
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

		// NOTE: By default, Recipients 3-4 are empty
		
		// Build the third recipient
			$recipient3 = new StdClass ();
			$recipient3->type = $email_r_type_3;
			$recipient3->name = $email_r_name_3;
			$recipient3->address = $email_r_address_3;

		// Build the fourth recipient
			$recipient4 = new StdClass ();
			$recipient4->type = $email_r_type_4;
			$recipient4->name = $email_r_name_4;
			$recipient4->address = $email_r_address_4;

			
	// Build Recipient List
		$recipients = array ($recipient1, $recipient2, $recipient3, $recipient4);
		
	// Build Email Message
		$message = new StdClass ();
			$message->text = $mailbody_text;
			$message->html = $mailbody_html;

		// Only Build/Include Attachment if there is data available
		if ($order_count != 0) 	{		
			
			/* COMMENTED OUT AS IT IS NO LONGER NEEDED (OR SO I THINK)
			// Open report file to be attached to the email
			     	$fh = fopen ($attachment_file, "r");
				$data1 = fread ($fh, filesize ($attachment_file));
				fclose ($fh);	
			*/
			
			// Build Email Attachment
			$attachment1 = new StdClass ();
				$attachment1->name = $attachment_file;
				$attachment1->content = base64_encode ($data1);
				$attachment1->content_type = $attachment_content_type;
				$attachment1->content_length = strlen ($attachment1->content);
				$attachment1->encoded = "TRUE";
			}	
	
		
	// Send Email via SOAP
	
		// Create the Mail Object and Send the Mail
			require_once "/virtualhosts/lib/soap_smtp_client.3.php";
			$mail = new SoapSmtpClient_3 ("soap.maildataserver.com");
	
		// Benchmark
		list ($sm, $ss) = explode (" ", microtime ());
	
		// Key Line - Create the mailing (Name of mailing, headers, scheduled date, scheduled time) DO NOT USE SCHEDULING!!!
		$mailing_id = $mail->CreateMailing ("survey_report", $header, NULL, NULL);
	
		// Key Line - Add the package to the mailing (mailing_id, array of recipients, message, array of attachments)
		$package_id =$mail->AddPackage ($mailing_id, $recipients, $message, array ($attachment1));
		
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
			// echo " ... Result: ".$result."\n";
			// echo " ... Recipients: ";
			// Debug_1::Raw_Dump ($recipients);
			
			list ($em, $es) = explode (" ", microtime ());
		
			echo " ... Process Time: ".(((float)$es + (float)$em) - ((float)$ss + (float)$sm));
			echo "\n";
			echo "Process Completed.";
			echo "\n\n";		
?>
