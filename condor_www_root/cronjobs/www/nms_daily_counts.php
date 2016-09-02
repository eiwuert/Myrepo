<?php

	// Creates a tab delimited data file to be sent off file

	company = "ucl";

	// Make sure we keep running even if user aborts
	ignore_user_abort (TRUE);

	// Let it run forever
	set_time_limit (0);

	// server configuration
	require_once ("/virtualhosts/site_config/server.cfg.php");

	$now = time ();
	$month = date ("m", $now);
	$day = date ("d", $now);
	$year = date ("Y", $now);

	$yesterday = mktime (0, 0, 0, $month, $day-1, $year);

	$q = "SELECT date, site_name, promo_id, sum(accepted) as total FROM stats_".date ("Y_m", $yesterday)." as stats left join d2_management.site as mgmt using (site_id) WHERE date='".date ("Y-m-d", $yesterday)."' AND time < '00:00:00' GROUP BY stats.site_id, promo_id order by site_name, promo_id";
	$result = $sql->Query ($company."_tracking", $q, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);

	$line = "Date \t Site name \t promo ID \t Total\r\n";

	while ($data = mysql_fetch_object($result))
	{
		if ($data->total > 0)
		{
			$line .= $data->date." \t ".$data->site_name." \t ".$data->promo_id." \t ".$data->total."\r\n";
		}
	}
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
			" name=\"uclcounts".date(Ymd)."\"\r\n".
			"Content-Transfer-Encoding: 7bit\r\n".
			"Content-Disposition: attachment; filename=\"uclcounts".date(Ymd).".txt\"\r\n\r\n".
			$line."\r\n".
			"--".$outer_boundry."--\r\n\r\n";
	
		// Send the file to ed for processing
		mail ("johnt@sellingsource.com", "uclcounts".date(Ymd).'.txt', NULL, $batch_headers);

/*
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
			" name=\"nmscounts".date(Ymd)."\"\r\n".
			"Content-Transfer-Encoding: 7bit\r\n".
			"Content-Disposition: attachment; filename=\"nmscounts".date(Ymd).".txt\"\r\n\r\n".
			$line."\r\n".
			"--".$outer_boundry."--\r\n\r\n";

	$headers	=	"From: <info@123onlinecash.com>\r\n";
	$headers	.=	"Content-type: text/plain\r\n";
	$headers	.=	"X-mailer: PHP/";
	$success = mail('John Telesh <johnt@sellingsource.com>', 'Counts for 20030216', $line, $headers);
	mail ( 'johnt@sellingsource.com', 'test email', 'here is the message');

		// Send the file
		mail ("johnt@sellingsource.com", "nmscounts".date(Ymd).'.txt', NULL, $batch_headers);
		mail ("johnt@sellingsource.com", $company."_".date("Ymd", $yesterday).".txt", $line, $batch_headers);
//		echo $test_str;
*/
?>