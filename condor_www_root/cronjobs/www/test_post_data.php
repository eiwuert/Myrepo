<?php
		
	// ==================================================
	// INCLUDES / DEFINES																			
	// ==================================================
	
	require_once("HTTP/Request.php");

	
	function post_data($post_ary, $post_url, $post_method)
	//==================================================
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
		
		if ( $net->getResponseCode() == 200 ) 
		{
			echo "<br>Life is good<br>";
		}
		else 
		{
			echo "<br>No good<br>";
		}	
			
		$body_whole = $net->getResponseBody();
		return $body_whole;
	}
	
	
	
	// post method
	// ------------------------------------------------------	
	
	$method_post = 'POST';
	$method_get  = 'GET';
	
	
	// post url
	// ------------------------------------------------------
	
	$post_url_0  = 'http://test.ds28.tss/catch_post_data.php';
	$post_url_1 = 'http://www.lendergateway.com/dn_register.php';
	$post_url_11 = 'http://www.lendergateway.com/testpost/dn_register.php';
	$post_url_2 = 'http://www.educationaldirect.net/studentloan/DirectSave.jst';
	$post_url_3 = 'http://www.loanapprovaldirect.com/posting/partnerweekly/partnerweekly_33.asp';
	$post_url_4 = 'http://secure.authorize.net/gateway/transact.dll';
	$post_url_5 = 'http://rc.fastbizconnect.com/test.php';
	$post_url_6 = 'https://www.greatweboffers.com/test/catch_post_data.php';
	
	
	// post array
	// ------------------------------------------------------	
	
	$post_array_1  = array
	(
		'media' 			=> 18648
		,'email' 			=> 'smcduck@aol.com'
		,'firstname'		=> 'scrooge'
		,'lastname' 		=> 'mcduck'
		,'address' 			=> '123 test drive'
		,'city' 			=> 'Las Vegas'
		,'state' 			=> 'NV'
		,'zip' 				=> '89119'
		,'home_phone' 		=> "1231231234"
		,'work_phone' 		=> "1231231234"
		,'wp_area' 			=> '123'
		,'wp_prefix' 		=> '123'
		,'wp_suffix' 		=> '1234'
		,'wp_ext' 			=> ''
		,'hp_area' 			=> '123'
		,'hp_prefix' 		=> '123'
		,'hp_suffix' 		=> '1234'
		,'contact_time' 	=> ""
		,'credit' 			=> ""
		,'loan_type' 		=> "debtnegotiation"
		,'udebt' 			=> '3000'
		,'cc_debt' 			=> '10000'
		,'total_debt' 		=> '10000'
		,'other_debt' 		=> ""
		,'creditors' 		=> ""
		,'payment_status' 	=> ""
		,'primary_goal' 	=> "consolidation"
		,'pay_per_month' 	=> ""
		,'income' 			=> ""
		,'agree' 			=> ""
		,'comment' 			=> ""
		,'ownorrent' 		=> ""
	);		
	$post_array_11  = array
	(
		'media' 			=> 18648
		,'email' 			=> ''
		,'firstname'		=> ''
		,'lastname' 		=> ''
		,'address' 			=> ''
		,'city' 			=> ''
		,'state' 			=> ''
		,'zip' 				=> ''
		,'home_phone' 		=> ''
		,'work_phone' 		=> ''
		,'wp_area' 			=> ''
		,'wp_prefix' 		=> ''
		,'wp_suffix' 		=> ''
		,'wp_ext' 			=> ''
		,'hp_area' 			=> ''
		,'hp_prefix' 		=> ''
		,'hp_suffix' 		=> ''
		,'contact_time' 	=> ''
		,'credit' 			=> ''
		,'loan_type' 		=> ''
		,'udebt' 			=> ''
		,'cc_debt' 			=> ''
		,'total_debt' 		=> ''
		,'other_debt' 		=> ''
		,'creditors' 		=> ''
		,'payment_status' 	=> ''
		,'primary_goal' 	=> ''
		,'pay_per_month' 	=> ''
		,'income' 			=> ''
		,'agree' 			=> ''
		,'comment' 			=> ''
		,'ownorrent' 		=> ''
	);		
	
	// educational direct
	$post_array_2  = array
	(
		'referId' 		=> 'TEST'
		,'campaignId' 	=> '1'
		,'dphone' 		=> '1231231234'
		,'nphone' 		=> '1231231234'
		,'fname' 		=> 'test'
		,'lname' 		=> 'test'
		,'address1' 	=> '123 test drive'
		,'city' 		=> 'las vegas'
		,'state' 		=> 'NV'
		,'zip' 			=> '89119'
		,'emailaddress' => 'test@test.com'
		,'SSN' 			=> '123123123'
		,'bFirstQuestionAnswer' => '1'
	);	
	
	// educational direct
	$post_array_22  = array
	(
		'referId' 		=> 'TEST'
		,'campaignId' 	=> ''
		,'dphone' 		=> ''
		,'nphone' 		=> ''
		,'fname' 		=> ''
		,'lname' 		=> ''
		,'address1' 	=> ''
		,'city' 		=> ''
		,'state' 		=> ''
		,'zip' 			=> ''
		,'emailaddress' => ''
		,'SSN' 			=> ''
		,'bFirstQuestionAnswer' => ''
	);		
	
	// debt24
	$post_array_3 = array
	(
		'fname' 		=> 'test'
		,'lname' 		=> 'test'
		,'address' 	=> '123 asdf'
		,'city' 		=> 'Las Vegas'
		,'state' 		=> 'NV'
		,'zip' 			=> '89119'
		,'phone1' 		=> '1231231234'
		,'phone2' 		=> '1231231234'
		,'email' 		=> 'test@test.com'
		,'dob' 			=> '11/11/1979'
		,'question1' 	=> '15000'
		,'question2' 	=> '2-5'
		,'question3' 	=> 'Visa'
		,'question4' 	=> 'rent'
		,'question5' 	=> 'morning'
		,'question6' 	=> 'yes'
		,'IPaddress' 	=> '111.11.111'
	);	


	$post_array_4 = array 
	(
		'x_first_name' => "test"
		,'x_last_name' => "test"
		,'x_address' => "123 test"
		,'x_city'    =>  "test"
		,'x_state' => "NV"
		,'x_zip' => "12345-12345"         
		,'x_phone' => "1234567890"
		,'x_email' => "test@test.com"
		//,'x_card_num' => "4007000000027"
		//,'x_exp_date' => "092008"
		,'x_amount' => "5.95"
		,'x_cust_id' => "123456"
		,'x_login' => "hea481307599"
		,'x_fp_timestamp' => $tstamp
		,'x_fp_sequence' => $sequence
		,'x_fp_hash' => $fingerprint
		,'x_test_request' => "TRUE"
		,'x_method' => "ECHECK"
		,'x_recurring_billing' => "NO"
		,'x_echeck_type' => "WEB"
		,'x_bank_aba_code' => "123123123"
		,'x_bank_acct_num' => "123123123"
		,'x_bank_acct_type' => "CHECKING"
	); 

	
	
	
	// function call
	// ------------------------------------------------------	

	$post_array  = $post_array_4;
	$post_url 	 = $post_url_4;
	$post_method = $method_post;
	
	$body_whole = post_data($post_array, $post_url, $post_method);
	
	foreach($post_array as $k => $v)
	{
		echo $k."-------------------------------".$v;
		echo '<br>';
	}

	
	// response
	// ------------------------------------------------------		
	
	echo '<br><br><br>';
	print_r($body_whole);
	
	
?>
