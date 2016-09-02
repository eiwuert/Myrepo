<?php
	ini_set ('magic_quotes_runtime', 0);
	ini_set ('session.use_cookies', 0);

	list ($ss, $sm) = explode (" ", microtime ());
	// Make sure we keep running even if user aborts
	ignore_user_abort (TRUE);

	// Let it run forever
	set_time_limit (0);

	// Database connectivity
	require_once ("/virtualhosts/lib/mysql.3.php");
	require_once ("/virtualhosts/lib/lib_mail.1.php");
	require_once ("/virtualhosts/lib/error.2.php");

	// Connection information

	//define ("HOST", "ds03.tss");
	//define ("USER", "root");
	//define ("PASS", "");

	define ("HOST", "selsds001");
	define ("USER", "sellingsource");
	define ("PASS", "%selling\$_db");
	$sent = 0;
	// Build the sql object
	$sql = new MySQL_3 ();

	// Try the connection
	$result = $sql->Connect ("BOTH", HOST, USER, PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	// Which dates to I pull
	$range_start = strtotime (date ("Y-m-d", strtotime ("-2 day"))); // Start 48 hours ago
	$range_end = strtotime (date ("Y-m-d 23:59:59", strtotime ("-1 day"))); // End 24 hours ago

	// Pull the user information
	$query = "
		select
			*
		from
			`session_site`
		where
			modifed_date between FROM_UNIXTIME(".$range_start.") and FROM_UNIXTIME(".$range_end.")";

	$result = $sql->Query ("olp_ca_visitor", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);

	$total_found = $sql->Row_Count($result);
	$html_page = file_get_contents("/virtualhosts/cronjobs/www/email/ca_email.html");
	$subject = "Your Application at %%%data[config]->site_name%%%";
	//foreach ($user_info as $user_data)
	@session_start();
	while ($session = $sql->Fetch_Array_Row ($result))
	{
		$_SESSION = array();

		session_decode ($session["session_info"]);
		$data = $_SESSION;
		$headers =
			"From: ".$data["config"]->site_name." - Approval Department <no-reply@".$data["config"]->site_name.">\r\n".
			"Reply-To: ".$data["config"]->site_name." - Approval Department <no-reply@".$data["config"]->site_name.">\r\n".
			"MIME-Version: 1.0\r\n".
			"Content-Type: text/html; charset=iso-8859-1\r\n";
		$message = NULL;
		$completed = FALSE;
		if ($data["application_id"])
		{
			if($data["completed"]["page4"])
			{
				$query = "
					select
						*
					from
						application
					where
						application_id='".$data["application_id"]."'";
				$app_result = $sql->Query ("ca_olp_visitor", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				$application = $sql->Fetch_Object_Row ($app_result);

				if ($application->type == "VISITOR" || $application->type == "QUALIFIED" || $application->type == "PROSPECT")
				{
					$completed = FALSE;
				}
				else
				{
					$completed = TRUE;
				}
			}

			if($completed == FALSE)
			{
				if (!$data["loan_note"]["fund_amount"])
				{
					$data["loan_note"]["fund_amount"] = "up to \$500.00";
				}
				else
				{
					$data["loan_note"]["fund_amount"] = "\$".$data["loan_note"]["fund_amount"].".00";
				}
				$message = preg_replace ("/%%%(.*?)%%%/e", "\$\\1", $message_header.$html_page);
				$mail_subject = preg_replace ("/%%%(.*?)%%%/e", "\$\\1", $subject);
				$sent++;
				//@Lib_Mail::mail ("todd@toddlee.org", $mail_subject, $message, $headers);
				@Lib_Mail::mail ($data["personal"]["email"], $mail_subject, $message, $headers);
			}
		}
	}

	list ($es, $em) = explode (" ", microtime ());
	$summary = "\nRows Processed: ".$total_found."\nEmails Sent: ".$sent."\n\n";
	$process_time = ((float)$es + (float)$em) - ((float)$ss + (float)$sm);
	//mail ("pauls@sellingsource.com", "Finish App Stats - CA_OLP", "Summary:\n".$summary."\nTook ".$process_time." seconds\n");
	//mail ("johnh@sellingsource.com", "Finish App Stats - CA_OLP", "Summary:\n".$summary."\nTook ".$process_time." seconds\n");
?>
