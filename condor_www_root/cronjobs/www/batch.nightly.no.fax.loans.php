<?php
	// ======================================================================
	// No Fax Loans Leads
	// This is an extension of the batch.nightly.page2drops.php 
	// TmpTable0434 must be populated 
	// Must not send same records as batch.nightly.page2drops.php
	// Hits Stat NFL_P2
	//
	// myya.perez@thesellingsource.com 05-23-2005
	// ======================================================================
	
	
	// INCLUDES / DEFINES / INITIALIZE VARIABLES
	// ======================================================================

	require_once("prpc/client.php");
	require_once("HTTP/Request.php");
	require_once('debug.1.php');
	require_once('error.2.php');
	require_once('mysql.4.php');
	require_once('config.6.php');
	require_once('setstat.3.php');
	require_once('olp_valid_accounts.1.php');
	
	define('LICENSE_KEY',  '3301577eb098835e4d771d4cceb6542b');
	define('STAT_COLUMN', 'h10');	
	define('PROMO_ID', 29497);
	define('PROMO_SUB_CODE','');
	
	echo '<pre>';
	$max_per_day = '100';
	$fromDate = date("Ymd000000",strtotime("2 days ago"));
	$toDate = date("Ymd235959",strtotime("2 days ago"));
	
	$today = date("Y-m-d");
	$post_url = 'http://www.no-fax-loan.com/leads.php';
	//$post_url = 'http://test.ds28.tss/catch_post_data.php';
	
	
	$olp_bb_partial = new OLP_Valid_Accounts($fromDate,$toDate,"OLP_BB_PARTIALS",null,"LIVE");
	$data_array = $olp_bb_partial->Get_Bad_Standing_Accounts();
	print "\r\nRESULT COUNT: ".count($data_array)."\r\n";
	$count = 0;
	$body = null;	
	for($i=0; $i<count($data_array); $i++)
	{
		$item = $data_array[$i];
		$wphone = str_replace("-", "", $item['work_phone']);
		$wphone = substr($wphone,0,3)."-".substr($wphone,3,3)."-".substr($wphone,6,4);
		
		$hphone = str_replace("-", "", $item['home_phone']);
		$hphone = substr($hphone,0,3)."-".substr($hphone,3,3)."-".substr($hphone,6,4);
		$post_ary = array
		(
			 'firstName' 	=> strtolower($item['first_name'])
			,'lastName' 	=> strtolower($item['last_name'])
			,'DirectDeposit'=> 'Yes'
			,'address' 		=> strtolower($item['address_1'])
			,'city' 		=> strtolower($item['city'])
			,'state' 		=> strtoupper($item['state'])
			,'zip' 			=> $item['zip']
			,'email' 		=> strtolower($item['email'])
			,'homePhone' 	=> $hphone
			,'workPhone' 	=> $wphone	
			,'workExt' 		=> ''	
			,'bestTime'		=> strtolower($item['best_call_time'])
		);	
		$body = post_data($post_ary, $post_url, $item['email']);

		print "\nRESPONSE: ".$body."\r\n";
		
		if ($body == "ACCEPT")
		{
			$count = $count + 1;
		}
		if ($count == $max_per_day)
		{
			break;
		}		
	} 

	// POST_DATA FUNCTION
	//============================================================	

	function post_data($post_ary, $post_url, $email)
	{
		$net = new HTTP_Request($post_url);

		$net->setMethod(HTTP_REQUEST_METHOD_POST);
		reset($post_ary);
		while (list($k, $v) = each($post_ary))
		$net->addPostData($k, $v);

		$net->sendRequest();

		print "\r\nLEAD: ".$email;

		$body_whole = $net->getResponseBody();
		return $body_whole;
	}
	
	// HIT STAT
	//============================================================	
		
	print "\nHITTING STAT COLUMN: ";
                     
    $db_info = array('db'       => 'management',
                     'host'     => 'writer.olp.ept.tss',
                     'user'     => 'sellingsource',
                     'password' => 'password');
		 	
	$sql = new MySQL_4($db_info['host'], $db_info['user'], $db_info['password']);
	$sql->Connect(TRUE);
	$sql->db_info = $db_info;
	$sql->db_type = 'mysql';
	
	$config_obj = new Config_6($sql);
	
	$config = $config_obj->Get_Site_Config(LICENSE_KEY, 
		                                   PROMO_ID, 
		                                   PROMO_SUB_CODE);
	
	@session_start();
	$_SESSION['config'] = $config;
	
	$set_stat = new Set_Stat_3();
	$set_stat->Set_Mode('LIVE');
	
	$_SESSION['stat_info'] = Set_Stat_3::Setup_Stats(NULL,
													 $config->site_id, 
													 $config->vendor_id, 
													 $config->page_id, 
													 $config->promo_id, 
													 $config->promo_sub_code, 
													 $config->promo_status);

	$r = $set_stat->Set_Stat($config->property_id,
							 STAT_COLUMN,
							 $count);
	
	echo $count . ' ' . STAT_COLUMN . ' stats hit\n';
?>