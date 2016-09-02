<?php
	// ======================================================================
	// FREE CREDIT REPORT OFFER - batch.nightly.vp.fcr.php
	//
	// Ggrabs records from EOP and sends to VP
	// Modification - two promo id's are pulled out and sent to VP seperately
	//
	// myya.perez@thesellingsource.com 06-01-2005 - mods and cleaup
	// ======================================================================
	
	
	// INCLUDES / DEFINES / INITIALIZE VARIABLES
	// ======================================================================
	
	require_once('mysql.3.php');
	require_once('debug.1.php');
	require_once('error.2.php');
	require_once('csv.1.php');
	require_once('ftp.2.php');
	include_once("prpc/client.php");
	include_once("pay_date_calc.1.php");
	//echo '<pre>';

	$filename = "SellingSourceEnrollment_" . date("Ymd", strtotime("-1 day")) . "_EOP.txt";
	

	// CONNECT & QUERY
	//============================================================	
	
	$sql = new MySQL_3();
	$sql->Connect("BOTH", "selsds001", "sellingsource", "%selling\$_db");
	
		
	// EOP START
	//============================================================

	// define variables
	$yesterday  = date("Y-m-d", strtotime("-1 day"));
	$today 		= date("Y-m-d");

	$start 	= $yesterday." 00:00:00";
	$end 	= $today." 00:00:01";
	
	//$start = "2005-08-16 00:00:00";
	//$end   = "2005-08-17 00:00:01";

	
	$app_id_list = 0;
	
	// select data
	$query_select = "	
		SELECT
			 eop_customers.app_id
			,eop_customers.promo_id
			,eop_customers.ip
			,eop_customers.tier
			,eop_customers.created_date
			,eop_customers.site
			,eop_customers.addl_info_serialized
			,eop_customers.fname
			,eop_customers.lname
			,eop_customers.email
			,REPLACE(eop_customers.phone_home,'-','') phone_home
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
			AND eop_selected.selected_offer = 'CreditReport'
			AND eop_customers.fname !='test'
			AND eop_customers.lname !='test'
			AND eop_selected.vendor = ''
		";

	$res = $sql->Query("lead_generation",$query_select, Debug_1::Trace_Code(__FILE__,__LINE__));
	Error_2::Error_Test($res, TRUE);
	
	//$res = $sql->Query("rc_lead_generation",$query_select, Debug_1::Trace_Code(__FILE__,__LINE__));
	//Error_2::Error_Test($res, TRUE);	
	
	$EOP_count=0;
	// format data and place in object_collection
	while ( $row =  $sql->Fetch_Array_Row($res) ) 
	{
		$query_olp = "	
			SELECT * 
			FROM income i
			JOIN paydate p ON i.application_id = p.application_id
			WHERE i.application_id = '".$row['app_id']."'
			LIMIT 1
		";
	
		$rs = $sql->Query("olp",$query_olp, Debug_1::Trace_Code(__FILE__,__LINE__));
		Error_2::Error_Test($rs, TRUE);		

		$olp_info = $sql->Fetch_Array_Row($rs);
		
		$post_array = unserialize($row['addl_info_serialized']);
		//print_r($post_array);
		$replace = array("http://","www.");
		$site = str_replace($replace, "", $row['site']);
		$cdf = str_replace(" ", "T", $row['created_date']);
		
		$new = new StdClass();
		
		$new->application_id = $row['app_id'];
		$new->created_date = $row['created_date'];
		$new->promo_id = $row['promo_id'];
		$new->first_name = $row['fname'];
		$new->middle_name = $post_array['middle_name'];
		$new->last_name = $row['lname'];
		$new->address_1 = $row['address1'].' '.$row['address2'];
		$new->city = $row['city'];
		$new->state = $row['state'];
		$new->zip = $row['zip'];
		$new->ssn = $post_array['ssn_part_1'].$post_array['ssn_part_2'].$post_array['ssn_part_3'];
		$new->home_phone = $row['phone_home'];
		$new->dobf = $post_array['date_dob_y'].'-'.$post_array['date_dob_m'].'-'.$post_array['date_dob_d'];
		$new->email = $row['email'];
		$new->url = $site;
		$new->paydate_model_id = $olp_info['paydate_model_id'];
		$new->income_frequency = $olp_info['pay_frequency'];
		$new->day_of_week = $olp_info['day_of_week'];
		$new->next_paydate = $olp_info['next_paydate'];
		$new->day_of_month_1 = $olp_info['day_of_month_1'];
		$new->day_of_month_2 = $olp_info['day_of_month_2'];
		$new->week_1 = $olp_info['week_1'];
		$new->week_2 = $olp_info['week_2'];
		$new->ip_address = $row['ip'];
		$new->cdf = $cdf;
		$new->bank_routing_number = $post_array['bank_aba'];
		$new->bank_account_number = $post_array['bank_account'];
		$new->bank_account_type = $post_array['bank_account_type']{0};

		$object_collection[] = $new;
		$app_id = $row['app_id'];
		$app_id_list.= ",$app_id";
		$EOP_count++;
	}
	
	//echo '<br>'.$EOP_count.'<br>';
	
	// update
	$query_update = "	
		UPDATE
			eop_selected
		SET
			 vendor = 'VP2'
			,result = 'Lead Sent'
		WHERE
			app_id IN (".$app_id_list.")
			AND selected_offer = 'CreditReport'
		";		
	
	
	$res = $sql->Query("lead_generation",$query_update, Debug_1::Trace_Code(__FILE__,__LINE__));
	Error_2::Error_Test($res, TRUE);
	
	//$res = $sql->Query("rc_lead_generation",$query_update, Debug_1::Trace_Code(__FILE__,__LINE__));
	//Error_2::Error_Test($res, TRUE);	
	
	//============================================================
	// EOP END

	
	// SCRUB FOR DUPLICATES
	//============================================================		
	
	print "\n\nSCRUBBING FOR DUPLICATES...";
	$scrubbed  = 0;
	
	foreach($object_collection as $k=>$object_row)
	{
		$ires=$sql->query("lead_generation","INSERT IGNORE INTO TmpTable0481 
		SET application_id='".mysql_escape_string($object_row->application_id)."',
		created_date='".mysql_escape_string($object_row->created_date)."',
		first_name='".mysql_escape_string($object_row->first_name)."',
		middle_name='".mysql_escape_string($object_row->middle_name)."',
		last_name='".mysql_escape_string($object_row->last_name)."',
		home_phone='".mysql_escape_string($object_row->home_phone)."',
		email='".mysql_escape_string($object_row->email)."',
		dob='".mysql_escape_string($object_row->dobf)."',
		ssn='".mysql_escape_string($object_row->ssn)."',
		address_1='".mysql_escape_string($object_row->address_1)."',
		city='".mysql_escape_string($object_row->city)."',
		state='".mysql_escape_string($object_row->state)."',
		zip='".mysql_escape_string($object_row->zip)."',
		bank_account_number='".mysql_escape_string($object_row->bank_account_number)."',
		bank_routing_number='".mysql_escape_string($object_row->bank_routing_number)."',
		bank_account_type='".mysql_escape_string($object_row->bank_account_type)."',
		paydate_model_id='".mysql_escape_string($object_row->paydate_model_id)."',		
		income_frequency='".mysql_escape_string($object_row->income_frequency)."',
		day_of_week='".mysql_escape_string($object_row->day_of_week)."',
		next_paydate='".mysql_escape_string($object_row->next_paydate)."',
		day_of_month_1='".mysql_escape_string($object_row->day_of_month_1)."',
		day_of_month_2='".mysql_escape_string($object_row->day_of_month_2)."',
		week_1='".mysql_escape_string($object_row->week_1)."',
		week_2='".mysql_escape_string($object_row->week_2)."',		
		ip_address='".mysql_escape_string($object_row->ip_address)."',
		url='".mysql_escape_string($object_row->url)."'",Debug_1::Trace_Code(__FILE__,__LINE__));
		
		//print_r($ires);
		
		if($sql->Affected_Row_Count($ires)==0)
		{
			unset($object_collection[$k]);
			print "\nRemoved: Not Unique: {$object_row->email}";
			$scrubbed++;
		}
	}
	print "SCRUBBED - ".$scrubbed;	
	//print_r($object_collection);

	
	// GET HOLIDAY ARRAY
	//============================================================		
	
	$query_holidays = "SELECT date FROM holidays WHERE date >= NOW()";
	
	$result_holidays = $sql->query('d2_management', $query_holidays);
	
	while($row_holidays = $sql->Fetch_Array_Row($result_holidays))
	{
		$holidays[] = $row_holidays['date'];
	}	
	
		
	// CREATE CSV FILES
	//============================================================		

	$headers = array("APPLICATION_ID","CREATED_DATE","FIRST_NAME","MIDDLE_NAME","LAST_NAME","HOME_PHONE","EMAIL","DOB","SSN","ADDRESS_1","CITY","STATE","ZIP","BANK_ACCOUNT_NUMBER","BANK_ROUTING_NUMBER","BANK_ACCOUNT_TYPE","IP_ADDRESS","URL","SPECIAL_OFFER");
		
	print "\n\nGENERATING CSV FILE '/tmp/leadgen_datagrabber_0481.csv'....";
	
	$fp_csv = fopen("/tmp/leadgen_datagrabber_0481.csv","w");
	$csv = new CSV(array("forcequotes" => TRUE,"header" => $headers,"autoflush" => FALSE,"stream" => $fp_csv));
	$fp = fopen("/tmp/$filename","w");
		
	$SO1_records = 0;
	$SO2_records = 0;	

	foreach($object_collection as $row)
	{
		$day_of_week_map = array();
		$day_of_week[1] = 'MONDAY';
		$day_of_week[2] = 'TUESDAY';
		$day_of_week[3] = 'WEDNESDAY';
		$day_of_week[4] = 'THURSDAY';
		$day_of_week[5] = 'FRIDAY';
		$row->day_of_week = $day_of_week[$row->day_of_week];
		//print_r($row);
	
		$model_name = $row->paydate_model_id;
		$start_date = date("Y-m-d");
		
		$model_data =array();
		$model_data["day_string_one"] = $row->day_of_week;
		$model_data["next_pay_date"] = $row->next_paydate;
		$model_data["day_int_one"] = $row->day_of_month_1;
		$model_data["day_int_two"] = $row->day_of_month_2;
		$model_data["week_one"] = $row->week_1;
		$model_data["week_two"] = $row->week_2;
		$num_dates = "2";

		$pd = new Pay_Date_Calc_1($holidays);
		$paydates = array();
		$paydates = $pd->Calculate_Payday($model_name, $start_date, $model_data, $num_dates);		
					
		$csv->recordFromArray((array)$row);
		$SO = '';

		if (strtolower($row->url) == 'webfastcash.com' && $row->promo_id == '26695')
		{
			$SO = "SPECIAL OFFER 1";
			$SO1_records++;
		}
		if (strtolower($row->url) == 'webfastcash.com' && $row->promo_id == '26799')
		{
			$SO = "SPECIAL OFFER 2";
			$SO2_records++;
		}		

		fwrite($fp,"{$row->application_id}\t{$row->first_name}\t{$row->middle_name}\t{$row->last_name}\t{$row->address_1}\t{$row->city}\t{$row->state}\t{$row->zip}\t{$row->ssn}\t{$row->home_phone}\tMTZ-1\t{$row->dobf}\t{$row->email}\t{$row->url}\t{$row->ip_address}\t{$row->cdf}\t{$row->bank_routing_number}\t{$row->bank_account_number}\t{$row->bank_account_type}\t8004703004\t{$row->income_frequency}\t{$paydates[0]}\t{$paydates[1]}\t{$SO}\r\n");
	}
		
	$buf = $csv->_buf;
	$csv->flush();
	fclose($fp_csv);
	fclose($fp);
	
	print "CSV COMPLETED";	
	
	
	// PRINT RESULTS
	//============================================================	
		
	print "\n\nFINALIZING...";
	
	
	$SO_count = $SO1_records + $SO2_records;
	$EOP_total = $EOP_count - $SO_count;
	
	$total_records = $SO_count + $EOP_total;
	$total_usable_records = $total_records - $scrubbed;
	
	$results = "
		EOP RECORDS : $EOP_total
		SPECIAL OFFER RECORDS : $SO_count
		
		TOTAL RECORDS : $total_records
		RECORDS SCRUBBED : $scrubbed
		
		TOTAL USABLE RECORDS : $total_usable_records
	";
	print "\n\n".$results;	
	
	
	// FTP
	//============================================================		

	$ftp_client = new FTP();
	$ftp_client->server = "ftp.sellingsource.com";
	$ftp_client->user_name = "vendorpromotions";
	$ftp_client->user_password = "password";
	print "\n\nCONNECTING TO VP FTP SITE ... ";				
	$ftp_client->do_Connect($ftp_client);
	
	print "CONNECTED";
	
	print "\n\nUPLOADING FILE ... ";					
	$ftp_client->file = array("/tmp/$filename,/$filename");
	
	
	if (!$ftp_client->do_Put($ftp_client, true, false))
	{
		print "FAILED";	
	}
	else print "UPLOADED";

		
	// EMAIL
	//============================================================	
	
	$email_port = 25;
	$email_url = "sellingsource.com";
	$email_s_name = "DataGrubber";
	$email_s_address = "data-grubber-noreply@thesellingsource.com";
	
	// Build Email Header
	$header = new StdClass ();
	$header->smtp_server = $email_smtp_server;
	$header->port = $email_port;
	$header->url = $email_url;
	$header->subject = "Vendor Promotions -- Credit Offer Report ".date("m-d-Y", strtotime("-1 day"));
	$header->sender_name = $email_s_name;
	$header->sender_address = $email_s_address;
			
	$recipients = array
	(
		 (object)array("type" => "To", "name" => "Laura G.",	"address" => "laura.gharst@partnerweekly.com")
		,(object)array("type" => "To", "name" => "Vendor",  	"address" => "joseph@vendorpromotions.com")
		,(object)array("type" => "To", "name" => "Programmer",	"address" => "myya.perez@sellingsource.com")
		,(object)array("type" => "To", "name" => "Hope P.",		"address" => "Hope.Pacariem@partnerweekly.com")
		,(object)array("type" => "To", "name" => "Celeste C.",	"address" => "celestec@partnerweekly.com")
	);
	
	//$recipients_test = array((object)array("type" => "To", "name" => "Programmer",	"address" => "myya.perez@thesellingsource.com"));			
		
	$message = new StdClass ();
	$message->text = "
		Vendor Promotions -- free credit offer	
		$results
		This is an automatic email generated by TSSDataGrubber; do not reply.  If you have any questions, please contact myya.perez@thesellingsource.com. Thank you.
	";

	$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
	$mailing_id = $mail->CreateMailing ("datagrubber_run", $header, NULL, NULL);
	$package_id = $mail->AddPackage ($mailing_id, $recipients, $message, NULL);
	//$package_id = $mail->AddPackage ($mailing_id, $recipients_test, $message, NULL);
	$result = $mail->SendMail ($mailing_id);

	print "\n\nBATCH COMPLETED SUCCESSFULLY"

?>