<?php

	# ex: set ts=4:
	# descended from /vh/cronjobs/www/batch.onetime.bb.vendorpromotions.phone.php
	
	require_once("diag.1.php");
	require_once("lib_mode.1.php");
	require_once("mysql.3.php");
	require_once("csv.1.php");
	require_once("prpc/client.php");
	require_once("ftp.2.php");
	
	Diag::Enable();
	
	define("DEBUG",FALSE); # if this is on the script will *not* send an email to the customer
	define("DEBUG_EMAIL", "john.hargrove@thesellingsource.com");
	define("MAIL_SERVER","prpc://smtp.2.soapdataserver.com/smtp.1.php");
	define("DB_HOST",	"selsds001");
	define("DB_USER",	"sellingsource");
	define("DB_PASS",	"%selling\$_db");
	define("DB_NAME",	"lead_generation");
	define("LICENSE_KEY",	"3301577eb098835e4d771d4cceb6542b");
	
	$sql = new MySQL_3();
	$sql->Connect("BOTH", DB_HOST, DB_USER, DB_PASS, Debug_1::Trace_Code(__FILE__, __LINE__));

	
	$fields = array(
		"first_name"
		,"last_name"
		,"home_phone"
		,"email"
		,"address_1"
		,"address_2"
		,"apartment"
		,"city"
		,"state"
		,"zip"
		,"datestamp"
		,"ip_address"
		,"url"
	);
	
	$path = "/tmp/";
	$early_date = date("Y-m-d", strtotime("-14 days"));
	$late_date = date("Y-m-d", strtotime("-8 days"));
	if(!DEBUG) $filename = "vendorpromotions-weekly-".$early_date."-".$late_date;
	else $filename = "vendorpromotions-weekly-debug-".$early_date."-".$late_date;
	$zipfile = $filename.".zip";
	$csvfile = $filename.".csv";
	$path_to_zipfile = $path . $zipfile;
	$path_to_csvfile = $path . $csvfile;
	
	$fp = fopen($path_to_csvfile, "w")
		or die("cannot open csv");
	$csv = new CSV(
		array(
			"flush" => false # don't want to flush
			,"nl" => CSV_NL_WIN
			,"forcequotes" => true # looks prettier
			,"stream" => $fp
			,"titles" => $fields
		)
	);
	$cmd = "mysql -h " . DB_HOST . " -u " . DB_USER . " -p" . escapeshellcmd(DB_PASS) . " < " ."/virtualhosts/cronjobs/www/batch.onetime.vendorpromotions.400k.sql 2>>/tmp/err.batch.weekly.vp";
	
	Diag::Out("running: $cmd");
	
	# generate vp weekly data... this will take a while
	system($cmd);
	
	Diag::Out("done.");
	
	// interject here.. we need to toss these records into our "dupe" database.
	
	$query = "
		SELECT
			id,
			email,
			first_name,
			last_name,
			home_phone
		FROM vp_400k
		ORDER BY
			email ASC";
	
	$r = $sql->Query(DB_NAME, $query,Debug_1::Trace_Code(__FILE__,__LINE__));
	print "\r\n\r\nrecord count: ".$sql->Row_Count($r);
	$failedcount=0;
	$first_index=-1;
	
	while ( $rec = $sql->Fetch_Object_Row($r) )
	{
		$q = "
			INSERT INTO
				vp_sent
			
			(application_id,email_address,phone_number,first_name,last_name)
				
			VALUES(
				'{$rec->id}',
				'{$rec->email}',
				'{$rec->home_phone}',
				'{$rec->first_name}',
				'{$rec->last_name}'
				)";
		$rs = $sql->Query("lead_generation", $q, Debug_1::Trace_Code(__FILE__,__LINE__));
		if (is_a($rs,"Error_2") )
		{
			$failedcount++;
		}
		else
		{
			if ( $first_index==-1 )
				$first_index = $sql->Insert_Id();	
		}
		
	}

	print "\r\n\r\nTOTAL DUPLICATES: $failedcount\r\n";
	
	
	// drop it here.
	if ( $first_index==-1 )
	{
		print "\r\nError: No non-duplicate records detected. Aborting the sendmail.\r\n\r\n";
		exit;
	}
	
	
	// sneak in a record
	$sql->query("lead_generation","
			INSERT IGNORE INTO vp_sent
			SET application_id='76918',email_address='bigbrad_2004@yahoo.com',
			phone_number='7024337465', first_name='brad', last_name='orfall'");
	
	# build sql
	
	$query = "
	SELECT
		v.email,
		v.first_name,
		v.last_name,
		v.address_1,
		v.address_2,
		v.apartment,
		v.city,
		v.state,
		v.zip,
		v.home_phone,
		v.datestamp,
		v.ip_address,
		v.url
	FROM
		vp_sent d
	JOIN
		vp_400k v ON v.id=d.application_id
	WHERE
		d.id>='$first_index'
	ORDER BY
		email ASC
	";
	
	$rs = $sql->Query(
		"lead_generation"
		,$query
		,Debug_1::Trace_Code(__FILE__, __LINE__)
	);
	
	Error_2::Error_Test($rs, true);
	
	# read resultset into csv
	$csv->recordsFromWrapper($sql, $rs, $fields);
	
	$records = $csv->getRecordCount();
	
	print "\r\nTOTAL RECORDS IN CSV: $records\r\n";
	
	# write csv file to disk
	$csv->flush();


	# compress file
	system("zip $path_to_zipfile $path_to_csvfile");
	
	while (FALSE == $mail = new Prpc_Client(MAIL_SERVER))
	{
		if (DEBUG)
		{
			echo "Could not connect to '" . MAIL_SERVER . "'... retrying...\n";
		}	Lib_Mail::mail(DEBUG_EMAIL, "SendMail failed!", "");
		Lib_Mail::mail(DEBUG_EMAIL, "PRPC_Client(" . MAIL_SERVER . ") failed!", "");
	}
	
	// Build the header
	$header = (object)array(
		"port"		=> 25,
		"url"		=> "maildataserver.com",
		"subject"	=> "Weekly Records for Vendor Promotions",
		"sender_name"	=> "John Hawkins",
		"sender_address"=> "john.hawkins@thesellingsource.com"
	);
	
	// Build the recipient
	if (DEBUG)
	{
		$recipient = array(
			(object)array("type" => "to", "name" => "debugger", "address" => DEBUG_EMAIL)
		);
	}
	else
	{
		$recipient = array(
			(object)array("type" => "to", "name" => "Joseph", "address" => "joseph@vendorpromotions.com"),
			(object)array("type" => "bcc", "name" => "Programmer", "address" => DEBUG_EMAIL)
		);
		
		$r = new StdClass();
		$r->type = "To";
		$r->name = "Brian R.";
		$r->address = "brian.rauch@thesellingsource.com";
				
		$r0 = new StdClass();
		$r0->type = "To";
		$r0->name = "Pamela S.";
		$r0->address = "pamelas@partnerweekly.com";
		
		$r1 = new StdClass();
		$r1->type = "To";
		$r1->name = "Celeste C.";
		$r1->address = "celeste.christman@thesellingsource.com";
		
		$r2 = new StdClass();
		$r2->type = "To";
		$r2->name = "Laura G.";
		$r2->address = "laura.gharst@partnerweekly.com";
				
		$r3 = new StdClass();
		$r3->type = "To";
		$r3->name = "John Hawkins";
		$r3->address = "john.hawkins@thesellingsource.com";


		$recipients_b = array ($r,$r0,$r1,$r2,$r3);
	}
	
	$message = (object)array(
		"text" => "Your files have been uploaded to the ftp and are available for download. There were $records records from $early_date until $late_date..."
	);
	
	$message_r = (object)array(
		"text" => "Vendorpromotion's Weekly leads have been sent. There were $records records from $early_date until $late_date..."
	);
	

	
	$ftp_client = new FTP();
	$ftp_client->server = "ftp.sellingsource.com";
	$ftp_client->user_name = "vendorpromotions";
	$ftp_client->user_password = "password";
	
	$ftp_client->do_Connect($ftp_client);
	
	$ftp_client->file = "$path_to_zipfile,/$zipfile";
	
	if (!$ftp_client->put_File($ftp_client->file,true))
	{
		print "\nftp upload failed hardcore.";
	}
	
	

	while (FALSE == ($mail_id = $mail->CreateMailing("TSS_VP_WEEKLY_01", $header, NULL, NULL)))
	{
		Lib_Mail::mail(DEBUG_EMAIL, "Create_Mailing failed!", "");
	}
	
	if (DEBUG)
	{
		echo "mail_id: $mail_id\n";
	}
	
	while (FALSE == ($package_id = $mail->AddPackage($mail_id, $recipient, $message, array($attachment))))
	{
		Lib_Mail::mail(DEBUG_EMAIL, "Add_Package failed!", "");
	}
	
	if (DEBUG)
	{
		echo "package_id: $package_id\n";
	}
	
	while (FALSE == ($package_id = $mail->AddPackage($mail_id, $recipients_b, $message_r)))
	{
		Lib_Mail::mail(DEBUG_EMAIL, "Add_Package failed on report email!", "");
	}
	if (DEBUG)
	{
		echo "package_id_b: $package_id\n";
	}
	
	while (FALSE == ($sender = $mail->SendMail($mail_id)))
	{
		Lib_Mail::mail(DEBUG_EMAIL, "SendMail failed!", "");
	}
	
	if (DEBUG)
	{
		echo "sender: $sender\n";
	}
	
	Diag::Out("done and... done.");
?>