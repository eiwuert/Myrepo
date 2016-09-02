<?php
	list ($ss, $sm) = explode (" ", microtime ());
	// Make sure we keep running even if user aborts
	ignore_user_abort (TRUE);
	
	// Let it run forever
	set_time_limit (0);
	
	// Database connectivity
	include_once ("/virtualhosts/cronjobs/includes/load_balance.mysql.class.php");

	// Build our db object
	$user_sql = new MySQL ("read1.iwaynetworks.net", "write1.iwaynetworks.net", "sellingsource", "%selling\$_db", "pcl_visitor", 3306, "\t".__FILE__." -> ".__LINE__."\n");
	$site_sql = new MySQL ("read2.iwaynetworks.net", "write2.iwaynetworks.net", "sellingsource", "%selling\$_db", "management", 3306, "\t".__FILE__." -> ".__LINE__."\n");
//	$user_sql = new MySQL ("read1.dev04.tss", "write1.dev04.tss", "root", "", "ucl_visitor", 3306, "\t".__FILE__." -> ".__LINE__."\n");
//	$site_sql = new MySQL ("read2.dev04.tss", "write2.dev04.tss", "root", "", "d2_management", 3306, "\t".__FILE__." -> ".__LINE__."\n");

	// Which dates to I pull
	$hours_24 = date ("Y-m-d", strtotime ("-1 day"));
	$hours_48 = date ("Y-m-d", strtotime ("-2 day"));

	// Pull the user information
	$query =
		"select ".
			"base.unique_id, ".
			"base.application_id, ".
			"full_name, ".
			"last_visit_date, ".
			"type, ".
			"site_id, ".
			"address_id, ".
			"personal.email, ".
			"fund_amount, ".
			"site_info.promo_id, ".
			"site_info.promo_sub_code ".
		"from ".
			"base, ".
			"applications, ".
			"site_info, ".
			"personal ".
		"left join ".
			"funding using (application_id) ".
		"where ".
			"last_visit_date in ('".$hours_24."', '".$hours_48."') and ".
			"type in ('VISITOR', 'QUALIFIED', 'PROSPECT') and ".
			"base.application_id=applications.application_id and ".
			"base.application_id=personal.application_id and ".
			"base.application_id=site_info.application_id";
	$user_info = $user_sql->Wrapper ($query, NULL, "\t".__FILE__." -> ".__LINE__."\n");
	
	// Pull the site information
	$query =
		"select ".
			"* ".
		"from ".
			"name_id_map ";
	$site = $site_sql->Wrapper ($query, "site_id", "\t".__FILE__." -> ".__LINE__."\n");
	
	// The messages:
	$message_header =
		"Date: ".date ("m/d/Y")."\r\n\r\n".
		"Dear %%%user->full_name%%%,\r\n\r\n";

	$message_footer =
		"We appreciate your interest and business.\r\n\r\n\r\n".
		"Thank You,\r\n\r\n".
		"%%%site->site_name%%%\r\n".
		"no-reply@%%%site->url%%%\r\n\r\n\r\n".
		"______________________________________________________________________\r\n".
		"You have received this email because you have pre-applied for a loan on %%%site->site_name%%%.  If this email reaches you in error, please disregard it.\r\n\r\n\r\n";

	$step4_subject = "Application for \$ %%%user->fund_amount%%%.00 CASH";
	$step4_body =
		"Congratulations!  Our records indicate that you qualified for a \$ %%%user->fund_amount%%%.00 loan at %%%site->site_name%%% and simply need to fax your documents to the processing center to get your cash as soon as tomorrow!\r\n\r\n".
		"To get a new copy of the application and receive your cash, simply click on the link below:\r\n".
		"\thttp://%%%site->return_url%%%/return_visitor.php?unique_id=%%%user->unique_id%%%\r\n\r\n".
		"This link is good for 48 hours.\r\n\r\n".
		"We could deposit \$ %%%user->fund_amount%%%.00 in your account the next business day!\r\n\r\n\r\n";

	$step3_subject = "Application for \$ %%%user->fund_amount%%%.00 CASH";
	$step3_body =
		"Congratulations!  Our records indicate that you qualified for a \$ %%%user->fund_amount%%%.00 loan at %%%site->site_name%%% and simply need to complete the process to get your cash as soon as tomorrow!\r\n\r\n".
		"If there are any questions which we have not answered, please do not hesitate to contact us via the return email.\r\n\r\n".
		"We do thank you for your interest in %%%site->site_name%%% and hope that we can still do business.\r\n\r\n".
		"To complete the application process and receive your cash, simply click on the link below:\r\n".
		"\thttp://%%%site->return_url%%%/return_visitor.php?unique_id=%%%user->unique_id%%%\r\n\r\n".
		"You could have \$ %%%user->fund_amount%%%.00 deposited in your account the next business day!\r\n\r\n\r\n";

	$step2_subject = "Your Application at %%%site->site_name%%%";
	$step2_body =
		"Your cash advance is pending!  You only need to complete the application you already started at %%%site->site_name%%% and you could receive up to $500.00 cash the next business day!\r\n\r\n\r\n".
		"To complete the application process and receive your cash, simply click on the link below:\r\n".
		"\thttp://%%%site->return_url%%%/return_visitor.php?unique_id=%%%user->unique_id%%%\r\n\r\n".
		"This link is good for 48 hours.\r\n\r\n".
		"If you have any additional questions, you can contact us at no-reply@%%%site->url%%%.\r\n\r\n\r\n";

	$step1_subject = "Your Application at %%%site->site_name%%%";
	$step1_body =
		"We all need a little extra cash from time to time... you have already pre-qualified for up to \$500.00 at %%%site->site_name%%%!\r\n\r\n".
		"To receive your cash, all you need to do is complete the application you already started!  Once you complete the application we could deposit up to $500.00 in your bank account as early as the next business day!\r\n\r\n".
		"To complete the application process and receive your cash, simply click on the link below:\r\n".
		"\thttp://%%%site->return_url%%%/return_visitor.php?unique_id=%%%user->unique_id%%%\r\n\r\n".
		"This link is good for 48 hours.\r\n\r\n";

	foreach ($user_info as $user_data)
	{
		$message_data = new stdClass ();
		$message_data->user=$user_data;
		$name_view = unserialize($site_info[$user_data->site_id]->run_state);
		$message_data->site->site_name=$name_view->name_view;
		$message_data->site->url=$site_info[$user_data->site_id]->site_name;
		$message_data->site->return_url = $message_data->site->url;

		$headers =
			"From: ".$message_data->site->site_name." - Approval Department <no-reply@".$message_data->site->url.">\r\n".
			"Reply-To: ".$message_data->site->site_name." - Approval Department <no-reply@".$message_data->site->url.">\r\n";

		switch (TRUE)
		{
			case ($message_data->user->type == "PROSPECT"): // Step 4 is finished
				$subject = preg_replace ("/%%%(.*?)%%%/e", "\$message_data->\\1", $step4_subject);
				$message = preg_replace ("/%%%(.*?)%%%/e", "\$message_data->\\1", $message_header.$step4_body.$message_footer);
				$step4++;
				break;

			case ($message_data->user->type == "QUALIFIED"): // Step 3 is finished
				$subject = preg_replace ("/%%%(.*?)%%%/e", "\$message_data->\\1", $step3_subject);
				$message = preg_replace ("/%%%(.*?)%%%/e", "\$message_data->\\1", $message_header.$step3_body.$message_footer);
				$step3++;
				break;

			case ($message_data->user->address_id): // Step 2 is finished
				$subject = preg_replace ("/%%%(.*?)%%%/e", "\$message_data->\\1", $step2_subject);
				$message = preg_replace ("/%%%(.*?)%%%/e", "\$message_data->\\1", $message_header.$step2_body.$message_footer);
				$step2++;
				break;

			default: // Step 1 is finished
				$subject = preg_replace ("/%%%(.*?)%%%/e", "\$message_data->\\1", $step1_subject);
				$message = preg_replace ("/%%%(.*?)%%%/e", "\$message_data->\\1", $message_header.$step1_body.$message_footer);
				$step1++;
				break;
		}

		// Send the message
		mail ($message_data->user->email, $subject, $message, $headers);
	}
	$counts = "Step1: ".$step1."\nStep2: ".$step2."\nStep3: ".$step3."\nStep4: ".$step4."\n";
	list ($es, $em) = explode (" ", microtime ());
	$process_time = ((float)$es + (float)$em) - ((float)$ss + (float)$sm);
	mail ("pauls@sellingsource.com", "Finish App Stats - PCL", "Sent:\n".$counts."\nTook ".$process_time." seconds\n");
	mail ("johnh@sellingsource.com", "Finish App Stats - PCL", "Sent:\n".$counts."\nTook ".$process_time." seconds\n");
?>
