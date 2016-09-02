<?php



 // 21days_autoapproved.php
 //
 // this file will query applicant_status of the "mbcash_com" DB for pending dates
 // <= 21 days before today then mark those records as approved with todays date.
 // after marking record as approved, stats will be updated
 
 // includes
	include ('int2Date.php');
 	require_once ("/virtualhosts/site_config/server.cfg.php");
	require_once ("/virtualhosts/lib/setstat.1.php");
	define ("DB_NAME", 'mbcash_com');

// Constants
	$inf = new MySQL_3 ();
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
	$int2Date = new int2Date();
	$queryDate = $int2Date->parseDate(($today - 21),2003,"");

	$q  = "
		SELECT 
			A.applicant_id, B.promo_id, B.sub_code 
		FROM 
			applicant_status A LEFT JOIN vendor_information B on (A.applicant_id = B.applicant_id) 
		WHERE 
			A.pending <= ".$queryDate." 
			AND returned IS NULL
			AND approved IS NULL
			AND denied IS NULL
		ORDER BY 
			applicant_id";
			
	// echo $q.'<br><BR>';
	$rs = $sql->Query (DB_NAME, $q);
        $rows = $sql->Row_Count($rs);

	if($rs)
	{
	// we have a valid recordset, continue script
	// echo "query good<BR>";
		$ID_List = 0;
		$Promo_Array = array();
		$SubCode_Array = array();
		while ($row = $sql->Fetch_Array_Row($rs))
		{
			// pull values from $rs array & check for NULL
			$Applicant_ID = $row["applicant_id"];
			$PromoID = $row["promo_id"];
			$SubCode = $row["sub_code"];
			if ($PromoID == "")
			{
				$PromoID = 10000;
			}
			if ($SubCode == "")
			{
				$SubCode = "";
			}
			$ID_List.=",$Applicant_ID";
			array_push ($Promo_Array, $PromoID);
			array_push ($SubCode_Array, $SubCode);

		}

		// Mark ID_List as approved with todays date
		$ID_List = substr($ID_List, 2);  // remove 0, from front of list
		$q  = "UPDATE applicant_status SET returned = '".$approve_date."', approved = '".$approve_date."' WHERE applicant_id IN (".$ID_List.")";
		echo $q.'<br>';
		// $sql->Query (DB_NAME, $q);
		$ID_Array = explode (",", $ID_List);
		

		for ($i=0;$i<sizeof($ID_Array);$i++)
		{
			// loop over arrays & set stats
			//$inf = Set_Stat_1::Setup_Stats ($site_id, $vendor_id, $page_id, $Promo_Array[$i], $SubCode_Array[$i], $sql, $database_name, $promo_status);
			//Set_Stat_1::Set_Stat ($inf->block_id, $inf->tablename, $sql, $database_name, 'approved', $increment='1');
		}


	}else
	{
	// no recordset, email & exit
	// echo "query bad<BR>";
	}




?>
