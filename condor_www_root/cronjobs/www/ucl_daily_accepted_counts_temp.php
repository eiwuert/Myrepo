<?php

		$target_date = strtotime('March 11, 2003');

		// Creates a tab delimited data file to be sent off file
		$company = "ucl";

		// Make sure we keep running even if user aborts
		ignore_user_abort (TRUE);

		// Let it run forever
		set_time_limit (0);

		// server configuration
		require_once ("/virtualhosts/site_config/server.cfg.php");

		$q = "SELECT date, site_name, promo_id, sum(accepted) as total FROM stats_".date ("Y_m", $target_date)." as stats left join d2_management.site as mgmt using (site_id) WHERE date='".date ("Y-m-d", $target_date)."' AND time < '00:00:00' GROUP BY stats.site_id, promo_id order by site_name, promo_id";
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

		$date_str = date("Ymd", $target_date);
			
		$batch_headers =
			"MIME-Version: 1.0\r\n".
			"Content-Type: Multipart/Mixed;\r\n boundary=\"".$outer_boundry."\"\r\n\r\n\r\n".
			"--".$outer_boundry."\r\n".
			"Content-Type: text/plain;\r\n".
			" charset=\"us-ascii\"\r\n".
			"Content-Transfer-Encoding: 7bit\r\n".
			"Content-Disposition: inline\r\n\r\n".
			"Leads for ".$date_str.".txt\r\n".
			"--".$outer_boundry."\r\n".
			"Content-Type: text/plain;\r\n".
			" charset=\"us-ascii\";\r\n".
			" name=\"uclcounts".$date_str."\"\r\n".
			"Content-Transfer-Encoding: 7bit\r\n".
			"Content-Disposition: attachment; filename=\"uclcounts".$date_str.".txt\"\r\n\r\n".
			$line."\r\n".
			"--".$outer_boundry."--\r\n\r\n";

		$file_name = 'uclcounts'.$date_str.'.txt';
		$file_handle = fopen('/virtualhosts/123onlinecash.com/www/st/data/'.$file_name, "w");
		fwrite($file_handle, $line);
		fclose($file_handle);

		// Send the file to ed for processing
		//mail ("davidb@sellingsource.com", "uclcounts".$date_str.'.txt', NULL, $batch_headers);
		mail ("marketing@fc500.com", "uclcounts".$date_str.'.txt', NULL, $batch_headers);
?>
