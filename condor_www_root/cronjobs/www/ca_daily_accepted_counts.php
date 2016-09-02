<?php

		define ('BASE', 'ca_tracking');

		// This enables this script to run standalone with a default of yesterday,
		//or be included as part of another script
		$target_date = $target_date ? $target_date : strtotime('yesterday');

		// Creates a tab delimited data file to be sent off file
		$company = "ca";

		// Make sure we keep running even if user aborts
		ignore_user_abort (TRUE);

		// Let it run forever
		set_time_limit (0);

		// server configuration
		// delete this before you go live
		// require_once ("/virtualhosts/site_config/server.cluster1.cfg.php");
		// uncomment this before you go live
		 require_once ("/virtualhosts/site_config/server.cfg.php");
		

		$page_ids = array();
		
		$tables = $sql->Get_Table_List (BASE);
		
		/*
		echo "<pre>\n";
		print_r ($tables);
		echo "</pre>\n";
		*/
		
		$q = "SELECT page_id, site_name, site_id FROM management.name_id_map ".
		"WHERE property_name='Cash Advance'";
		$result = $sql->Query (BASE, $q, Debug_1::Trace_Code (__FILE__, __LINE__));
		Error_2::Error_Test ($result, TRUE);
		
		while ($data = $sql->Fetch_Object_Row ($result))
		{
			$page_ids[] = $data; //array ('page_id'=>$data->page_id, 'site_name'=>$data->site_name);
		}
		$sql->Free_Result ($result);
		
		$line = "Date\tSite Name\tpromo ID\tTotal\r\n";
		foreach ($page_ids as $affiliate)
		{
			$stats = "stats".$affiliate->page_id."_".date ("Y_m", $target_date);
			if ($tables->$stats)
			{
				$q = "SELECT promo_id, sum(accepted) AS total FROM ca_tracking.id_blocks left join ca_tracking.".
				$stats." using (block_id) where site_id='".$affiliate->site_id."' and ".
				"stat_date ='".date ("Y-m-d", $target_date)."' group by promo_id order by promo_id";
			
				$result = $sql->Query(BASE, $q, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test ($result, TRUE);
				
				while ($data = $sql->Fetch_Object_Row ($result))
				{
					$line .= date ("Y-m-d", $target_date)."\t".$affiliate->site_name."\t".$data->promo_id."\t".$data->total."\r\n";
				}
				$sql->Free_Result ($result);
				
			}
		}
				
	/*
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


		
		echo "<pre>\n".$date_str."\n\n".$line."\n</pre>\n";;
		*/
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
			" name=\"cacounts".$date_str."\"\r\n".
			"Content-Transfer-Encoding: 7bit\r\n".
			"Content-Disposition: attachment; filename=\"cacounts".$date_str.".txt\"\r\n\r\n".
			$line."\r\n".
			"--".$outer_boundry."--\r\n\r\n";

		/*
		$file_name = 'cacounts'.$date_str.'.txt';
		$file_handle = fopen('/home/davidb/data/'.$file_name, "w");
		fwrite($file_handle, $line);
		fclose($file_handle);
		*/
		
		// Send the file to ed for processing
		mail ("David Bryant <davidb@sellingsource.com>", "cacounts".$date_str.'.txt', NULL, $batch_headers);
		mail ("Marilyn Carver <marketing@fc500.com>", "uclcounts".$date_str.'.txt', NULL, $batch_headers);
		

		
?>
