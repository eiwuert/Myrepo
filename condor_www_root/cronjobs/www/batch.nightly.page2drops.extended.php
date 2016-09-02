<?php
	// ======================================================================
	// BATCH NIGHTLY PAGE2DROPS EXTENDED
	// Page2Drops => batch.nightly.page2drops.php
	// Page2Drops.extended => batch.nightly.page2drops.extended.php
	//
	// Webadmin Stat column - CT4U_P1_EX
	//
	// This is an extension of the page2drops cron...pulls from the same
	// tmptable but sends more information. Records that are sent are removed
	// from tmptable.
	//
	// myya.perez@thesellingsource.com 06-27-2005
	// ======================================================================
	
	
	// INCLUDES / DEFINES / INITIALIZE VARIABLES
	// ======================================================================

	require_once('mysql.3.php');
	require_once('debug.1.php');
	require_once('error.2.php');
	require_once('csv.1.php');
	require_once('ftp.2.php');
	require_once('hit_stats.1.php');
	require_once('lgen.record.1.php');
	require_once("HTTP/Request.php");// pear
	echo '<pre>';
	define('MAX_RECORDS', 100);
	define('LICENSE_KEY',  '3301577eb098835e4d771d4cceb6542b');
	define('STAT_COL', 'h12');	
	define('URL', 'http://www.cash2day4u.com/leadpost.php');
	//define('URL', 'http://test.ds28.tss/catch_post_data.php'); // testing

	$pay_map[WEEKLY] 		= "Weekly";
	$pay_map[TWICE_MONTHLY] = "Semi-Monthly";
	$pay_map[BI_WEEKLY] 	= "Bi-Weekly";
	$pay_map[MONTHLY] 		= "Monthly";
	
	// SQL CONNECT & QUERY
	// ======================================================================		
	
	$sql=new MySQL_3();
	$sql->connect("both","selsds001","sellingsource","%selling\$_db");	
	
	$query = "
		SELECT *
		FROM TmpTable0434
		WHERE ssn != ''
		AND dob != '0000-00-00'
		AND routing_number != ''
		AND pay_frequency != 'MONTHLY'
	";
	
	$result = $sql->query("lead_generation",$query);
	$count 	= $sql->Row_Count($result);
	
	print "\n\n RECORD COUNT: " .$count;
	
	
	// MANAGE RESULTS
	// ======================================================================		
	
	$cnt=0;
	while($row = $sql->Fetch_Array_Row($result))
	{
		if ( $cnt == MAX_RECORDS ) break;
		$resp = '';

		if ( Leadgen_Record::Check_BMG($sql,$row[email]) )
		{
			print "\n\nDropping {$row[app_id]} : BMG DUPE";
		}
		elseif ( Leadgen_Record::Check_DS($sql,$row[email]) )
		{
			print "\n\nDropping {$row[app_id]} : DS DUPE";
		}
		elseif ( Leadgen_Record::Check_CT($sql,$row[email]) )
		{
			print "\n\nDropping {$row[app_id]} : CT DUPE";
		}
		else 
		{
			print "\n\n" . date("H:i:s") . " - START CT SEND ";
			
			$fields = array 
			(
				"Source" => "Partner",
				"FirstName" => $row[first_name],
				"LastName" => $row[last_name],
				"Email" => $row[email],
				"HomePhone" => substr($row[home_phone],'0','3')."-".substr($row[home_phone],'3','3')."-".substr($row[home_phone],'6','4'),
				"WorkPhone" => substr($row[work_phone],'0','3')."-".substr($row[work_phone],'3','3')."-".substr($row[work_phone],'6','4'),
				"DirectDeposit" => "Yes",
				"DOB" => date("Y/m/d", strtotime($row['dob'])),
				"Addr" => $row[address_1],
				"City" => $row[city],
				"State" => $row[state],
				"Zip" => $row[zip],
				"Employer" => $row[employer_name],
				"MonthlyIncome" => $row[monthly_income],
				"PayPeriod" => $pay_map[$row[pay_frequency]],
				"RoutingNumber" => $row[routing_number]			
			);

			//print_r($fields);
			
			$net = new HTTP_Request(URL);
			$net->setMethod(HTTP_REQUEST_METHOD_POST);
			reset($fields);
			while (list($k, $v) = each($fields))
			{
				$net->addPostData($k, $v);
			}
			$net->sendRequest();
			$resp = $net->getResponseBody();
			
			print "\nRESPONSE: " . $resp ."  ". $row[app_id];

			if ( trim($resp) == "OK" )
			{
				Leadgen_Record::Record_CT($sql, $row[app_id], "SellingSourceHalf", $row->first_name, $row->last_name, $row->home_phone, $row->email);
				$cnt++;
			}
			
			print "\n" . date("H:i:s") . " - END CT SEND ";
		}
			
		if ( trim($resp) != "REJECTED" )
		{
			$delete_query = "
				DELETE FROM TmpTable0434
				WHERE app_id = '".mysql_escape_string($row[app_id])."'
			";
			$res = $sql->query("lead_generation",$delete_query);
		}			
	
	}
	
	
	// HIT STAT
	// ======================================================================		
	
	Hit::Stats_Promoless(LICENSE_KEY, $sql, STAT_COL, $cnt);	

	print "\n\n DONE AND DONE"

?>