<?php

	//============================================================
	// Vendor Promotions Cronjob for EOP Offers (on cash sites)
	// Runs Nightly - Grabs data from EOP tables where user
	// has selected "Phone" offer
	// Sends a CSV file to Selling Source FTP site
	// Vendor Promotions can log in and grab the file 
	// Updates eop_post table
	// - myya perez(myya.perez@thesellingsource.com), 02-02-2005
	//============================================================
	
	
	// INCLUDES / DEFINES
	//============================================================	

	require_once("mysql.3.php");
	require_once("diag.1.php");
	require_once("lib_mode.1.php");
	require_once("csv.1.php");
	require_once("prpc/client.php");
	require_once("ftp.2.php");
	
	$yesterday  = date("Y-m-d", strtotime("-1 day"));
	$today = date("Y-m-d");

	//testing values
	//$today = "2005-02-08";
	//$tomorrow  = "2005-02-09";
	
	$start 	= $yesterday." 00:00:00";
	$end = $today." 00:00:01";
	
	$app_id_list = 0;

	
	// SELECT DATA
	//============================================================
	
	$sql = new MySQL_3();
	$sql->Connect("BOTH", "selsds001", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__));

	$query_select = "	
		SELECT
			 eop_customers.app_id
			,eop_customers.ip
			,eop_customers.created_date
			,eop_customers.site
			,eop_customers.addl_info_serialized
			,eop_customers.fname
			,eop_customers.lname
			,eop_customers.email
			,eop_customers.phone_home
			,eop_customers.address1
			,eop_customers.address2
			,eop_customers.city
			,eop_customers.state
			,eop_customers.zip
		FROM
			eop_customers
			JOIN eop_selected USING (app_id)
		WHERE
			eop_customers.created_date BETWEEN '$start' AND '$end'
			AND eop_selected.selected_offer = 'Phone'
			AND eop_customers.fname !='test'
			AND eop_customers.lname !='test'
			AND eop_selected.vendor = ''
		";

	$result = $sql->Query("lead_generation", $query_select, Debug_1::Trace_Code(__FILE__,__LINE__));
	//$result = $sql->Query("rc_lead_generation", $query_select, Debug_1::Trace_Code(__FILE__,__LINE__));
		
	
	// CREATE CSV FILE
	//============================================================	
	
	$fields = array
	(
		 "APP_ID"
		,"FIRST_NAME"
		,"LAST_NAME"
		,"ADDRESS_1"
		,"ADDRESS_2"
		,"CITY"
		,"STATE"
		,"ZIP"
		,"PHONE"
		,"EMAIL"
		,"BANK_ABA"
		,"BANK_ACCOUNT"
		,"IP"
		,"REFERER"
		,"CREATED_DATE"
	);
	
	$path = "/tmp/";
	$filename = "vendorpromotions-EOP-".$yesterday;
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
		$post_array = unserialize($row['addl_info_serialized']);
		$bank_aba = $post_array['bank_aba'];
		$bank_account = $post_array['bank_account'];

		if (!$bank_aba || !$bank_account) {}
		else
		{
		
			$csv_array = array
			(
				 $row['app_id']
				,$row['fname']
				,$row['lname']
				,$row['address1']
				,$row['address2']
				,$row['city']
				,$row['state']
				,$row['zip']
				,$row['phone_home']
				,$row['email']
				,$bank_aba
				,$bank_account
				,$row['ip']
				,$row['site']
				,$row['created_date']
			);
				
			$csv->recordFromArray($csv_array);
			$app_id = $row['app_id'];
			$app_id_list.= ",$app_id";
		}
	}
	
	// small hack to make sure leading zeros dont get dropped when csv is opened in excel
	$csv->_buf = str_replace('"=','="',$csv->_buf);
	
	$app_id_list = substr($app_id_list, 2);
	$mycsv = $csv->_buf; 
	$csv->flush();
	$records = $csv->getRecordCount();
	
	if ($records == '0')
	{
		print "\r\nTHERE ARE NO RECORDS FOR TODAY\r\n";
	}
	else
	{
		//print "\r\nTOTAL RECORDS IN CSV: $records\r\n";
	
		// BUILD EMAIL
		//============================================================		
	
		$header = (object)array
		(
			"port"			 => 25,
			"url"			 => "maildataserver.com",
			"subject"		 => "Nightly EOP Records for Vendor Promotions",
			"sender_name"	 => "John Hawkins",
			"sender_address" => "john.hawkins@thesellingsource.com"
		);
		
	 	$recipient = array
	 	(
	 		(object)array("type" => "to", "name" => "Joseph", 	  "address" => "joseph@vendorpromotions.com"),
			(object)array("type" => "to", "name" => "Laura G.",   "address" => "laura.gharst@partnerweekly.com"),
			(object)array("type" => "to", "name" => "Programmer", "address" => "myya.perez@thesellingsource.com"),
	 	);
		
		$message = (object)array
		(
			"text" => "Your files have been uploaded to the ftp and are available for download. There were $records records from $yesterday..."
		);
		
		
		// FTP
		//============================================================	
		
		$ftp_client = new FTP();
		$ftp_client->server 	= "ftp.sellingsource.com";
		$ftp_client->user_name 	= "vendorpromotions";
		$ftp_client->user_password = "password";
		$ftp_client->do_Connect($ftp_client);
		$ftp_client->file = "$path_to_csvfile,/$csvfile";
		$ftp_client->put_File($ftp_client->file,true);
		
	
		// SEND EMAIL
		//============================================================
		
		$mail = new Prpc_Client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
		$mail_id = $mail->CreateMailing("TSS_VP_EOP_NIGHTLY_01", $header, NULL, NULL);
		$package_id = $mail->AddPackage($mail_id, $recipient, $message, array($attachment));
		$sender = $mail->SendMail($mail_id);
		
		print "\r\nEMAILS HAVE BEEN SENT.\r\n";

	}
	
	
	
	// UPDATE
	//============================================================	
	$query_update = "	
		UPDATE
			eop_selected
		SET
			vendor = 'VP'
			,result = 'Lead Sent'
		WHERE
			app_id IN (".$app_id_list.")
			AND selected_offer = 'Phone'
		";		
	
	$rs = $sql->Query("lead_generation", $query_update, Debug_1::Trace_Code(__FILE__,__LINE__));
	//$rs = $sql->Query("rc_lead_generation", $query_update, Debug_1::Trace_Code(__FILE__,__LINE__));
	


?>