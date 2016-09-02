<?php
	// Make sure we keep running even if user aborts
	ignore_user_abort (TRUE);

	// Database connectivity
	include_once ("smtp.class.php");
	include_once ("/home/sellingsource/sql/sql.class.php");

	$hostname = "localhost";
	$username = "root";
	$dbpassword = "";
	$db="st";

	mysql_local_connect ($hostname, $username, $dbpassword) or die (mysql_error());
	mysql_local_select_db( $db ) or die (mysql_error());

	// The messages:
	$message_header =
		"Date: %%%date%%%\r\n\r\n".
		"Dear %%%firstname%%% %%%lastname%%%,\r\n\r\n";

	$message_footer =
		"We appreciate your interest and business.\r\n\r\n\r\n".
		"Thank You,\r\n\r\n".
		"%%%weburl%%%\r\n".
		"customerservice@mycash-online.com\r\n\r\n\r\n".
		"______________________________________________________________________\r\n".
		"You have received this email because you have pre-applied for a loan on %%%weburl%%%.  If this email reaches you in error, please disregard it.\r\n\r\n\r\n";

	$form_c_subject = "Application for \$ %%%la%%%.00 CASH";
	$form_c_body =
		"Congratulations!  Our records indicate that you qualified for a \$ %%%la%%%.00 loan at %%%weburl%%% and simply need to complete the process to get your cash as soon as tomorrow!\r\n\r\n".
		"If there are any questions which we have not answered, please do not hesitate to contact us via the return email.\r\n\r\n".
		"We do thank you for your interest in %%%weburl%%% and hope that we can still do business.\r\n\r\n".
		"To complete the application process and receive your cash, simply click on the link below:\r\n".
		"\thttp://www.123onlinecash.com/st/applyonline.php?sid=%%%id%%%&unl=%%%uid%%%\r\n\r\n".
		"If you would like to access your application and make a change or update and reprocess, you can use this link:\r\n".
		"\thttp://www.123onlinecash.com/st/new_apply.php?sid=%%%id%%%&unl=%%%uid%%%\r\n\r\n".
		"We'll deposit \$ %%%la%%%.00 in your account the next business day!\r\n\r\n\r\n";

	$form_b_subject = "Your Application at %%%weburl%%%";
	$form_b_body =
		"Your cash advance is pending!  You only need to complete the application you already started at %%%weburl%%% and receive up to $500 cash the next business day!\r\n\r\n\r\n".
		"To complete the application process and receive your cash, simply click on the link below:\r\n".
		"\thttp://www.123onlinecash.com/st/new_apply.php?sid=%%%id%%%&unl=%%%uid%%%\r\n\r\n".
		"This link is good for 48 hours.\r\n\r\n".
		"If you have any additional questions, you can contact us at customerservice@mycash-online.com.\r\n\r\n\r\n";

	$form_a_subject = "Your Application at %%%weburl%%%";
	$form_a_body = "";
		"We all need a little extra cash from time to time... you have already pre-qualified for up to $500.00 at %%%weburl%%%!\r\n\r\n".
		"To receive your cash, all you need to do is complete the application you already started!  Once you complete the application we can deposit up to $500.00 in your bank account as early as the next business day!\r\n\r\n".
		"To complete the application process and receive your cash, simply click on the link below:\r\n".
		"\thttp://www.123onlinecash.com/st/new_apply.php?sid=%%%id%%%&unl=%%%uid%%%\r\n\r\n".
		"This link is good for 48 hours.\r\n\r\n".

	// Establish the parameters to the smtp server
	$params['host'] = '64.119.211.10';				// The smtp server host/ip
	$params['port'] = 25;						// The smtp server port
	$params['helo'] = "oledirect2.com";			// What to use when sending the helo command. Typically, your domain/hostname
	$params['auth'] = FALSE;						// Whether to use basic authentication or not
	$params['user'] = 'testuser';				// Username for authentication
	$params['pass'] = 'password';				// Password for authentication

	// Create the smtp object
	$smtp = new SMTP ($params);

	// Connect to the smtp server
	if (!$smtp->connect())
	{
		exit;
	}

	// Set who the email is from
	$smtp->set ("from", "<customerservice@mycash-online.com>");

	// Determine how far back to look in the database for emails to send (in days)
	$look_back_duration = (strlen ($_GET ["look_back_duration"]) ? $_GET ["look_back_duration"] : 35);
	$extended_search_duration = $look_back_duration + 30;
	
	// Give the query plenty of time to run
	set_time_limit (50);

	// Pull a list of "do not send email addresses
	$query = "select distinct(email) from translog where date between '".date ("Ymd", strtotime ("-$extended_search_duration) day"))."' and '".date ("Ymd", strtotime ("-1 day"))."' and (followup=1 or remove=1 or result='approvedloan')";
	$resource_id = $GLOBALS ["sql"]->Query ($query) or die (mysql_error());

	while ($temp = mysql_fetch_object ($resource_id))
	{
		// Make sure I have plenty of time to execute this
		set_time_limit (5);

		// Add to the reject list
		$reject_list->{$temp->email} = TRUE;
	}

	$stats_message = "Reject List Size: ".mysql_num_rows ($resource_id)."\r\n";

	// Give the query plenty of time to run
	set_time_limit (50);

	// Process the Form C emails first
	$query = "select distinct(email), id, uid, firstname, lastname, weburl, la, prequal, forma, formb, qual from translog where date between '".date ("Ymd", strtotime ("-$look_back_duration day"))."' and '".date ("Ymd", strtotime ("-1 day"))."' and weburl !='' and result is null and remove != 1 and followup != 1 limit 10";
	$resource_id = $GLOBALS ["sql"]->Query ($query) or die (mysql_error());

	// Some stats
	$form_a_count = 0;
	$form_b_count = 0;
	$form_c_count = 0;

	// Walk the results and send the email
	while ($db_data = mysql_fetch_object ($resource_id))
	{
		// Make sure I have plenty of time to execute this
		set_time_limit (15);

		// Put the date into the array for replacement
		$db_date->{"date"} = date ("m-d-Y");

		// Test for count
		if (!$reject_list->{$db_data->email})
		{
			// We can send the email -- determine which email to use
			
			// Nuke any existing message
			unset ($message);
			unset ($headers);

			// Determine which message to send to the user
			switch (TRUE)
			{
				case ($db_data->qual == "qual"): // Send form C email
					$form_c_count ++;
					// Build the headers
					$headers =
						"MIME-Version: 1.0\r\n".
						"Content-type: text/html; charset=iso-8859-1\r\n".
						"From: customerservice@mycash-online.com \r\n".
						"Reply-To: customerservice@mycash-online.com \r\n".
						"Subject: ".$form_c_subject."\r\n";

					// Put the message together
					$message = $message_header.$form_c_body.$message_footer;
					break;

				case ($db_data->forma == "yes" and $db_data->formb != "yes"): // Send form B email
					$form_b_count ++;
					// Build the headers
					$headers =
						"MIME-Version: 1.0\r\n".
						"Content-type: text/html; charset=iso-8859-1\r\n".
						"From: customerservice@mycash-online.com \r\n".
						"Reply-To: customerservice@mycash-online.com \r\n".
						"Subject: ".$form_b_subject."\r\n";

					// Put the message together
					$message = $message_header.$form_b_body.$message_footer;
					break;

				case ($db_data->prequal == "qual" and $db_data->forma != "yes"): // Send form A email
					$form_a_count ++;
					// Build the headers
					$headers =
						"MIME-Version: 1.0\r\n".
						"Content-type: text/html; charset=iso-8859-1\r\n".
						"From: customerservice@mycash-online.com \r\n".
						"Reply-To: customerservice@mycash-online.com \r\n".
						"Subject: ".$form_a_subject."\r\n";

					// Put the message together
					$message = $message_header.$form_a_body.$message_footer;
					break;
			}

			// Make sure we have a message to send
			if (strlen ($message))
			{
				// Set the headers
				$smtp->set ("header", preg_replace ("/%%%(.*?)%%%/e", "\$db_data->\\1", stripslashes ($headers));

				// Customize the message
				$send_message = preg_replace ("/%%%(.*?)%%%/e", "\$db_data->\\1", stripslashes ($message));

				// Send the message
				$smtp->send ("pauls@sellingsource.com", $send_message);
				//$smtp->send ($db_data->email, $send_message);

				// Add to the update list
				$update_list .= "id='".$db_data->id."' or ";
			}
		}
	}
	
	// Allow time to finish
	set_time_limit (30);

	// Put some information together
	$stats_message .= "Send List Size: ".mysql_num_rows ($resource_id)."\r\n";

	// Updated the db
	if (strlen ($update_list))
	{
		$query = "update translog set followup=1 where ".substr ($update_list, 0, -4);
		$GLOBALS ["sql"]->Query ($query) or die (mysql_error());
	}

	$message = "Form A: ".$form_a_count."\nForm B: ".$form_b_count."\nForm C: ".$form_c_count."\n";
	mail ("pauls@sellingsource.com", "Auto Response Results", $stats_message.$message);
	mail ("derekl@sellingsource.com", "Auto Response Results", $message);
?>
