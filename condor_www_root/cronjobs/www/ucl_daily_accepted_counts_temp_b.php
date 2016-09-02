<?php

	// creates a series of tab-delimited data files
	// to be emailed
	
	// note: dates must be in the same year

	$start_date = strtotime('Apr 17, 2003');
	$stop_date = strtotime('Apr 19, 2003');
	$company = "ucl";
	
	// get the number of days
	$start_doy = date ("z", $start_date);
	$stop_doy = date ("z", $stop_date);

	if ($start_doy < $stop_doy)
	{
		$num_days = $stop_doy - $start_doy;
		
		// keep running even if user aborts
		ignore_user_abort (true);
		// let it run forever
		set_time_limit (0);
		
		require_once ("/virtualhosts/site_config/server.cfg.php");
		
		for ($i = 0; $i < $num_days; $i++)
		{
			$new_day = mktime (0, 0, 0, date ("n", $start_date), date ("j", $start_date) + $i, date ("Y", $start_date));
			
			$q  = "SELECT date, site_name, promo_id, sum(accepted) AS total FROM stats_";
			$q .= date ("Y_m", $new_day)." AS stats LEFT JOIN d2_management.site AS mgmt ";
			$q .= "USING (site_id) WHERE date='".date ("Y-m-d", $new_day)."' AND time < '00:00:00' ";
			$q .= "GROUP BY stats.site_id, promo_id ORDER BY site_name, promo_id";
			
			$result = $sql->Query ($company."_tracking", $q, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, true);
			
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
			"Leads for ".date("Ymd", $new_day).".txt\r\n".
			"--".$outer_boundry."\r\n".
			"Content-Type: text/plain;\r\n".
			" charset=\"us-ascii\";\r\n".
			" name=\"uclcounts".date("Ymd", $new_day)."\"\r\n".
			"Content-Transfer-Encoding: 7bit\r\n".
			"Content-Disposition: attachment; filename=\"uclcounts".date("Ymd", $new_day).".txt\"\r\n\r\n".
			$line."\r\n".
			"--".$outer_boundry."--\r\n\r\n";

			$file_name = 'uclcounts'.date("Ymd", $new_day).'.txt';
			$file_handle = fopen('/virtualhosts/123onlinecash.com/www/st/data/'.$file_name, "w");
			fwrite($file_handle, $line);
			fclose($file_handle);

		// Send the file to ed for processing
			mail ("davidb@sellingsource.com", "uclcounts".date("Ymd", $new_day).'.txt', NULL, $batch_headers);
		//	mail ("marketing@fc500.com", "uclcounts".date("Ymd", $new_day).'.txt', NULL, $batch_headers);

			
		}
	}
	
?>
