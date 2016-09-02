<?php
	
	/*
	vim: set ts=8:
	*/
	define("DEBUG_EMAIL", "john.hargrove@thesellingsource.com");
	//define("DEBUG_EMAIL", "doug.harris@thesellingsource.com");
	//define("DEBUG_EMAIL", "ryanf@sellingsource.com");
	
	define("MAIL_SERVER","prpc://smtp.2.soapdataserver.com/smtp.1.php");
	
	define("METAREWARD_XMIT_METHOD", "METAREWARD");
	
	require_once("lib_mode.1.php");
	require_once("diag.1.php");
	require_once("csv.1.php");
	require_once("mysql.3.php");
	require_once("prpc/client.php");
	
	switch (Lib_Mode::Get_Mode())
	{
	case MODE_LIVE:
		define("DEBUG", false);
		define("DB_HOST", "selsds001");
		define("DB_USER", "sellingsource");
		define("DB_PASS", "%selling\$_db");
		define("DB_NAME", "emv_visitor");
		break;
	case MODE_RC:
		define("DEBUG", false);
		define("DB_HOST", "selsds001");
		define("DB_USER", "sellingsource");
		define("DB_PASS", "%selling\$_db");
		define("DB_NAME", "rc_emv_visitor");
		break;
	case MODE_LOCAL:
		define("DEBUG", true);
		Diag::Enable();
		define("DB_HOST", "localhost");
		define("DB_USER", "root");
		define("DB_PASS", "");
		define("DB_NAME", "emv_visitor");
		break;
	}
	
	#Diag::Out("DB_HOST: " . DB_HOST . ", DB_USER: " . DB_USER . ", DB_PASS: " . DB_PASS);
	
	/****************************** define functions ***************************/
		
	function get_last(&$sql)
	{
	
		$last = (object)array("id" => 0, "updated" => 0);
	
		$query = "
		SELECT
			last_id AS id
			,UNIX_TIMESTAMP(updated) AS updated
		FROM
			log_users_batched
		ORDER BY
			last_id DESC
		LIMIT
			1
	";
	
		Diag::Out("query $query");
		
		if (!DEBUG)
		{
			$rs = $sql->Query(
				DB_NAME
				,$query
				,Debug_1::Trace_Code(__FILE__, __LINE__)
			);
	
			Error_2::Error_Test($rs, TRUE);
	
			$last = $sql->Fetch_Object_Row($rs);
		}
	
		return $last;
	}
	
	function save_point(&$sql, $count, $high_id)
	{
		$query = "
	INSERT INTO log_users_batched (
		id
		,updated
		,records_sent
		,last_id
	) VALUES (
		NULL
		,NOW()
		," . intval($count) ."
		," . intval($high_id) . "
	)";
	
		Diag::Out($query, "query");
		
		if (!DEBUG)
		{
			$rs = $sql->Query(
				DB_NAME
				,$query
				,Debug_1::Trace_Code(__FILE__, __LINE__)
			);
			Error_2::Error_Test($rs, TRUE);
		}
	
	}
	
	function send_mail($csv, $last)
	{
	
		// if we didn't get anything back then tell us for debug, but email empty record anyways
		// because otherwise the client will think our script is messed up when in fact it may not be the case
		if (0 == $csv->getRecordCount())
		{
			Diag::Out("no records found!");
		}
	
		$mail = new Prpc_Client(MAIL_SERVER);
		
		// Build the header
		$header = (object)array(
			"port"		=> 25,
			"url"		=> "maildataserver.com",
			"subject"	=> "This week's MetaReward acknowledgements",
			"sender_name"	=> "No One",
			"sender_address"=> "noreply@maildataserver.com"
		);
		
		// Build the recipient
		if (DEBUG)
		{
			$recipient = array(
				(object)array(
					"type" => "to"
					,"name" => "debugger"
					,"address" => DEBUG_EMAIL
				)
			);
		}
		else
		{
			$recipient = array(
				(object)array(
					"type" => "to"
					,"name" => "The Chipster"
					,"address" => "john.hargrove@thesellingsource.com" //"chips@emarketventures.com"
				)
			);
		}
	
		$data = $csv->_buf;
	
		Diag::Out("data: $data");
		
		$message = (object)array(
			"text" => $csv->getRecordCount() . " records" .
				($last->id ? " from " . date("l, F jS Y H:i:s T", $last->updated) . " to " : " through ") .
				date("l, F jS Y H:i:s T")
		);
		
		$attachment = (object)array(
			"name" => "metareward-acks-" . date("Y-m-d") . ".csv"
			,"content" => base64_encode($data)
			,"content_type" => "text/csv"
			,"content_length" => strlen($data)
			,"encoded" => true
		);
		
		while (false == ($mail_id = $mail->CreateMailing("EMV_GASREW_USER_BATCH", $header, NULL, NULL)))
		{
			Diag::Out("failed to create mailing, trying again...");
			sleep(3);
		}
	
		echo "mail_id: $mail_id";
		
		while (false == ($package_id = $mail->AddPackage($mail_id, $recipient, $message, array($attachment))))
		{
			Diag::Out("AddPackage failed!");
			sleep(3);
		}
	
		Diag::Out("package_id: $package_id");
		
		while (false == ($sender = $mail->SendMail($mail_id)))
		{
			Diag::Out("SendMail failed!");
			sleep(3);
		}
	
		Diag::Out("sender: $sender");
	
		return $mail_id;
	
	}

	///////////////// BEGIN ACTUAL CODE
	
	
	$sql = new MySQL_3();
	$sql->Connect("BOTH", DB_HOST, DB_USER, DB_PASS, Debug_1::Trace_Code(__FILE__, __LINE__));
	
	Diag::Dump($sql, "sql");
	
	$last = get_last($sql);
	
	Diag::Dump($last, "last");
	
	# now 
	$query = "
	SELECT
		u.site_id
		,u.user_id
		,u.email
		,u.name_first
		,u.name_last
		,u.address_street
		,u.address_unit
		,u.city
		,u.state
		,u.zip_code
		,CONCAT_WS('-', u.phone_areacode, u.phone_prefix, u.phone_suffix)	AS phone
		,u.birth_date
		,tr.processed_date
	FROM
		user u
	INNER JOIN
		transmission_register tr
	ON
		u.user_id = tr.user_id
	AND
		tr.xmit_method_name = '" . mysql_escape_string(METAREWARD_XMIT_METHOD) . "'
	WHERE
		u.user_id > " . intval($last->id) . "
	AND
		(tr.processed_date is not null AND tr.processed_date <> 0)
	AND
		u.active_flag = 'Y'
	ORDER BY
		u.site_id, u.user_id
	";
	
	Diag::Out("query: $query");
	
	$rs = $sql->Query(
		DB_NAME
		,$query
		,Debug_1::Trace_Code(__FILE__, __LINE__)
	);
	
	Error_2::Error_Test($rs, TRUE);
	
	$fp = fopen("/tmp/". "metareward-acks-" . date("Y-m-d") . ".csv","w");
		
	$csv = new CSV(array("FLUSH" => false, "STREAM"=>$fp));
	$csv->setTitles(array("site_id","user_id","email","name_first","name_last","address_street",
		"address_unit","city","state","zip","phone","dob","processed"));
	$high_id = 0;
	while (false !== ($rec = $sql->Fetch_Array_Row($rs)))
	{
		if ($rec["user_id"] > $high_id)
		{
			$high_id = $rec["user_id"];
		}
		$csv->recordFromArray($rec);
	}
	
	# release resultset
	$sql->Free_Result($rs);
	
	Diag::Dump($csv);
	
	// save our point, even if we retrieved 0 records
	save_point($sql, $csv->getRecordCount(), $high_id);
	

	
	//fclose($fp);
	send_mail($csv, $last);

	// write to disk..
	$csv->flush();
		
?>
