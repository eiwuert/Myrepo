#!/usr/local/bin/php
<?php
// *************************************************
// Version 1.0.0
// SMARTID WEEKLY ORDER REPORT
// By Doug Daulton 
// 07/30/03 - SellingSource.com
// *************************************************
// Set on crontab to run every Thursday at midnight
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

	// Determine Environment and Set Vars accordingly
	
		switch ($env){
			case "live":
			// VARS - Database Connection - PRODUCTION
				$host_type = "BOTH";
				$host_read = "linux24.iwaynetworks.net";
				$host_login = "sellingsource";
				$host_password = "password";
				$host_database = "smartidonline";	
				
			// Email Recipient(s) - PRODUCTION
				$email_r_type_1 = "to";
				$email_r_name_1 = "Terry";
				$email_r_address_1 = "terryb@sellingsource.com";		
							
				$email_r_type_2 = "cc";
				$email_r_name_2 = "John Hawkins";
				$email_r_address_2 = "johnh@sellingsource.com";	
	
				$email_r_type_3 = "bcc";
				$email_r_name_3 = "Doug Daulton";
				$email_r_address_3 = "doug@ursastudios.com";					

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
				$host_read = "dev01.tss";
				$host_login = "developer";
				$host_password = "password";
				$host_database = "smartidonline";	

			// Email Recipient(s) - TEST
				$email_r_type_1 = "to";
				$email_r_name_1 = "Doug Daulton";
				$email_r_address_1 = "ddaulton@ursastudios.com";	

				$email_r_type_2 = "cc";
				$email_r_name_2 = "John Hawkins";
				$email_r_address_2 = "johnh@sellingsource.com";	
			break;		
		}

	// VARS - Relevant Dates
	
		// Generates the appropriate dates for defining the query range.
			$currentdate =  date("F j, Y");
			$daterangeopen = mktime (0,0,0,date("m"), date("d")-7, date("Y"));
			$daterangeclose = mktime (0,0,0,date("m"), date("d")-1, date("y"));
			$date_open = (date('D F j, Y',$daterangeopen));
			$date_close = (date('D F j, Y',$daterangeclose));

	// VARS - Query String
	
		// This query counts the number of orders entered in the db for the last 7 days		
		$query = "SELECT o.order_date 
				  FROM orders o 
				  WHERE o.order_date >= CURDATE()-7
				  AND o.order_date <> CURDATE();
				  ";

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

	echo "<br />";
	echo "Running SmartID Order Report Generator! <br />";

// Run Query and Write Report File
// *************************************************

	// Initialize Query Object
		$sql = new MySQL_3 ();
	
	// Open DB Connection
		$link_id = $sql->Connect ($host_type, $host_read, $host_login, $host_password, Debug_1::Trace_Code (__FILE__, __LINE__));
		
		$query_output = $sql->Query ($host_database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		
		$weekly_orders = $sql->Row_Count($query_output);
		
		// DEBUGGING DUMPS
			 // echo " ... Link ID: ".$link_id."<br/>";
			 // echo " ... Query: "; 
			 // Debug_1::Raw_Dump ($query);
			 // echo "<br/>";
			 // echo " ... Query Output: "; 
			 // Debug_1::Raw_Dump ($query_output);
			 // echo "<br/>";
			 echo "<b>... SmartIDOnline.com had ".$weekly_orders." orders from ".$date_open." to ".$date_close.".</b>";
			 echo "<br/>";			 
			 

// VARS - Email Generation
		
		// Email Headers
			//$email_smtp_server = "mail.sellingsource.com";
			$email_port = 25;
			$email_url = "sellingsource.com";
			$email_s_name = "Doug Daulton";
			$email_s_address = "dougd@sellingsource.com";	
			$email_subject = "TSS :: Smart ID Weekly Orders :: ".$currentdate;
				
		// Email Content	
			$mailbody_text = "Good morning, \r\n\r\n;
							  SmartIDOnline.com had ".$weekly_orders." orders 
							  from ".$date_open." to ".$date_close.".\r\n\r\n; 
							  Regards, \r\n\r\n;
							  Doug Daulton\r\n;
							 ";
							  
			$mailbody_html = "<html><body>
							  Good morning, <br /><br />
							  SmartIDOnline.com had <b>".$weekly_orders."</b> 
							  orders from ".$date_open." to ".$date_close.".							  
							  <br /><br />
							  Regards, <br /><br />
							  Doug Daulton<br />
							  </body></html>						 
							 ";


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
			echo " ... Mailing Id: ".$mailing_id."<br />";
			echo " ... Result: ".$result."<br />";
			// echo " ... Recipients: ";
			// Debug_1::Raw_Dump ($recipients);
			
			list ($em, $es) = explode (" ", microtime ());
		
			echo " ... Process Time: ".(((float)$es + (float)$em) - ((float)$ss + (float)$sm));
			echo "<br />";
			echo "Process Completed.";
			echo "<br /><br />";		
?>

