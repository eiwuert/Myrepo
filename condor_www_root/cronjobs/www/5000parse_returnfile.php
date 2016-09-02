<?php

// server configuration
	require_once ("/virtualhosts/site_config/server.cfg.php");
	require_once ("/virtualhosts/lib/setstat.1.php");
	 $path = '/virtualhosts/5000freelongdistance.com/data_mgt/';
	//$path = '/virtualhosts/direct.5000freelongdistance.com/data_mgt/';

// Constants
	$promo_status = new stdClass;
	$promo_status->valid = "valid";
	$site_id = 8869; // 5000freelongdistance.com
	$vendor_id = 0;
	$page_id = 8871;
	$database_name = "direct1_stat";
	define ("DB_NAME", 'freelongdistance');
	$today = date ("Ymd");
	$returns_array = array ("20030505");

	foreach ($returns_array as $event_date)
	{
		// $file_name = 'returns'.$event_date.'.txt';
		$file_name = 'batch_'.$event_date.'.bad';

		$file_handle = fopen($path.$file_name, 'r');

		if (!$file_handle)
		{
			die('could not open'.$file_name);
		}

		$max_date = 0;
		while (!feof($file_handle))
		{
			$line = fgets($file_handle, 4096);

			if (! strlen ($line))
				continue;

			// parse file as if it were comma delimted
			// list ($promcode, $ID, $null, $null, $null, $null, $null, $null, $null, $null, $null, $null, $null, $null, $null, $decline_date, $null, $null) = explode (",", $line);
			// SZ,20030415,144,Kelley,Gillis,kceb@ctcak.net,
			list ($promcode, $decline_date, $ID, $first_name, $last_name, $email, $status) = explode (",", $line);
			$max_date = $decline_date > $max_date ? $decline_date : $max_date;

			if ($status == "CCD")
			{
				// mark applicant as denied
				$q  = "UPDATE applicant_status SET denied=".$decline_date." WHERE applicant_id = ".$ID;
			} else
			{
				// mark applicant as approved
				$q  = "UPDATE applicant_status SET approved=".$decline_date." WHERE applicant_id = ".$ID;
			}
			
			echo $q."\n";
			$sql->Query (DB_NAME, $q);

			// query for tracking info
			$q = "SELECT pending, promo_id, sub_code FROM vendor_information left join applicant_status using (applicant_id) WHERE vendor_information.applicant_id = ".$ID;
			// echo "$q";
			$tracking_data = $sql->Query (DB_NAME, $q);
			$row = $sql->Fetch_Array_Row($tracking_data);
			$PromoID = $row["promo_id"];
			$SubCode = $row["sub_code"];
			if ($PromoID == "")
			{
				$PromoID = 10000;
			}

		// 	set tracking info
			$inf = Set_Stat_1::_Setup_Stats ($row['pending'], $site_id, $vendor_id, $page_id, $PromoID, $SubCode, $sql, $database_name, $promo_status, 'week');
			Set_Stat_1::Set_Stat ($inf->block_id, $inf->tablename, $sql, $database_name, 'denied', 1);
		}
	}

	if (preg_match ('/(\d{4})(\d{2})(\d{2})/', $max_date, $m))
	{
		
		$cut_date = $m[1].'-'.$m[2].'-'.$m[3];
		
		$query  = "
			SELECT 
				A.applicant_id, B.promo_id, B.sub_code AS promo_sub_code, A.pending as approved_date
			FROM 
				applicant_status A LEFT JOIN vendor_information B on (A.applicant_id = B.applicant_id) 
			WHERE 
				A.pending <= '".$cut_date."' 
				AND returned IS NULL
				AND approved IS NULL
				AND denied IS NULL
			ORDER BY 
				applicant_id";
		
		echo $query."\n";
		$result = $sql->Query (DB_NAME, $query, Debug_1::Trace_Code(__FILE__, __LINE__));
		Error_2::Error_Test ($result, TRUE);
		
		while ($row = $sql->Fetch_Array_Row ($result))
		{
			$row['promo_id'] = $row['promo_id'] ? $row['promo_id'] : 10000;
			$row['promo_sub_code'] = $row['promo_sub_code'] ? $row['promo_sub_code'] : '';
	
			$query = "
				UPDATE
					applicant_status 
				SET 
					returned = '".$today."',
					approved = '".$row['approved_date']."' 
				WHERE 
					applicant_id = ".$row['applicant_id'];
	
			echo $query."\n";
			$sql->Query (DB_NAME, $query);
	
			$info = Set_Stat_1::_Setup_Stats (
				$row['approved_date'], 
				$site_id, $vendor_id, $page_id, 
				$row['promo_id'], $row['promo_sub_code'], 
				$sql, $database_name, $promo_status,
				'week'
			);
			
			Set_Stat_1::Set_Stat ($info->block_id, $info->tablename, $sql, $database_name, 'approved');
		}
		
	}

// we should be done!
?>
