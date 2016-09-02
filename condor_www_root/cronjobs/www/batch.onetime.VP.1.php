<?PHP
	// ======================================================================
	// Onetime Cron for catching missed days batch.onetime.VP.1.php
	//
	// VP Nightly 1 => batch.nightly.VP.1.php
	// VP Nightly 2 => batch.nightly.VP.2.php
	//
	// This used to be hawkins.session_harves.php...it was changed for BB2
	// Does not hit any stats
	//
	// myya.perez@thesellingsource.com 05-27-2005
	// ======================================================================
	
	
	// INCLUDES / DEFINES / INITIALIZE VARIABLES
	// ======================================================================

	require_once('lib_mode.1.php');
	require_once('diag.1.php');
	require_once('error.2.php');
	require_once('debug.1.php');
	require_once('mysql.3.php');
	require_once('hit_stats.1.php');
	require_once('csv.1.php');
	
	Diag::Enable();
	echo '<pre>';
	
	
	// SQL CONNECT & QUERY
	// ======================================================================		
	
	$sql=new MySQL_3();
	$sql->connect("both","selsds001","sellingsource","%selling\$_db");	
	
	
	// SEND_EMAIL FUNCTION
	//============================================================		
	
	function send_email($csv, $filename, $csvfile)
	{	
		$header = (object)array
		(
			"port"			 => 25,
			"url"			 => "sellingsource.com",
			"subject"		 => $filename,
			"sender_name"	 => "Selling Source",
			"sender_address" => "no-reply@sellingsource.com"
		);
		
		$recipient = array
	 	(
	 		(object)array("type" => "to", "name" => "Laura G.",   "address" => "laura.gharst@partnerweekly.com"),
			(object)array("type" => "to", "name" => "Vendor",  "address" 	=> "pwleads@19communications.com"),
			(object)array("type" => "to", "name" => "Programmer", "address" => "myya.perez@thesellingsource.com"),
	 	);	
		
	 	//$recipient = array((object)array("type" => "to", "name" => "Programmer", "address" => "myya.perez@thesellingsource.com"));		
		
		$message = (object)array
		(
			"text" => "Attached Report Files"
		);
			
		$attach = new stdClass ();
		$attach->name = $csvfile;
		$attach->content = base64_encode ($csv);
		$attach->content_type = "plain/text";
		$attach->content_length = strlen ($csv);
		$attach->encoded = "TRUE";		
		
		$mail = new Prpc_Client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
		$mail_id = $mail->CreateMailing("", $header, NULL, NULL);
		$package_id = $mail->AddPackage($mail_id, $recipient, $message, array ($attach));
		$sender = $mail->SendMail($mail_id);	
	
		print "\r\nEMAILS HAVE BEEN SENT.\r\n";		
	}	
	
	
	// VP_1 FUNCTION
	//============================================================	

	function VP_1($sql, $date_early, $date_late, $yesterday)
	{
		$query = "
		SELECT
			personal.first_name
			,personal.last_name
			,personal.home_phone
			,personal.email AS email
			,residence.address_1
			,residence.city
			,residence.state
			,residence.zip
			,application.created_date AS created
			,campaign_info.url AS signup_source	
			,campaign_info.ip_address	
		FROM 
			application,
			personal,
			residence,
			campaign_info
		WHERE application.created_date BETWEEN '$date_early' AND '$date_late'
		AND application.application_id = personal.application_id
		AND application.application_id = residence.application_id
		AND application.application_id = campaign_info.application_id
		AND	application.application_type != 'VISITOR'
		AND personal.first_name != ''
		AND personal.first_name IS NOT NULL
		AND personal.last_name != ''
		AND personal.last_name IS NOT NULL
		AND personal.home_phone != ''
		AND personal.home_phone IS NOT NULL	
		AND personal.email != ''
		AND personal.email IS NOT NULL
		AND residence.address_1 != ''
		AND residence.address_1 IS NOT NULL
		";
	
		$result = $sql->query("olp", $query);
		
		$result_count = $sql->Row_Count($result);
		print "\r\nResult Count - ".$result_count."\r\n";
	
		
		// CREATE_CSV
		//============================================================	
	
		$fields = array
		(
			"FNAME"
			,"LNAME"
			,"STREET"
			,"CITY"
			,"STATE"
			,"ZIP"
			,"PHONE"
			,"EMAIL"
			,"IP"
			,"REFERRER"
			,"CREATED"
		);
		
		$path = "/tmp/";
		$filename = "[".$yesterday."]-[".$result_count."]-VP-NIGHTLY-1";
		$csvfile = $filename.".csv";
		$path_to_csvfile = $path . $csvfile;
	
		$fp = fopen($path_to_csvfile, "w")
			or die("cannot open csv");
			
		$csv = new CSV
		(
			array
			(
				"flush" 		=> false, 
				"stream" 		=> $fp, 
				"forcequotes" 	=> false
			)
		);
		
		while ($row = $sql->Fetch_Array_Row($result))
		{
			//print_r($row);
			$csv_array = array
			(
				 $fname = strtoupper($row['first_name'])
				,$lname = strtoupper($row['last_name'])
				,$address = strtoupper($row['address_1'])
				,$city = strtoupper($row['city'])
				,$state = strtoupper($row['state'])
				,$row['zip']
				,$nphone = str_replace("-", "", $row['home_phone'])
				,$email = strtoupper($row['email'])
				,$row['ip_address']
				,$row['signup_source']
				,$row['created']
			);
	
			$csv->recordFromArray($csv_array);
		}
		
		$mycsv = $csv->_buf;
		$csv->flush();		
	
		send_email ($mycsv, $filename, $csvfile);
	
		print "\r\rDONE\r\r";
	}
	
	
	// CALL FUNCTION
	//============================================================		
	
	//VP_1($sql, "20050525000000", "20050525235959", "2005-05-25");
	VP_1($sql, "20050526000000", "20050526235959", "2005-05-26");
	VP_1($sql, "20050527000000", "20050527235959", "2005-05-27");
	VP_1($sql, "20050528000000", "20050528235959", "2005-05-28");
	VP_1($sql, "20050529000000", "20050529235959", "2005-05-29");
	VP_1($sql, "20050530000000", "20050530235959", "2005-05-30");

?>
