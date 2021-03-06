<?php

	// This file has been automatically generated by DataGrabber(tm)
	require_once('mysql.3.php');
	require_once('debug.1.php');
	require_once('error.2.php');
	require_once('csv.1.php');
	require_once('ftp.2.php');
	require_once('lgen.record.1.php');
	require_once('HTTP/Request.php');
	
	define( 'MODE_NORMAL', 0 );
	define( 'MODE_LEGACY', 1 );
	define( 'MODE_TEST', 2 );
	
	if ( $argc == 2 && $argv[1] == "LEGACY" )
	{
		define( 'MODE', MODE_LEGACY ); 
	}
	else if ( $argc == 2 && $argv[1] == "TEST" )
	{
		define( 'MODE', MODE_TEST );
	}
	else
	{
		define( 'MODE', MODE_NORMAL );
	}
	
	define ( 'CAMPAIGN_ID', 34632 );
	define ( 'URL', "http://registration.vendaregroup.com/import.aspx" );

	$SQL=new MySQL_3();
	$cx=$SQL->connect("both","selsds001","sellingsource","%selling\$_db",Debug_1::Trace_Code(__FILE__,__LINE__));
	Error_2::Error_Test($cx,TRUE);
	$total_records=0;
	$total_bad=0;
	$bad_funded=0;
	$bad_unique=0;
	$bad_scrubbed=0;
	
	if ( MODE == MODE_LEGACY )
	{
		$st = date("Y-m-d", strtotime("-30 days"));
		$end = date("Y-m-d");
		$fn = "VENDARE_CASH_LIST_LEGACY.TXT";
	}
	else if ( MODE == MODE_NORMAL || MODE==MODE_TEST )
	{
		$st = date("Y-m-d", strtotime("-1 day"));
		$end = date("Y-m-d");
		$fn = "VENDARE_CASH_LIST_$st.TXT";		
	}
		
	$_TmpTable0486 = $SQL->query("lead_generation","
	CREATE TABLE `TmpTable0486` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`modified` timestamp(14) NOT NULL,
	`application_id` int(10) unsigned,
	`created_date` timestamp(14) NOT NULL DEFAULT '00000000000000',
	`first_name` varchar(50) NOT NULL DEFAULT '',
	`last_name` varchar(50) NOT NULL DEFAULT '',
	`email` varchar(100) NOT NULL DEFAULT '',
	`ip_address` varchar(15) NOT NULL DEFAULT '',
	`url` varchar(250) NOT NULL DEFAULT '',
	PRIMARY KEY(`id`),
	UNIQUE KEY `email` (`email`)
	) TYPE=MyISAM;
	", Debug_1::Trace_Code(__FILE__,__LINE__));
	
	$SQL->Query("lead_generation", "TRUNCATE TmpTable0486");
	
	
	$Result_olp_bb_visitor=$SQL->query("olp_bb_visitor","SELECT
		application.application_id AS application_id,
		application.created_date AS created_date,
		personal.first_name AS first_name,
		personal.last_name AS last_name,
		personal.email AS email,
		campaign_info.ip_address AS ip_address,
		campaign_info.url AS url,
		DATE_FORMAT(application.created_date,'%m/%d/%Y') as cdt
		FROM application
		JOIN personal ON (application.application_id=personal.application_id)
		JOIN campaign_info ON (application.application_id=campaign_info.application_id)
		WHERE
		application.created_date BETWEEN '$st' AND '$end'
		AND application.application_id!=''
		AND personal.first_name!=''
		AND personal.first_name NOT LIKE '%TEST'
		AND personal.first_name NOT LIKE 'TEST%'
		AND personal.first_name NOT LIKE '%SHIT%'
		AND personal.first_name NOT LIKE '%SPAM%'
		AND personal.first_name NOT LIKE '%FUCK%'
		AND personal.first_name NOT LIKE '%BITCH%'
		AND personal.last_name!=''
		AND personal.last_name NOT LIKE '%TEST'
		AND personal.last_name NOT LIKE 'TEST%'
		AND personal.last_name NOT LIKE '%SHIT%'
		AND personal.last_name NOT LIKE '%SPAM%'
		AND personal.last_name NOT LIKE '%FUCK%'
		AND personal.last_name NOT LIKE '%BITCH%'
		AND personal.email!=''
		AND personal.email NOT LIKE '%TEST'
		AND personal.email NOT LIKE 'TEST%'
		AND personal.email NOT LIKE '%SHIT%'
		AND personal.email NOT LIKE '%SPAM%'
		AND personal.email NOT LIKE '%FUCK%'
		AND personal.email NOT LIKE '%BITCH%'
		AND personal.email NOT LIKE '%ABUSE'
		AND personal.email NOT LIKE '%INTERNIC%'
		AND personal.email NOT LIKE '%NETWORKSOLUTIONS%'
		AND personal.email NOT LIKE '%TSSMASTERD%'
		AND campaign_info.ip_address!=''
		AND campaign_info.url!=''",Debug_1::Trace_Code(__FILE__,__LINE__));
	Error_2::Error_Test($Result_olp_bb_visitor,TRUE);
	print "\nolp_bb_visitor count - ". 
	$SQL->Row_Count($Result_olp_bb_visitor);
	$total_records+=$SQL->Row_Count($Result_olp_bb_visitor);
	while($object=$SQL->Fetch_Object_Row($Result_olp_bb_visitor))
	{
		$object_collection[] = $object;
	}
	$Result_olp_bb_partial=$SQL->query("olp_bb_partial","SELECT
		campaign_info.application_id AS application_id,
		campaign_info.modified_date AS created_date,
		personal.first_name AS first_name,
		personal.last_name AS last_name,
		personal.email AS email,
		campaign_info.ip_address AS ip_address,
		campaign_info.url AS url,
		DATE_FORMAT(campaign_info.modified_date,'%m/%d/%Y') as cdt
		FROM campaign_info
		JOIN personal ON (campaign_info.application_id=personal.application_id)
		WHERE
		campaign_info.modified_date BETWEEN '$st' AND '$end'
		AND campaign_info.application_id != 0
		AND personal.first_name!=''
		AND personal.first_name NOT LIKE '%TEST'
		AND personal.first_name NOT LIKE 'TEST%'
		AND personal.first_name NOT LIKE '%SHIT%'
		AND personal.first_name NOT LIKE '%SPAM%'
		AND personal.first_name NOT LIKE '%FUCK%'
		AND personal.first_name NOT LIKE '%BITCH%'
		AND personal.last_name!=''
		AND personal.last_name NOT LIKE '%TEST'
		AND personal.last_name NOT LIKE 'TEST%'
		AND personal.last_name NOT LIKE '%SHIT%'
		AND personal.last_name NOT LIKE '%SPAM%'
		AND personal.last_name NOT LIKE '%FUCK%'
		AND personal.last_name NOT LIKE '%BITCH%'
		AND personal.email!=''
		AND personal.email NOT LIKE '%TEST'
		AND personal.email NOT LIKE 'TEST%'
		AND personal.email NOT LIKE '%SHIT%'
		AND personal.email NOT LIKE '%SPAM%'
		AND personal.email NOT LIKE '%FUCK%'
		AND personal.email NOT LIKE '%BITCH%'
		AND personal.email NOT LIKE '%ABUSE'
		AND personal.email NOT LIKE '%INTERNIC%'
		AND personal.email NOT LIKE '%NETWORKSOLUTIONS%'
		AND personal.email NOT LIKE '%TSSMASTERD%'
		AND campaign_info.ip_address!=''
		AND campaign_info.url!=''",Debug_1::Trace_Code(__FILE__,__LINE__));
	Error_2::Error_Test($Result_olp_bb_partial,TRUE);
	print "\nolp_bb_partial count - ". 
	$SQL->Row_Count($Result_olp_bb_partial);
	$total_records+=$SQL->Row_Count($Result_olp_bb_partial);
	while($object=$SQL->Fetch_Object_Row($Result_olp_bb_partial))
	{
		$object_collection[] = $object;
	}
	foreach($object_collection as $k=>$object_row){
		
		if ( Leadgen_Record::Check_Vendare ( $SQL, $object_row->email ) )
		{
			unset($object_collection[$k]);
			print "\nRemoved: Leadgen_Record::Check_Vendare() : Bounced ({$object_row->email})";
			$bad_scrubbed++;
			continue;
		}
		
		$ires=$SQL->query("lead_generation","INSERT IGNORE INTO TmpTable0486 
		SET application_id='".mysql_escape_string($object_row->application_id)."',
		created_date='".mysql_escape_string($object_row->created_date)."',
		first_name='".mysql_escape_string($object_row->first_name)."',
		last_name='".mysql_escape_string($object_row->last_name)."',
		email='".mysql_escape_string($object_row->email)."',
		ip_address='".mysql_escape_string($object_row->ip_address)."',
		url='".mysql_escape_string($object_row->url)."'",Debug_1::Trace_Code(__FILE__,__LINE__));
		if($SQL->Affected_Row_Count($ires)==0)
		{
			unset($object_collection[$k]);
			print "\nRemoved: Not Unique: {$object_row->email}";
			$bad_unique++;
		}
		if ( MODE == MODE_NORMAL )
		{
			Leadgen_Record::Record_Vendare($SQL, 
				$object_row->application_id, 
				"CASHADVANCE_" . MODE, 
				$object_row->first_name,
				$object_row->last_name,
				$object_row->home_phone,
				$object_row->email);
		}
	}
	
				
	$total_bad=$bad_funded+$bad_unique+$bad_scrubbed;
	$results="

Removed (funded): $bad_funded
Removed (scrubbed): $bad_scrubbed
Removed (duplicate): $bad_unique
Total Removed: $total_bad 
Total Records: $total_records good records - $total_bad bad records = ".($total_records-$total_bad)." usable records";print"\n\n";
	print $results;
	print "\n\nFinalizing...";
	
	$usable_count = $total_records-$total_bad;

	
	$headers = array ("APPLICATION_ID","CREATED_DATE","FIRST_NAME","LAST_NAME","EMAIL","IP_ADDRESS","URL");
	print "\n\nGenerating CSV File '/tmp/$fn'....

";
	$fp_csv = fopen("/tmp/$fn","w");

	$cnt=0;
	foreach($object_collection as $row)
	{
		fwrite($fp_csv,"{$row->email}\t{$row->first_name}\t{$row->last_name}\t{$row->ip_address}\t{$row->url}\t{$row->cdt}\r\n");
		if ( MODE == MODE_NORMAL || MODE == MODE_TEST )
		{
			$fields = array 
					(
					"ac"		=> CAMPAIGN_ID,
					"vs"		=> $row->url,
					"ip"		=> $row->ip_address,
					"ad"		=> $row->cdt,
					"email"		=> $row->email,
					"firstname"	=> $row->first_name,
					"lastname"	=> $row->last_name						
					);
			
			
			$net = new HTTP_Request(URL);
			$net->setMethod(HTTP_REQUEST_METHOD_POST);
			
			foreach ( $fields as $k=>$v )
				$net->addPostData($k, $v);
					
			print "\nVENDARE SEND {$row->email} ... ";
			$net->sendRequest();
			print "OK";
			$cnt++;
			if ( MODE == MODE_TEST && $cnt==5 )
			{
				break;
			}
		}
	}		
	
	fclose($fp_csv);
	
	print "Done.";			
		$email_port = 25;
		$email_url = "sellingsource.com";
		$email_s_name = "DataGrubber";
		$email_s_address = "data-grubber-noreply@thesellingsource.com";
		
		// Build Email Header
		$header = new StdClass ();
		$header->smtp_server = $email_smtp_server;
		$header->port = $email_port;
		$header->url = $email_url;
		$header->subject = "$fn";
		$header->sender_name = $email_s_name;
		$header->sender_address = $email_s_address;
		
		if ( MODE == MODE_LEGACY )
		{				
			//ftp delivery
			$ftp_client = new FTP();
			$ftp_client->server = "ftp.sellingsource.com";
			$ftp_client->user_name = "vendare";
			$ftp_client->user_password = "password";
			print "
	Connecting to VENDARE FTP Site ... ";				
			$ftp_client->do_Connect($ftp_client);
			
			print "OK";
			
			print "
	Uploading File ... ";					
			$ftp_client->file = 
				
				array(
					"/tmp/$fn,/$fn"
				);
			
			
			if (!$ftp_client->do_Put($ftp_client, true, false))
			{
				print "FAILED";	
			}
			else print "OK";
		}
		
		$recipients = array();
		$recipient = new StdClass ();
		$recipient->type = "To";
		$recipient->name = "john.hargrove@thesellingsource.com";
		$recipient->address = "john.hargrove@thesellingsource.com";
		$recipients[] = $recipient;
		$message = new StdClass ();
		$message->text = "
		
		DataGrubber Run #0486		
		
		$results
		
			This is an automatic email generated by TSSDataGrubber; do not reply.  If you have any questions, please contact john.hargrove@thesellingsource.com. Thank you.
		";
					
		//email delivery
		// Create the Mail Object and Send the Mail	
		include_once("prpc/client.php");
		$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
			
		// Key Line - Create the mailing (Name of mailing, headers, scheduled date, scheduled time) DO NOT USE SCHEDULING!!!
		$mailing_id = $mail->CreateMailing ("datagrubber_run", $header, NULL, NULL);
	
		// Key Line - Add the package to the mailing (mailing_id, array of recipients, message, array of attachments)
		$package_id = $mail->AddPackage ($mailing_id, $recipients, $message, NULL);

		// Key Line - Tell the server to process the mailing (send all emails)
		$result = $mail->SendMail ($mailing_id);
	
		// Debug Code - Use if you want to see the soap stuff
		// print_r ($mail->__get_wire ());
		echo " ... Report Mailing Id: ".$mailing_id."
";
		echo " ... Result: ".$result."\n";
		echo " ... Recipients: 
";	
	print "Batch Completed Successfully"

?>