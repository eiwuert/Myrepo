#!/usr/local/bin/php
<?php

// *************************************************
// Version 1.0.0
// SURVEY SITES DAILY REPORTING (CRON)
// By Doug Daulton (with help from Paul Strange)
// 07/15/03 - SellingSource.com
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
	
	// VARS - Database Connection
		$host_type = "BOTH";
		$host_read = "localhost";
		$host_login = "root";
		$host_password = "";
		$host_database = "survey_sites";	
	
	// VARS - Query String
	
		// This query grabs the required data and writes it to a 
		// CSV (comma seperated values) file to be e-mailed
		$currentdate =  date("mdy");
		$query = "SELECT v.site_id, v.name, v.address, v.city, v.state, v.zip 
					FROM visitors v, surveys s 
					WHERE v.visitor_id = s.visitor_id and s.survey_date = curdate() - 1";
	
	// VARS - Email Generation
		
		// Email Headers
			//$email_smtp_server = "mail.sellingsource.com";
			$email_port = 25;
			$email_url = "sellingsource.com";
			$email_s_name = "John Hawkins";
			$email_s_address = "johnh@sellingsource.com";	
			$email_subject = "SellingSource.com Daily Survey Report :: ".$currentdate;
				
		// Email Recipient(s)
			$email_r_type_1 = "to";
			$email_r_name_1 = "Doug Daulton";
			$email_r_address_1 = "ddaulton@ursastudios.com";	
			
			// Uncomment and define vars as needed to add additional recipients
			/*
			$email_r_type_2 = "to";
			$email_r_name_2 = "";
			$email_r_address_2 = "";	
			*/
			
			/*
			$email_r_type_3 = "to";
			$email_r_name_3 = "";
			$email_r_address_3 = "";
			*/
			
			/*
			$email_r_type_4 = "to";
			$email_r_name_4 = "";
			$email_r_address_4 = "";
			*/

		// Email Content
			$mailbody_url_1 = "survey.cashloansurvey.com";
			$mailbody_url_2 = "survey.ezcashsurvey.com";
			$mailbody_url_3 = "survey.yourcashsurvey.com";
			$mailbody_url_4 = "survey.yourloansurvey.com";	
			
			$mailbody_text = "Good morning, \r\n\r\n;
							  Attached, you will find ".$currentdate."_surveyreport.csv.
							  This file is the daily report of surveys from received from the 
							  following websites: \r\n \r\n;
							 	* http://".$mailbody_url_1." \r\n;
								* http://".$mailbody_url_2." \r\n;
								* http://".$mailbody_url_3." \r\n;
								* http://".$mailbody_url_4." \r\n;
							  If you have any questions about this report, please contact
							  me at the address or phone number listed below.  \r\n \r\n; 
							  Regards, \r\n \r\n;
							  John Hawkins \r\n;
							  Director of Technical Services \r\n;
							  SellingSource.com \r\n;
							  e: johnh@sellingsource.com \r\n;
							  p: 800.391.1178 \r\n;
							  o: 702407.0707
							 ";
							  
			$mailbody_html = "<html><body>
							  Good morning, <br /><br />
							  Attached, you will find ".$currentdate."_surveyreport.csv.
							  This file is the daily report of surveys from received from the 
							  following websites: <ul>
							 	<li> <a href='http://".$mailbody_url_1."'>".$mailbody_url_1."</a> </li>
								<li> <a href='http://".$mailbody_url_2."'>".$mailbody_url_2."</a> </li>
								<li> <a href='http://".$mailbody_url_3."'>".$mailbody_url_3."</a> </li>
								<li> <a href='http://".$mailbody_url_4."'>".$mailbody_url_4."</a> </li></ul>
							  If you have any questions about this report, please contact
							  me at the address or phone number listed below.  \r\n \r\n; 
							  Regards, <br /><br />
							  John Hawkins <br />
							  Director of Technical Services <br />
							  <a href='http://sellingsource.com'>SellingSource.com</a> <br />
							  e: johnh@sellingsource.com <br />
							  p: 800.391.1178 <br />
							  o: 702407.0707
							  </body></html>						 
							 ";

		// Email Attachments
			$attachment_file = $currentdate."_surveyreport.csv";
			$attachment_content_type = "text/x-csv";		 

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
	
	// Open DB Connection
		$link_id = $sql->Connect ($host_type, $host_read, $host_login, $host_password, Debug_1::Trace_Code (__FILE__, __LINE__));
		echo " ... Link ID: ".$link_id."\n";
		
		$query_output = $sql->Query ($host_database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		echo " ... Query Output: "; Debug_1::Raw_Dump ($query_output);//."\n";
		
		while (FALSE !== ($temp = $sql->Fetch_Object_Row ($query_output)))
		{
			$data1 = $temp->site_id.",".$temp->name.",".$temp->address.",".$temp->city.",".$temp->state.",".$temp->zip."\r\n";
			Debug_1::Raw_Dump ($temp);

	
		}
		Debug_1::Raw_Dump ($result);
		

// BUILD EMAIL REPORT AND SEND
// *************************************************

	// Build Email Header
		$header = new StdClass ();
			$header->smtp_server = $email_smtp_server;
			$header->port = $email_port;
			$header->url = $email_url;
			$header->subject = $email_subject;
			$header->sender_name = $email_s_name;
			$header->sender_address = $email_s_address;
	
	
	// Build Email Recipient(s)
		$recipient1 = new StdClass ();
			$recipient1->type = $email_r_type_1;
			$recipient1->name = $email_r_name_1;
			$recipient1->address = $email_r_address_1;
	
		// NOTE: By default, Recipients 2-4 are empty
		
		/*
		// Build the second recipient
		$recipient2 = new StdClass ();
			$recipient1->type = $email_r_type_2;
			$recipient1->name = $email_r_name_2;
			$recipient1->address = $email_r_address_2;
	
		// Build the third recipient
			$recipient1->type = $email_r_type_3;
			$recipient1->name = $email_r_name_3;
			$recipient1->address = $email_r_address_3;
	
		// Build the fourth recipient
			$recipient1->type = $email_r_type_4;
			$recipient1->name = $email_r_name_4;
			$recipient1->address = $email_r_address_4;
		*/
		
	// Build Email Message
		$message = new StdClass ();
			$message->text = $mailbody_text;
			$message->html = $mailbody_html;
/*	
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
	
		
	// Send Email via SOAP
	
		// Create the Mail Object and Send the Mail
			require_once "/virtualhosts/lib/soap_smtp_client.3.php";
			$mail = new SoapSmtpClient_3 ("soap.maildataserver.com");
	
		// Benchmark
		list ($sm, $ss) = explode (" ", microtime ());
	
		// Key Line - Create the mailing (Name of mailing, headers, scheduled date, scheduled time) DO NOT USE SCHEDULING!!!
		$mailing_id = $mail->CreateMailing ("survey_report", $header, NULL, NULL);
	
		// Debug Code - Just an fyi
		echo " ... Mailing Id: ".$mailing_id."\n";
		
		// Key Line - Add the package to the mailing (mailing_id, array of recipients, message, array of attachments)
		$package_id =$mail->AddPackage ($mailing_id, array ($recipient1), $message, array ($attachment1));
		
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
	
		echo " ... Result: ".$result."\n";
	
		list ($em, $es) = explode (" ", microtime ());
	
		echo " ... Process Time: ".(((float)$es + (float)$em) - ((float)$ss + (float)$sm));
		echo "\n";
		echo "Process Completed.";
		echo "\n\n";
		
?>
