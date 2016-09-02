<?php

// 21days_autoapproved.php
//
// this file will query applicant_status of the "mbcash_com" DB for pending dates
// <= 21 days before today then mark those records as approved with todays date.
// after marking record as approved, stats will be updated

	ini_set ('magic_quotes_runtime', 0);

 	require_once ("/virtualhosts/site_config/server.cfg.php");
	require_once ("/virtualhosts/lib/setstat.1.php");

	include ('int2Date.php');


	// Constants

	define ("DB_NAME", 'mbcash_com');
	
	$promo_status = new stdClass;
	$promo_status->valid = "valid";
	$today = date("z");
	$approve_date = date ("Ymd");
	$site_id = 1965; // mbcash.com
	$vendor_id = 0;
	$page_id = 1967;
	$database_name = "stat";
	$column = "approved";

// Query date will always be 21 days prior to today
	$int2Date = new int2Date ();
	$queryDate = $int2Date->parseDate(($today - 21), 2003, "");

	$query  = "
		SELECT 
			A.applicant_id, B.promo_id, B.sub_code AS promo_sub_code, FROM_DAYS(TO_DAYS(pending)+21) AS approved_date 
		FROM 
			applicant_status A LEFT JOIN vendor_information B on (A.applicant_id = B.applicant_id) 
		WHERE 
			A.pending <= ".$queryDate." 
			AND returned IS NULL
			AND approved IS NULL
			AND denied IS NULL
		ORDER BY 
			applicant_id";
			
	$result = $sql->Query (DB_NAME, $query, Debug_1::Trace_Code(__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	
	$n_approved = 0;
	while ($row = $sql->Fetch_Array_Row($result))
	{

		$row['promo_id'] = $row['promo_id'] ? $row['promo_id'] : 10000;
		$row['promo_sub_code'] = $row['promo_sub_code'] ? $row['promo_sub_code'] : '';

		$query = "
			UPDATE 
				applicant_status 
			SET 
				returned = '".$approve_date."', 
				approved = '".$row['approved_date']."' 
			WHERE 
				applicant_id = ".$row['applicant_id'];

		$sql->Query (DB_NAME, $query);

		$info = Set_Stat_1::_Setup_Stats (
			$row['approved_date'], 
			$site_id, $vendor_id, $page_id, 
			$row['promo_id'], $row['promo_sub_code'], 
			$sql, $database_name, $promo_status
		);
		
		Set_Stat_1::Set_Stat ($info->block_id, $info->tablename, $sql, $database_name, 'approved');

		$n_approved++;
	}

	

?>
