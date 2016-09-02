<?php
	// ======================================================================
	// VP Nightly 2 => batch.nightly.VP.2.php
	// VP Nightly 1 => hawkins.session_harvest.php
	//
	// This requires that batch.nightly.page2drops.php TmpTable0434 be populated s
	//
	// myya.perez@thesellingsource.com 05-17-2005
	// ======================================================================
	
	
	// INCLUDES / DEFINES / INITIALIZE VARIABLES
	// ======================================================================

	require_once('mysql.3.php');
	require_once('csv.1.php');
	require_once('hit_stats.1.php');
	require_once('diag.1.php');
	require_once('error.2.php');
	require_once('debug.1.php');
	
	define('LICENSE_KEY',  '3301577eb098835e4d771d4cceb6542b');
	define('STAT_COL', 'h9');	
	
	$csv_records = array();
	$yesterday  = date("Y-m-d", strtotime("-1 day"));
	
	
	// SQL CONNECT & QUERY
	// ======================================================================		
	
	$sql=new MySQL_3();
	$sql->connect("both","selsds001","sellingsource","%selling\$_db");

	$query = 
	"
		SELECT 
			* 
		FROM 
			TmpTable0434 
		LIMIT 
			2000
	";

	$result = $sql->query("lead_generation", $query);
	
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
	$filename = "[".$yesterday."]-[".$result_count."]-VP-NIGHTLY-2";
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
			"forcequotes" 	=> true, 
			"header"		=> $fields
		)
	);
	
	while ($row = $sql->Fetch_Array_Row($result))
	{
		$csv_array = array
		(
			 '='.$fname = strtoupper($row['first_name'])
			,'='.$lname = strtoupper($row['last_name'])
			,$address = strtoupper($row['address_1'])
			,'='.$city = strtoupper($row['city'])
			,'='.$state = strtoupper($row['state'])
			,'='.$row['zip']
			,'='.$nphone = str_replace("-", "", $row['home_phone'])
			,'='.$email = strtoupper($row['email'])
			,'='.$row['ip_address']
			,'='.$row['signup_source']
			,'='.$row['created']
		);

		$csv->recordFromArray($csv_array);
	}
	
	$csv->_buf = str_replace('"=','="',$csv->_buf);
	$mycsv = $csv->_buf;
	$csv->flush();

	
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

	send_email ($mycsv, $filename, $csvfile);
		
	Hit::Stats_Promoless(LICENSE_KEY, $sql, STAT_COL, $result_count);

	print "\r\rDONE AND DONE\r\r";

	
?>