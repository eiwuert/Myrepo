<?php

$target_date = strtotime ("January 1, 2003");

$stop_date = strtotime ("yesterday");

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
		

while ($target_date <= $stop_date)
{

		$page_ids = array();
		
		$tables = $sql->Get_Table_List ('ca_tracking');
		
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

		
		$file_name = 'cacounts'.$date_str.'.txt';
		$file_handle = fopen('/virtualhosts/123onlinecash.com/www/st/data/'.$file_name, "w");
		fwrite($file_handle, $line);
		fclose($file_handle);

		
		// Send the file to ed for processing
		//mail ("davidb@sellingsource.com", "cacounts".$date_str.'.txt', NULL, $batch_headers);
		//mail ("marketing@fc500.com", "uclcounts".$date_str.'.txt', NULL, $batch_headers);
		

		
	$target_date = strtotime ("1 day", $target_date);
}

echo "done";

?>
