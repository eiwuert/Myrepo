<?php
	// ======================================================================
	// FREE CREDIT REPORT OFFER - batch.nightly.vp.fcr.php
	//
	// Grabs records from BB2 where campaign_info.offers = 'TRUE'
	// Also grabs records from EOP and sends to VP
	// Modification - two promo id's are pulled out and sent to VP seperately
	//
	// myya.perez@thesellingsource.com 06-01-2005 - mods and cleaup
	// ======================================================================


	// INCLUDES / DEFINES / INITIALIZE VARIABLES
	// ======================================================================

	define('EMAIL_MODE','LIVE');
//	define('EMAIL_MODE','RC');
	
	define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

	require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
	require_once('mysql.3.php');
	require_once('debug.1.php');
	require_once('error.2.php');
	require_once('csv.1.php');
	require_once('ftp.2.php');
	include_once("prpc/client.php");
	include_once("pay_date_calc.1.php");
	require_once('olp_valid_accounts.1.php');
	//echo '<pre>';

	$filename = "SellingSourceEnrollment_" . date("Ymd", strtotime("-3 day")) . ".txt";

	$start 	= date("Ymd000000", strtotime("-3 day"));
	$end 	= date("Ymd235959", strtotime("-3 day"));
	$day_of_week[1] = 'MONDAY';
	$day_of_week[2] = 'TUESDAY';
	$day_of_week[3] = 'WEDNESDAY';
	$day_of_week[4] = 'THURSDAY';
	$day_of_week[5] = 'FRIDAY';
	$sites_list =  array (
		 '1500payday.com'
		//,'911paydaystore.com'
		,'americash-online.com'
		//,'directdepositcash.com'
		,'directdepositpayday.com'
		,'expresscashsource.com'
		,'fast-funds-online.com'
		,'greenpayday.com'
		,'nationalcheckadvance.com'
		,'nationalfastcash.com'
		,'paydaydirectusa.com'
		//,'primecashadvance.com'
		,'prioritycashloans.com'
		//,'procashadvance.com'
		//,'quickadvances.com'
		//,'rapidcashproviderapp.com'
		,'smartcashloans.com'
		,'universalpayday.com'
		,'usfastcash.com'
		,'webfastcash.com'
		//,'yourcashcentral.com'
		//,'yourfastcash.com'
	);

	$sql = new MySQL_3();
	
	$sql->Connect("BOTH", "reader.ecasholp.ept.tss:3307", "olp", "hK95GvDF");

	// GET HOLIDAY ARRAY
	//============================================================

	$query_holidays = "SELECT holiday FROM holiday WHERE holiday >= NOW()";

	$result_holidays = $sql->query('ldb', $query_holidays);

	while($row_holidays = $sql->Fetch_Array_Row($result_holidays))
	{
		$holidays[] = $row_holidays['holiday'];
	}
	
	$olp_accounts = new OLP_Valid_Accounts($start,$end,"OLP_VPFCR",$sites_list,EMAIL_MODE);
	$data_array = $olp_accounts->Get_Bad_Standing_Accounts();
	//$data_array = $olp_accounts->Get_All_Accounts();

	// CREATE CSV FILES
	//============================================================

	$headers = array("APPLICATION_ID","CREATED_DATE","FIRST_NAME","MIDDLE_NAME","LAST_NAME","HOME_PHONE","EMAIL","DOB","SSN","ADDRESS_1","CITY","STATE","ZIP","BANK_ACCOUNT_NUMBER","BANK_ROUTING_NUMBER","BANK_ACCOUNT_TYPE","IP_ADDRESS","URL","SPECIAL_OFFER");

	print "\n\nGENERATING CSV FILE '/tmp/leadgen_datagrabber_0481.csv'....";

	$fp_csv = fopen("/tmp/leadgen_datagrabber_0481.csv","w");
	$csv = new CSV(array("forcequotes" => TRUE,"header" => $headers,"autoflush" => FALSE,"stream" => $fp_csv));

	$fp = fopen("/tmp/$filename","w");

	$SO1_records = 0;
	$SO2_records = 0;
	$olp_result_count = count($data_array);
	print "\n\nOLP RESULT COUNT -".$olp_result_count;

	for($i=0; $i<$olp_result_count ; $i++)
	{
		$item = $data_array[$i];
		$item['day_of_week'] = is_numeric($item['day_of_week']) ? $day_of_week[$item['day_of_week']] : null;
		$model_name = $item['paydate_model_id'];
		$start_date = date("Y-m-d");

		$model_data =array();
		$model_data["day_string_one"] = $item['day_of_week'];
		$model_data["next_pay_date"] = $item['next_paydate'];
		$model_data["day_int_one"] = $item['day_of_month_1'];
		$model_data["day_int_two"] = $item['day_of_month_2'];
		$model_data["week_one"] = $item['week_1'];
		$model_data["week_two"] = $item['week_2'];
		$num_dates = "2";

		$pd = new Pay_Date_Calc_1($holidays);
		$paydates = $pd->Calculate_Payday($model_name, $start_date, $model_data, $num_dates);

		$csv->recordFromArray($item);
		$SO = '';
		if (strtolower($item['url']) == 'webfastcash.com' && $item['promo_id'] == '26695')
		{
			$SO = "SPECIAL OFFER 1";
			$SO1_records++;
		}
		if (strtolower($item['url']) == 'webfastcash.com' && $item['promo_id'] == '26799')
		{
			$SO = "SPECIAL OFFER 2";
			$SO2_records++;
		}

		fwrite($fp,"{$item['application_id']}\t{$item['first_name']}\t{$item['middle_name']}\t{$item['last_name']}\t{$item['address_1']}\t{$item['city']}\t{$item['state']}\t{$item['zip']}\t{$item['ssn']}\t{$item['home_phone']}\tMTZ-1\t{$item['dobf']}\t{$item['email']}\t{$item['url']}\t{$item['ip_address']}\t{$item['cdf']}\t{$item['bank_routing_number']}\t{$item['bank_account_number']}\t{$item['bank_account_type']}\t8004703004\t{$item['income_frequency']}\t{$paydates[0]}\t{$paydates[1]}\t{$SO}\r\n");
	}


	$buf = $csv->_buf;
	$csv->flush();
	fclose($fp_csv);
	fclose($fp);

	print "\n\nCSV COMPLETED";


	// PRINT RESULTS
	//============================================================

	print "\n\nFINALIZING...";

	$SO_count = $SO1_records + $SO2_records;
	$BB_count = $olp_result_count - $SO_count;

	$total_records = $BB_count + $SO_count;

	$results = "
		BLACKBOX RECORDS : $BB_count
		SPECIAL OFFER RECORDS : $SO_count

		TOTAL RECORDS : $total_records
		";
	print "\n\n".$results;

	$data = array(
		"email_primary_name" => "joseph@monetizeit.net",
		"email_primary" => "joseph@monetizeit.net",
//		"email_primary" => "adam.englander@sellingsource.com",
		"sender_name" => "Selling Source <no-reply@sellingsource.com>",
		"date"        => date("Y-m-d H:i:s"),
		"site_name"   => "sellingsource.com",
		"filename"     => $filename,
	);
	

	$file_contents = file_get_contents("/tmp/{$filename}");
	$attach = array(
		'method' => 'ATTACH',
		'filename' => $filename,
		'mime_type' => 'text/plain',
		'file_data' => gzcompress($file_contents),
		'file_data_size' => strlen($file_contents),
	);
	
	$tx = new OlpTxMailClient(false,MAIL_MODE);

	try 
	{
		$r = $tx->sendMessage('live', 'CRON_NIGHTLY_VP_FCR', $data['email_primary'], '', $data, array($attach));
	
	}
	catch(Exception $e)
	{
		$r = FALSE;
	}

	echo ($r === FALSE) ? 'FAILED' : 'Message sent';
	
	/*$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/ole_smtp.1.php");
	
	$data['attachment_id'] = $mail->Add_Attachment(file_get_contents("/tmp/".$filename), 'plain/text', $filename, "ATTACH");
	if ($result = $mail->Ole_Send_Mail("CRON_NIGHTLY_VP_FCR", 17176, $data))
	{
		print "Message sent";
	}
	else print "FAILED";*/


	// SEND_EMAIL FUNCTION
	//============================================================

	function send_email($results)
	{

		$subject = "Vendor Promotions -- Credit Offer Report ".date("m-d-Y", strtotime("-1 day"));
		$message =  "
		Vendor Promotions -- free credit offer
		$results
		This is an automatic email generated by TSSDataGrubber; do not reply.  If you have any questions, please contact jason.gabriele@sellingsource.com. Thank you.
	";
		$header = array
		(
			"sender_name" => "DataGrubber <data-grubber-noreply@thesellingsource.com>",
			"subject" 	=> $subject,
			"site_name" 	=> "sellingsource.com",
			"message" 	=> $message
		);
	 	$recipients = array
	 	(
//			array("email_primary_name" => "Test",   		"email_primary" => "adam.englander@sellingsource.com"),
			array("email_primary_name" => "Vendor",   		"email_primary" => "joseph@vendorpromotions.com"),
			array("email_primary_name" => "Jake Ludens",   	"email_primary" => "jake.ludens@partnerweekly.com"),
			array("email_primary_name" => "Hope P",   		"email_primary" => "Hope.Pacariem@partnerweekly.com"),
			array("email_primary_name" => "Celeste C.",   	"email_primary" => "celestec@partnerweekly.com"),
			array("email_primary_name" => "Programmer",   	"email_primary" => "jason.gabriele@sellingsource.com"),
			array("email_primary_name" => "Jessalin Foell", "email_primary" => "Jessalin.Foell@PartnerWeekly.com")
		);
		$tx = new OlpTxMailClient(false,MAIL_MODE);
		for($i=0; $i<count($recipients); $i++)
		{
			/*$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/ole_smtp.1.php");
			$data = array_merge($recipients[$i], $header);
			$result = $mail->Ole_Send_Mail("CRON_EMAIL", 28400, $data);*/

			$data = array_merge($recipients[$i], $header);
			try 
			{
				$result = $tx->sendMessage('live', 'CRON_EMAIL_OLP', $data['email_primary'], '', $data);
			}
			catch(Exception $e)
			{
				$result = FALSE;
			}
			
			if($result)
			{
				print "\r\nEMAIL HAS BEEN SENT TO: ".$recipients[$i]['email_primary']." .\r\n";
			}
			else
			{
				print "\r\nERROR SENDING EMAIL TO: ".$recipients[$i]['email_primary']." .\r\n";
			}
		}

	}

	send_email($results);

	print "\n\nBATCH COMPLETED SUCCESSFULLY"

?>
