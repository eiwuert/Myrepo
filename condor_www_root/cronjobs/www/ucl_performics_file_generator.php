<?php
	// Creates a tab delimited data file to be sent off file

	// Make sure we keep running even if user aborts
	ignore_user_abort (TRUE);

	// Let it run forever
	set_time_limit (0);
	
	define ('DB_HOST', 'www2.nationalmoneyonline.com');
	define ('DB_LOGIN', 'tss');
	define ('DB_PASS', '1954');
	
	// NEEDS TO BE SET
	define ('PERFORMICS_PROMO_ID', '10434');
	// define ('PERFORMICS_PROMO_ID', '10033');

	// server configuration
	require_once ("/virtualhosts/lib/mysql.3.php");
 
	$sql = new MySQL_3();
	$sql->Connect ('', DB_HOST, DB_LOGIN, DB_PASS);
	
	// do date calculations
	$yesterday = strtotime ("12 AM yesterday");
	$today = strtotime ("12 AM today");

	// Set query for the file.  We will need a count to include in the header
	$query  = "SELECT applications.application_id, fund_date, site_info.promo_sub_code ";
	$query .= "FROM applications, funded_tracking, site_info WHERE ";
	$query .= "fund_date >= ".date("YmdHis", $yesterday)." AND ";
	$query .= "fund_date < ".date("YmdHis", $today)." AND ";
	$query .= "funded_tracking.application_id=applications.application_id AND ";
	$query .= "site_info.application_id=applications.application_id AND ";
	$query .= "promo_id=".PERFORMICS_PROMO_ID;
	
	$result = $sql->Query ('ucl_agent', $query,  Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);

	// Performics header data
	
	// Upload Date
	$line = date('Ymd', $yesterday)."\r\n";
	
	// Version
	$line .= "instrns/1.3\r\n";
	
	// Action/Type
	$line .= "insact\r\n";
	
	// CC_Id
	$line .= "ccvidK27252\r\n";
	
	// Num_records
	$line .= $sql->Row_Count($result)."\r\n\r\n";

	
	// Get the transsaction data
	while ($data = $sql->Fetch_Object_Row ($result))
	{
		$year = substr ($data->fund_date, 0, 4);
		$mo = substr ($data->fund_date, 4, 2);
		$dy = substr ($data->fund_date, 6, 2);
		$hh = substr ($data->fund_date, 8, 2);
		$mm = substr ($data->fund_date, 10, 2);
		$ss = substr ($data->fund_date, 12, 2);
		$t_stamp = mktime ($hh, $mm, $ss, $mo, $dy, $year);
		
		// Performics transaction data
		
		// Date Time
		$line .= date('Ymd H:i:s', $t_stamp)."\t";
		
		// ORDER_ID
		$line .= trim ($data->application_id)."\t";
		
		// ORDER_AMT
		$line .= "0\t";
		
		// PRDSKU
		$line .= "\t";
		
		// PRDQN
		$line .= "1\t";
		
		// PRDPR
		$line .= "\t";
		
		// VARCM
		$line .= "\t";
		
		// SID
		$line .= trim ($data->promo_sub_code)."\t";
		
		// STATUS
		$line .= "N\r\n";
	}

	// if the line is LARGER than the headers (They are 146)
	if (strlen($line) > 20)
	{
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
			"Leads for ".date("Ymd", $yesterday).".txt\r\n".
			"--".$outer_boundry."\r\n".
			"Content-Type: text/plain;\r\n".
			" charset=\"us-ascii\";\r\n".
			" name=\"performics".date("Ymd", $yesterday)."\"\r\n".
			"Content-Transfer-Encoding: 7bit\r\n".
			"Content-Disposition: attachment; filename=\"performics".date("Ymd", $yesterday).".txt\"\r\n\r\n".
			$line."\r\n".
			"--".$outer_boundry."--\r\n\r\n";
	
		// Send the file to ed for processing
		mail ("davidb@sellingsource.com", "performics".date('Ymd', $yesterday).".txt", NULL, $batch_headers);
		mail ("inserttransaction@performics.com", "performics".date('Ymd', $yesterday).".txt", NULL, $batch_headers);
		mail ("johnh@sellingsource.com", "performics".date('Ymd', $yesterday).".txt", NULL, $batch_headers);
	}
?>
