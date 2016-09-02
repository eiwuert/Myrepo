<?php

	//============================================================
	// Mortgage Cronjob for EOP Postapp Offers (on cash sites)
	// Runs Hourly - Grabs data from EOP tables where user
	// has selected "Mortgage" offer
	// Checks Business rules to decide which vendor gets lead
	// Builds a CSV string and Sends Email or Posts to site depending 
	// on which Vendor is selected
	// Hits Stat and Updates eop_selected table
	// - myya perez(myya.perez@thesellingsource.com), 02-07-2005
	//============================================================
	
	
	// INCLUDES / DEFINES
	//============================================================	

	require_once("mysql.3.php");
	require_once("diag.1.php");
	require_once("lib_mode.1.php");
	require_once("csv.1.php");
	require_once("prpc/client.php");
	require_once("prpc/server.php");
	require_once("HTTP/Request.php");	
	require_once("hit_stats.1.php");	
	
	$one_hour_ago = date("Y-m-d H:i:s", strtotime("-1 hour"));
	$now = date("Y-m-d H:i:s");	
	$day = date("Y-m-d");
	
	$start = $one_hour_ago;
	$end = $now;
	
	$start_hour = date("hi", strtotime("-1 hour"));
	$end_hour =date("hi");

	// testing values
	// $start = '2005-02-08 11:00:00';
	// $end = '2005-02-08 12:00:100';
	// $start_hour = '1100';
	// $end_hour = '1200';	
	
	$update = array();

	$CHS1 = array();
	$CHS2 = array();
	$AM1  = array();
	$AM2  = array();
	$AM3  = array();
	

	// CONNECTIONS									
	// ==================================================
	
	switch (Lib_Mode::Get_Mode())
	{
	case MODE_LOCAL:
		Diag::Enable();
		define('DB_HOST',	'selsds001');
		define('DB_USER',	'sellingsource');
		define('DB_PASS',	'password');
		define('DB_NAME',	'rc_lead_generation');
		define('LICENSE_T1','b81f96b6ff780f1542b5886688a41ae8');
		define('LICENSE_T3','8adcfe5b961a51076207e499ecdaf640');
		define('LICENSE_RMQ','a4c5a3e750b644f26741f6df1f11e2a4');
		break;
	case MODE_RC:
	default:
		define('DB_HOST',	'selsds001');
		define('DB_USER',	'sellingsource');
		define('DB_PASS',	'password');
		define('DB_NAME',	'rc_lead_generation');
		define('LICENSE_T1','0ced7b863c570b2ab4ce666f34ffd1ef');
		define('LICENSE_T3','99f2710b19d8844c6d94a7606c980c11');
		define('LICENSE_RMQ','b2559932e03c8ea97cbd6bf087841c98 ');
		break;
	case MODE_LIVE:
		define('DB_HOST',	'selsds001');
		define('DB_USER',	'sellingsource');
		define('DB_PASS',	'password');
		define('DB_NAME',	'lead_generation');
		define('LICENSE_T1','f6d768ff02aaffc42d847ae7ba79f95f');
		define('LICENSE_T3','7045c88a5dd2ef50e5dfa7f9cb623864');
		define('LICENSE_RMQ','16e0700a627742722e9169a2e0e0a4bd');		
		// for testing
		//define('DB_NAME',	'rc_lead_generation');
		//define('LICENSE_T1','0ced7b863c570b2ab4ce666f34ffd1ef');
		//define('LICENSE_T3','99f2710b19d8844c6d94a7606c980c11');
		//define('LICENSE_RMQ','b2559932e03c8ea97cbd6bf087841c98 ');
		break;
	}		
	
	
	// SELECT DATA
	//============================================================
	
	$sql = new MySQL_3();
	$sql->Connect(NULL,DB_HOST,DB_USER,DB_PASS,Debug_1::Trace_Code(__FILE__, __LINE__));	
	
	$query_select = "	
		SELECT
			 eop_customers.app_id
			,eop_customers.ip
			,eop_customers.promo_id
			,eop_customers.promo_sub_code
			,eop_customers.tier
			,eop_customers.created_date
			,eop_customers.addl_info_serialized
			,eop_customers.fname
			,eop_customers.lname
			,eop_customers.email
			,eop_customers.phone_home
			,eop_customers.phone_work
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
			AND eop_selected.selected_offer = 'Mortgage'
			AND eop_customers.fname !='test'
			AND eop_customers.lname !='test'
			AND eop_selected.vendor = ''
		";

	$result = $sql->Query(DB_NAME, $query_select, Debug_1::Trace_Code(__FILE__,__LINE__));

	
	// MANAGE RESULTS
	//============================================================	
	
	while ($row = $sql->Fetch_Array_Row($result))
	{	
	
		// PREPARE DATA FOR SORTING AND POSTING / EMAILING
		//--------------------------------------------------
		
		// unserialize additional information
		$addl_info = unserialize($row['addl_info_serialized']);
		
		// modify address information - add home unit to street address
		if (!$row["address2"])
		{
			$address = $row["address1"];
		}
		else
		{
			$address = $row["address1"].' #'.$row["address2"];
		}		
		
		// map credit rating
		$rating = $addl_info['credit_rating'];
		if ($rating == 'good' || $rating == 'minor_problems' || $rating == 'little_or_none')
		{
			$rating = 'moderate';
		}
		
		// map property type
		$property_type = $addl_info['property_type'];
		if ($property_type == 'single_family')
		{
			$property_type = 'single family';
		}		
		elseif ($property_type == 'town_house')
		{
			$property_type = 'Multi-Family';
		}	
		
		
		// check interest rate
		if ($addl_info['interest_rate'] < '1')
		{
			$addl_info['interest_rate'] = $addl_info['interest_rate'] * '100';
		}		
		
		
		// INITIALIZE ARRAYS FOR POSTING / EMAILING
		//--------------------------------------------------
		
		//Addictive Marketing CSV Fields Array
		
		$AM_fields = '"PartnerID","VendorLeadID","FirstName","LastName","StreetAddress","City","State","ZIP","PhoneDay","PhoneEvening","BestCallTime","EmailAddress","LoanType","PropertyType","LoanAmt","PropertyValue","RefinanceReason","PropertyPrice","LoanBalance","LoanPayment","LoanRate","LoanRateType","CreditQuality","MonthlyIncome","IPAddress","DateTimeApplication"'."\r\n";
		
		//Addictive Marketing CSV Array
		$AM_csv_ary = array(
			"3439"
			,$row['app_id']
			,$row['fname']
			,$row['lname']
			,$address
			,$row['city']
			,$row['state']
			,$row['zip']
			,$row['phone_work']
			,$row['phone_home']
			,$addl_info['best_call_time']
			,$row['email']
			,"Refinance"
			,$addl_info['property_type']
			,$addl_info['loan_amount']
			,$addl_info['property_value']
			,"1"
			,""
			,$addl_info['mortgage_balance']
			,$addl_info['monthly_pmt']
			,$addl_info['interest_rate']
			,"Fixed"
			,$addl_info['credit_rating']
			,$addl_info['income_monthly_net']
			,$row['ip']
			,$row['created_date']
		);	
		
		//California Home Savers Posting Array
		$CHS_post_ary = array
		(
			'loantype'				=> $addl_info['loan_type']
			,'fname'				=> $row['fname']
			,'lname'				=> $row['lname']	 
			,'address1'				=> $row['address1']
			,'address2'				=> $row['address2']
			,'city'					=> $row['city']
			,'state'				=> $row['state']
			,'zip'					=> $row['zip']
			,'phone1'				=> $row['phone_home']
			,'phone2'				=> $row['phone_work']
			,'email'				=> $row['email']
			,'TimetoContact'		=> $addl_info['best_call_time']
			,'ipaddress'			=> $row['ip']
			,'LoanAmount'			=> $addl_info['loan_amount']
			,'MortgageBalance'		=> $addl_info['mortgage_balance']
			,'homeValue'			=> $addl_info['property_value']
			,'CreditRating'			=> $rating
			,'CurrentInterestRate'	=> $addl_info['interest_rate']
			,'typeofhouse'			=> $property_type
			,'comments'				=> "no comments"
			,'mailinglist'			=> "no"
		);	
				
		// INITIALIZE VARIABLES TO CHECK BUSINESS RULES
		//--------------------------------------------------
		
		$stat_col = '';
		$state 	  = $row['state'];
		$loan_amt = $addl_info['loan_amount'];
		$home_val = $addl_info['property_value'];
		$int_rate = $addl_info['interest_rate'];
		$loan_typ = $addl_info['loan_type'];
		$ltv	= '';
		
		if ($home_val && $home_val != '0' && $home_val != '' )
		{
			$ltv = $loan_amt / $addl_info['property_value'];		
		}

		
		// CHECK BUSINESS RULES / DECIDE WHICH VENDOR GETS LEAD
		//--------------------------------------------------		
		
		if (!$loan_amt || $loan_amt == '' || !$home_val || $home_val == '' ||
			!$int_rate || $int_rate == '' || 
			!$addl_info['property_type'] || $addl_info['property_type'] == '' || 
			!$addl_info['credit_rating'] || $addl_info['credit_rating'] == '' || 
			!$addl_info['mortgage_balance'] || $addl_info['mortgage_balance'] == '')
		{
			// addl info is missing 
			// add to update array but don't send anywhere 
			$count = count($update);
			$update[$count]['app_id'] = $row['app_id'];
			$update[$count]['vendor'] = '';
			$update[$count]['result'] = 'Not Sent';
			$update[$count]['reason'] = 'Did Not Complete Additional Information';			
		}		
		elseif ($state == 'CA' || $state == 'FL' || $state == 'CO' || $state == 'CT' || 
				$state == 'RI' || $state == 'WA' || $state == 'VA' || $state == 'MN' &&
				$loan_amt > '99999' && $ltv < '0.9' && $int_rate > '4' && $loan_typ != '')
		{
			// grab post ary and send it to pear posting function
			// receive back results
			
			// add to update array
			$count = count($update);
			$update[$count]['app_id'] = $row['app_id'];
			$update[$count]['vendor'] = 'CHS1';
			$update[$count]['result'] = 'Lead Sent';
			$update[$count]['reason'] = '';		

			// post
			$post_method = 'POST';
			//$post_url = 'http://rc.fastbizconnect.com/test.php';
			$post_url = 'http://www.californiahomesavings.com/posting/partner_weekly/partner_weekly_3049.asp';
			$body = post_lead ($post_url, $post_method, $CHS_post_ary);	
			
			// add to CHS1 array - only for counting purposes
			$count = count($CHS1);
			$CHS1[$count] = $row['app_id'];	
			
			//Set Stat Column
			$stat_col = 'vendor2';		
		}
		elseif ($state == 'CA' || $state == 'CO' || $state == 'WA' || $state == 'MA' ||
				$state == 'NV' || $state == 'CT' || $state == 'MI' || $state == 'IL' ||
				$state == 'FL' || $state == 'VA' || $state == 'MD' || $state == 'MN' ||
				$state == 'RI' && $loan_amt > '99999' && $ltv < '0.85' && $home_val > '117999')
		{
			// put entire entry in AM array
			$count = count($AM1);
			$AM1[$count] = $AM_csv_ary;

			// add to update array
			$count = count($update);
			$update[$count]['app_id'] = $row['app_id'];
			$update[$count]['vendor'] = 'AM1';
			$update[$count]['result'] = 'Lead Sent';
			$update[$count]['reason'] = '';	
			
			//Set Stat Column
			$stat_col = 'vendor4';						
		}
		elseif ($loan_amt > '99999' && $ltv < '0.85' && $home_val > '117999')
		{
			// put entire entry in AM array
			$count = count($AM2);
			$AM2[$count] = $AM_csv_ary;

			// add to update array
			$count = count($update);
			$update[$count]['app_id'] = $row['app_id'];
			$update[$count]['vendor'] = 'AM2';
			$update[$count]['result'] = 'Lead Sent';
			$update[$count]['reason'] = '';			
			
			//Set Stat Column
			$stat_col = 'vendor5';			
		}
		elseif ($loan_amt > '99999' && $ltv < '0.9' && $int_rate > '4' && $loan_typ != '')
		{
			// grab post ary and send it to pear posting function
			// receive back results
			
			// add to update array
			$count = count($update);
			$update[$count]['app_id'] = $row['app_id'];
			$update[$count]['vendor'] = 'CHS2';
			$update[$count]['result'] = 'Lead Sent';
			$update[$count]['reason'] = '';		
			
			// post
			$post_method = 'POST';
			//$post_url = 'http://rc.fastbizconnect.com/test.php';
			$post_url = 'http://www.californiahomesavings.com/posting/partner_weekly/partner_weekly_3310.asp';
			$body = post_lead ($post_url, $post_method, $CHS_post_ary);				
			
			// add to CHS1 array - only for counting purposes
			$count = count($CHS2);
			$CHS2[$count] = $row['app_id'];	
			
			//Set Stat Column
			$stat_col = 'vendor3';							
		}
		elseif ($loan_amt > '69999' && $ltv < '0.85' && $home_val > '81999')
		{
			// put entire entry in AM array
			$count = count($AM3);
			$AM3[$count] = $AM_csv_ary;

			// add to update array
			$count = count($update);
			$update[$count]['app_id'] = $row['app_id'];
			$update[$count]['vendor'] = 'AM3';
			$update[$count]['result'] = 'Lead Sent';
			$update[$count]['reason'] = '';	
			
			//Set Stat Column
			$stat_col = 'vendor6';					
		}
		else
		{
			// does not meet any of our current vendor requirements 
			// add to update array but don't send anywhere 
			$count = count($update);
			$update[$count]['app_id'] = $row['app_id'];
			$update[$count]['vendor'] = '';
			$update[$count]['result'] = 'Not Sent';
			$update[$count]['reason'] = 'Did Not Meet Vendor Requirements';			
		}	
		
		// HIT STAT
		//--------------------------------------------------		
		$key = LICENSE_T1;
		if ($row['tier'] == '3')
		{
			$key = LICENSE_T3;
		}
		elseif ($row['tier'] == 'rmq')
		{
			$key = LICENSE_RMQ;
		}		

		if ($stat_col != '')
		{
			Hit::Stats(array(  
				"license_key" => $key,  
				"sql" => $sql,  
				"promo_id" => $data['promo_id'],  
				"promo_sub_code" => $data['promo_sub_code'],  
				"column" => $stat_col,  
				"hits" => 1  
				)  
			);	
		}	
	}

	
	// POST_LEAD FUNCTION
	//============================================================		
	
	function post_lead($post_url, $post_method, $post_ary)
	{	
	
		$net = new HTTP_Request($post_url);
		
		if ($post_method == 'GET')
		{
			$net->setMethod(HTTP_REQUEST_METHOD_GET);
			reset($post_ary);
			while (list($k, $v) = each($post_ary))
			$net->addQueryString($k, $v);
		}
		elseif ($post_method == 'POST')
		{
			$net->setMethod(HTTP_REQUEST_METHOD_POST);
			reset($post_ary);
			while (list($k, $v) = each($post_ary))
				$net->addPostData($k, $v);
		}
	
		$net->sendRequest();

		$response = $net->getResponseCode(); 
		
		if (!$response)
		{
			print "\r\nRESPONSE PAGE WAS NOT FOUND<br>\r\n";
		}

		$body = $net->getResponseBody();
		
		return $body;

	}		
	
	
	// UPDATE_OFFERS FUNCTION
	//============================================================	
	
	function update_offers ($count, $update, $sql)
	{
		for ($i =0; $i < $count; $i++)
		{
			$app_id = $update[$i]['app_id'];
			$vendor = $update[$i]['vendor'];
			$reason = $update[$i]['reason'];
			$result = $update[$i]['result'];
			
			$query_update = "	
				UPDATE
					eop_selected
				SET
					vendor = '$vendor'
					,result = '$result'
					,result_reason = '$reason'
				WHERE
					app_id = '$app_id'
					AND selected_offer = 'Mortgage'
				";		
				
			$rs = $sql->Query("lead_generation", $query_update, Debug_1::Trace_Code(__FILE__,__LINE__));
			//$rs = $sql->Query("rc_lead_generation", $query_update, Debug_1::Trace_Code(__FILE__,__LINE__));			
		}
	}
		
	
	// CREATE_CSV FUNCTION
	//============================================================	
	
	function create_csv ($csv, $csv_array)
	{
		for ($i = '0'; $i < count($csv_array); $i++)
		{
			$csv .= '"'.join('","', $csv_array[$i])."\"\r\n";
		}
		
		return $csv;
	}

	
	// SEND_EMAIL FUNCTION
	//============================================================		
	
	function send_email($day, $start_hour, $end_hour, $vendor, $csv, $count)
	{	
	
		$header = (object)array
		(
			"port"			 => 25,
			"url"			 => "maildataserver.com",
			"subject"		 => "TSS Mortgage Leads",
			"sender_name"	 => "John Hawkins",
			"sender_address" => "john.hawkins@thesellingsource.com"
		);
		
	 	$recipient = array
	 	(
	 		(object)array("type" => "to", "name" => "Ken Keyes",  "address" => "kkeyes@addictivemarketing.com"),
			(object)array("type" => "to", "name" => "Laura G.",   "address" => "laura.gharst@partnerweekly.com"),
			(object)array("type" => "to", "name" => "Programmer", "address" => "myya.perez@thesellingsource.com"),
	 	);	
		
		$message = (object)array
		(
			"text" => "Please see attached CSV file. There are $count records..."
		);
			
		$attach = new stdClass ();
		$attach->name = $day."_".$vendor."_leads_from_".$start_hour."-".$end_hour.".csv";
		$attach->content = base64_encode ($csv);
		$attach->content_type = "text/csv";
		$attach->content_length = strlen ($csv);
		$attach->encoded = "TRUE";		
		
		$mail = new Prpc_Client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
		$mail_id = $mail->CreateMailing("EOP_MORTGAGE", $header, NULL, NULL);
		$package_id = $mail->AddPackage($mail_id, $recipient, $message, array ($attach));
		$sender = $mail->SendMail($mail_id);	
	}
	
	
	// GET COUNTS AND PRINT
	//============================================================	
		
	$count_update	= count($update);
	$count_CHS1 	= count($CHS1);
	$count_CHS2 	= count($CHS2);
	$count_AM1		= count($AM1);
	$count_AM2		= count($AM2);
	$count_AM3		= count($AM3);
	
	if ($count_update != 0)
	{
		print "\r\nTOTAL RECORDS FOR THIS HOUR: $count_update\r\n";
		print "\r\nTOTAL RECORDS FOR CHS1: $count_CHS1\r\n";
		print "\r\nTOTAL RECORDS FOR CHS2: $count_CHS2\r\n";
		print "\r\nTOTAL RECORDS FOR AM1: $count_AM1\r\n";
		print "\r\nTOTAL RECORDS FOR AM2: $count_AM2\r\n";
		print "\r\nTOTAL RECORDS FOR AM3: $count_AM3\r\n";
	}
		
	// CALL FUNCTIONS
	//============================================================		

	
	if ($count_AM1 && $count_AM1 > '0')
	{
		$AM1_csv = create_csv ($AM_fields, $AM1);
		send_email ($day, $start_hour, $end_hour, 'AM1', $AM1_csv, $count_AM1);
	}
	
	if ($count_AM2 && $count_AM2 > '0')
	{
		$AM2_csv = create_csv ($AM_fields, $AM2);
		send_email ($day, $start_hour, $end_hour, 'AM2', $AM2_csv, $count_AM2);
	}
	
	if ($count_AM3 && $count_AM3 > '0')
	{
		$AM3_csv = create_csv ($AM_fields, $AM3);
		send_email ($day, $start_hour, $end_hour, 'AM1', $AM3_csv, $count_AM3);
	}			
	
	if ($count_update && $count_update > '0')
	{
		update_offers ($count_update, $update, $sql);
	}
	
?>