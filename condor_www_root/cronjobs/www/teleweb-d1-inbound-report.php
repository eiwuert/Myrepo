<?php
	// Mandatory fields
	require_once ("/virtualhosts/lib/mysql.3.php");
	
	// connect to the db
	$type = NULL;
	$host = "live01.tss";
	$login = "root";
	$password = "";
	$db = "teleweb";
	
	$sql = new MySQL_3 ();
	$result = $sql->Connect ($type, $host, $login, $password, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result);
	
	// A batch file to send the daily report
	$report_date = date ("Y-m-d", strtotime ("-1 day")); 
	$display_date = date ("m/d/Y", strtotime ("-1 day"));
	
	$query = "SELECT record_id, NULL as order_number, date_entered, time_entered, first_name, last_name, 
	address, address2, city, state, zip, home_phone, work_phone, fax, email, source, send_by 
	FROM data_collection where date_entered = '".$report_date."'";
	
	$result = $sql->Query ($db, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result);
	
	while (FALSE !== ($temp = $sql->Fetch_Object_Row ($result)))
	{
		unset ($line);
		
		foreach ($temp as $value)
		{
			$line .= "'".$value."',";
		}
		
		$result_set .= substr ($line, 0, -1)."\r\n";
	}
	
	// Send the stuff in an email
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
		//"Teleweb D1 Inbound Call Lifetime to ".$display_date."\r\n".
		"Teleweb - D1 - Inbound Call - ".$display_date."\r\n".
		"--".$outer_boundry."\r\n".
		"Content-Type: application/vnd.ms-excel;\r\n".
		" name=\"Teleweb - D1 - Inbound Call - ".$display_date.".csv\"\r\n".
		//" name=\"Teleweb - D1 - Inbound Call - lifetime to ".$display_date.".csv\"\r\n".
		"Content-Transfer-Encoding: quoted-printable\r\n".
		"Content-Disposition: attachment; filename=\"Teleweb - D1 - Inbound Call - ".$display_date.".csv\"\r\n\r\n".
		//"Content-Disposition: attachment; filename=\"Teleweb - D1 - Inbound Call - lifetime to ".$display_date.".csv\"\r\n\r\n".
		$result_set."\r\n".
		"--".$outer_boundry."--\r\n\r\n";

	// Send the file to ed for processing
	//mail ("rodricg@sellingsource.com", "Teleweb - D1 - Inbound Call - Lifetime to ".$display_date, NULL, $batch_headers);
	mail ("ecross@41cash.com, paulamc1107@hotmail.com, marketing@fc500.com, glennm@sellingsource.com", "Teleweb - D1 - Inbound Call - ".$display_date, NULL, $batch_headers);
	//mail ("ecross@41cash.com, paulamc1107@hotmail.com, marketing@fc500.com, glennm@sellingsource.com", "Teleweb - D1 - Inbound Call - Lifetime to ".$display_date, NULL, $batch_headers);
	//mail ("ecross@41cash.com, pmcreynolds@41cash.com, marketing@fc500.com, glennm@sellingsource.com", "Teleweb - D1 - Inbound Call - Lifetime to ".$display_date, NULL, $batch_headers);
	//mail ("pauls@sellingsource.com, cross_edward@hotmail.com, ecross41@41cash.com", "Teleweb - D1 - Inbound Call - ".$display_date, NULL, $batch_headers);
	//mail ("ecross@41cash.com", "Teleweb - D1 - Inbound Call - ".$display_date, NULL, $batch_headers);
	//Debug_1::Raw_Dump ($result_set);
?>
	
