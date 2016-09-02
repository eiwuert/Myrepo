<?php
	// Creates a tab delimited data file to be sent off file

	// Make sure we keep running even if user aborts
	ignore_user_abort (TRUE);

	// Let it run forever
	set_time_limit (0);
	
	// server configuration
	require_once ("/virtualhosts/site_config/server.cfg.php");

	// set the header for the file
	$line = "First Name \t Last Name \t Email Address \t Street Address \t City \t State \t Zip \t Home Phone \t Bank \t Routing Number \t Check Account Number \t Savings Account Number \t SSN \t Card ID \r\n";
	$query = "SELECT first_name, last_name, email_address, address, city, state, zip, phone_number, bank_name, routing_number, account_number, social_security_number, account_type FROM applicant_information, banking_information WHERE banking_information.applicant_id=applicant_information.applicant_id AND order_date=".date(Ymd, time()-86400);
	$result = $sql->Query ('mbcash_com', $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);

	// Get the data
	while ($data = $sql->Fetch_Object_Row ($result))
	{
		$data->account_type == 'checking' ? $checking_account_number = $data->account_number : $savings_account_number = $data->account_number;
		$line .= $data->first_name." \t ".$data->last_name." \t ".$data->email_address." \t ".$data->address." \t ".$data->city." \t ".$data->state." \t ".$data->zip." \t ".$data->phone_number." \t ".$data->bank_name." \t ". $data->routing_number." \t ".$checking_account_number." \t ".$savings_account_number." \t ".$data->social_security_number." \t 1000 \r\n";
	}

	// if the line is LARGER than the headers (They are 146)
	//if (strlen($line) > 150) // PDS: Send the file no matter what!!
	{
		$outer_boundry = md5 ("Outer Boundry");
		$inner_boundry = md5 ("Inner Boundry");

		$batch_headers =
			"MIME-Version: 1.0\r\n".
			"Content-Type: Multipart/Mixed;\r\n boundary=\"".$outer_boundry."\"\r\n\r\n\r\n".
			"--".$outer_boundry."\r\n".
			"Content-Type: text/plain;\r\n".
			" charset=\"us-ascii\"\r\n".
			"Content-Transfer-Encoding: 7bit\r\n".
			"Content-Disposition: inline\r\n\r\n".
			"Leads for ".date(Ymd).".txt\r\n".
			"--".$outer_boundry."\r\n".
			"Content-Type: text/plain;\r\n".
			" charset=\"us-ascii\";\r\n".
			" name=\"mbcash".date(Ymd)."\"\r\n".
			"Content-Transfer-Encoding: 7bit\r\n".
			"Content-Disposition: attachment; filename=\"mbcash".date(Ymd).".txt\"\r\n\r\n".
			$line."\r\n".
			"--".$outer_boundry."--\r\n\r\n";
	
		// Send the file to ed for processing
		mail ("johnt@sellingsource.com, pauls@sellingsource.com, ecross@41cash.com", "mbcash".date(Ymd).'.txt', NULL, $batch_headers);
	}
?>
