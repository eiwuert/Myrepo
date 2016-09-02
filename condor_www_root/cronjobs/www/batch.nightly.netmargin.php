<?PHP
	// ======================================================================
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
	//require_once('csv.2.php');
	//require_once('ftp.2.php');
	
	Diag::Enable();

	
	$date_early = date("YmdHis", strtotime("-7 days 0:00:00"));
	$date_late = date("YmdHis", strtotime("-7 days 23:59:59"));
	$yesterday = date("Y-m-d", strtotime("-7 days"));
	
	// SQL CONNECT & QUERY
	// ======================================================================		
	
	$sql=new MySQL_3();
	$sql->connect("both","olpslave.internal.clkonline.com","sellingsource","%selling\$_db");


	$query = "
	SELECT
		 p.first_name
		,p.last_name
		,p.home_phone
		,p.email AS email
		,p.date_of_birth
		,p.social_security_number
		,r.address_1
		,r.address_2
		,r.city
		,r.state
		,r.zip
		,app.created_date AS created
		,t.name
		,t.tier_id 
		,ci.url AS source	
		,ci.ip_address	
	FROM 
		application app use index (idx_created_data)
	JOIN personal p ON p.application_id = app.application_id
	JOIN residence r ON r.application_id = app.application_id
	JOIN campaign_info ci ON ci.application_id = app.application_id
	JOIN target t ON t.target_id = app.target_id 
	WHERE app.created_date BETWEEN '$date_early' AND '$date_late'
	AND ci.url IN ('universalpayday.com', 'paycheckcentral.com', 'northcash.com') 
	AND	app.application_type != 'VISITOR'
	AND p.first_name != ''
	AND p.first_name IS NOT NULL
	AND p.last_name != ''
	AND p.last_name IS NOT NULL
	AND p.home_phone != ''
	AND p.home_phone IS NOT NULL	
	AND p.email != ''
	AND p.email IS NOT NULL
	AND r.address_1 != ''
	AND r.address_1 IS NOT NULL
	";

	echo "Query: \n".$query."\n";

	$result = $sql->query("olp", $query);


	$result_count = $sql->Row_Count($result);
	print "\r\nResult Count - ".$result_count."\r\n";

	// is_funded
	// Check clk to see if this app has been funded.
	function is_funded($checkdate, $ssn)
	{
		// This connection for fund check
		$db = new MySQL_3();
		$db->connect("both","db1","sellingsource","%selling\$_db");
		$sql = "SELECT * from cashline_funded_log WHERE 
				social_security_number = ".$ssn." AND
				date_funded >= ".$checkdate;
		$result = $db->Query("clk_funded",$sql);
		if ($row = $db->Fetch_Array_Row($result))
		{
			//echo "Funded ".$row['date_funded']."\n";
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	// CREATE_CSV
	//============================================================	

	$fields = array
	(
		 "email"
		,"fname"
		,"lname"
		,"gender"
		,"address1"
		,"address2"
		,"city"
		,"state"
		,"zip"
		,"country"
		,"phone"
		,"source"
		,"sign_up_date"
		,"date_of_birth"
		,"ip"
	);
	
	$path = "/tmp/";
	$filename = $yesterday.$result_count."-NetMargin-NIGHTLY-1";
	$txtfile = $filename.".txt";
	$path_to_txtfile = $path . $txtfile;

	$rec_knt = 0;
	$inc_knt = 0;
	
	$fp = fopen($path_to_txtfile, "w")
		or die("cannot open csv");
		
	//$csv = new CSV
	//(
	//	array
	//	(
	//		"flush" 		=> TRUE, 
	//		"stream" 		=> $fp, 
	//		"forcequotes" 	=> FALSE,
	//		"separator"		=> '\t'
	//	)
	//);
	//$csv->setTitles($fields);

	$line = "email \t fname \t lname \t gender \t address1 \t address2 \t city \t state \t zip \t country \t phone \t source \t sign_up_date \t date_of_birth \t ip \n\r";
	fwrite($fp, $line);

	while ($row = $sql->Fetch_Array_Row($result))
	{
		//print_r($row);
		//echo "Row: ";var_dump($row);
		echo ".";
		$rec_knt++;
		if ($row["tier_id"] > 1)
		{
			//echo "not NMS ".$row['tier_id']." - ".$row['social_security_number']."\n";
		}
		if (!is_funded($row['created'],$row['social_security_number']))
		{
			//$csv_array = array
			//(
			$line = strtoupper($row['email'])."\t". 
				strtoupper($row['first_name'])."\t".
				strtoupper($row['last_name']). "\t".
				''. "\t".
				strtoupper($row['address_1']). "\t".
				strtoupper($row['address_2']). "\t".
				strtoupper($row['city']). "\t".
				strtoupper($row['state']). "\t".
				$row['zip']. "\t".
				''."\t".
				$row['home_phone']. "\t".
				$row['source']. "\t".
				formdate($row['created']). "\t".
				$row['date_of_birth']. "\t".
				$row['ip_address']."\n";
			
			echo "Line: ".$line."\n";
			
			fwrite($fp,$line);
			$inc_knt++;
			//$csv->recordFromArray($csv_array);
		}
		else
		{
			//echo "Funded ".$row['created']," - ",$row['social_security_number']."\n";
		}
		//die;
	}
	
	//$mycsv = $csv->_buf;
	//$csv->flush();
	fclose($fp);

	function formdate($datestamp)
	{
		if (!is_null($datestamp))
		{
			//echo "formdate: ".$datestamp."\n";
			return substr($datestamp,0,4)."-".substr($datestamp,4,2)."-".substr($datestamp,6,2);
		} else {
			return '';
		}
	}
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
	 		//(object)array("type" => "to", "name" => "Laura G.",   "address" => "laura.gharst@partnerweekly.com"),
			//(object)array("type" => "to", "name" => "Vendor",  "address" 	=> "pwleads@19communications.com"),
			(object)array("type" => "to", "name" => "Programmer", "address" => "keith.mcmillen@thesellingsource.com"),
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
	
	//send_email ($mycsv, $filename, $txtfile);

	echo "\nTotal: ".$rec_knt." included: ".$inc_knt."\n";

//Use sftp to send results to NetMargin

	$ftp_host="sftp.datranmedia.com";
	$ftp_user="sellingsource";
	$ftp_pass="ssqaz!@";
	$ftp_port = 22;
	$ftp_file=$path_to_txtfile;
	$sftp_methods = array (
        'kex' => 'diffie-hellman-group14-sha1,diffie-hellman-group-exchange-sha1,diffie-hellman-group1-sha1',
		'hostkey' => 'ssh-rsa,ssh-dss',
		'client_to_server' => array(
            'crypt' => 'aes256-cbc,aes192-cbci,blowfish-cbc,3des-cbcarcfour,cast128-cbc,aes128-cbc',
            'comp' => 'none'),
        'server_to_client' => array(
            'crypt' => 'aes256-cbc,aes192-cbc,blowfish-cbc,3des-cbc',
            'comp' => 'none'));
	$sftp_callback = array (
		'disconnect' => 'my_ssh_disconnect');
	
	if (!$conn = ssh2_connect($ftp_host,$ftp_port,$sftp_methods))
	{
		echo "\nError - could not connect to Host: ".$ftp_host."\n";
		exit;
	} else {
		echo "Connection: <pre>";var_dump($conn);
	}
	
	if (!$result = ssh2_auth_password($conn,$ftp_user,$ftp_pass))
	{
		echo "\nError - could not log in to host";
		ftp_quit($conn);
		exit;
	} else {
		echo "Logged in: \n";
	}
/*	
	if (!$sftp = ssh2_sftp($conn))
	{
		echo "\nError - Could not request sftp sub-system";
		exit;
	} else {
		echo "\nSub system requested \n";
	}
*/
	echo "Connected: \n";
	$response = ssh2_methods_negotiated($conn);
	var_dump($response);
	echo "\n";
	echo "Fingerprint: ".ssh2_fingerprint($conn)."\n";	
	$remote_file = $filename.".txt";
	
	echo "\nNOT Put to: ".$remote_file." - from: ".$ftp_file."\n";
	//echo "\nsftp: <pre>";var_dump($sftp);
		
	//echo "List: \n";
	//ssh2_exec($conn,"ls -l");
	/*
	if (ssh2_scp_send($conn,$ftp_file,$remote_file))
	{
		echo "\nCopied\n";
	} else {
		echo "\nNot Copied\n";
	}
	*/
	
	//$sftp_context = stream_context_create(array('ssh2'=>array('sftp'=>$sftp)));
	//echo "Context: ";var_dump($sftp_context);
	//fclose($fpr);
	//while (!feof($fp))
	//Hit::Stats_Promoless(LICENSE_KEY, $sql, STAT_COL, $result_count);

	print "\r\rDONE AND DONE\r\r";

?>
