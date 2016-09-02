<?php

	define('REACT_SEND_LOG', '/virtualhosts/cronjobs/react/log/react_send_'.date("Ymd").'.log');
	$fh = fopen(REACT_SEND_LOG, "a") or die("\nCould Not Open Log\n");

	//Runmodes: test, live
	$runmode = 'live';
// 	$runmode = 'test';

	//$debug_email = 'raymond.lopez@sellingsource.com';
	//$debug_email = 'randy.kochis@sellingsource.com';
	//$debug_email = 'tss_test10@hotmail.com';
	//$debug_email = 'TSSTEST@SELLINGSOURCE.COM';
	//$debug_email = 'tss_test10@yahoo.com';
	//$debug_email = 'norbinn.rodrigo@sellingsource.com';
	$debug_email = NULL;  // turns debugging off
	
//	Statpro Stat hitting stuff
	$statpro_key = 'clk';
	$statpro_pass = 'dfbb7d578d6ca1c136304c845';
	define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');
	require_once(BFW_CODE_DIR . 'setup_db.php');
	require_once ('statpro_client.php');
	require_once('config.6.php');
	
// Set the include path to have the libraries available
	ini_set ("include_path", "/virtualhosts/lib/");
	ini_set ("session.use_cookies", "1");
	ini_set ("magic_quotes_runtime", "0");
	ini_set ("magic_quotes_gpc", "1");

	// We need to include the some libs
	require_once ("library.1.php");
	require_once ("error.2.php");

	$lib_path = Library_1::Get_Library ("debug", 1, 0);
	Error_2::Error_Test ($lib_path);
	require_once ($lib_path);

	$lib_path = Library_1::Get_Library ("mysql", 3, 0);
	Error_2::Error_Test ($lib_path);
	require_once ($lib_path);
	require_once ('react_server.php');

	// Build the sql object
	$react_sql = new MySQL_3 ();	
	$sms_sql = new MySQL_3();
	$database = "react_db";

	$result = $react_sql->Connect ("BOTH", $olp_server['host'], $olp_server['user'], $olp_server['pass'], Debug_1::Trace_Code (__FILE__, __LINE__));
	$result3 = $sms_sql->Connect ("BOTH", $ldb_server['host'].":".$ldb_server['port'], $ldb_server['user'], $ldb_server['pass'], Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	Error_2::Error_Test ($result3, TRUE);

	function Process_Info($enable_watch_hosts, $mail_host=NULL) {

		global $database, $react_sql, $debug_email, $sms_sql, $prpc_server, $olp_server, $ldb_server, $fh;
	$update_sql = $react_sql;
	$args = func_get_args();
	fwrite($fh, print_r($args, 1));

		// set to TRUE if you want to skip over the big dogs
		// If it's FALSE, remember to set the big dog you want
		//  to send
		//$enable_watch_hosts = FALSE;
		$watchhosts = array(
			'hotmail.com',
			'aol.com',
			'yahoo.com',
		);

		/* CHANGE THESE VARIABLES TO SEND BY HOST ONLY */
		/* 	IE, SEND ONLY TO YAHOO EMAIL ACCOUNT, AOL, etc. */
		$where_args = array();
		$host_limit = 0;
		$where_args[]  = "send_status='not_sent'";

		// removed this because we always want to send out the reacts no matter how old - rsk
		/*
		if (date("l")=='Tuesday')
		{
//			$where_args[]  = "date_added>='".date('Y-m-d')."'";
		}
		else
		{
//			$where_args[]  = "date_added>='".date('Y-m-d', strtotime('last tuesday'))."'";
		}
		*/
		

		if (!$enable_watch_hosts && !is_null($mail_host))
		{
			$where_args[] = "email_primary regexp '".$mail_host."$'";
			$host_limit = 500;
		}
		
		$q = "SELECT * FROM send_list";

		//$q = "SELECT * FROM send_list WHERE ssn='234122356'";
		if (sizeof($where_args)>0)
		{
			$q.= " WHERE ".implode(' AND ', $where_args);
		}
		$q.= " ORDER BY id ";
		if ($host_limit>0)
		{
			$q.= " LIMIT {$host_limit}";
		}
		elseif (SEND_LIMIT>0)
		{
			$q .= " LIMIT ".SEND_LIMIT;
		}

		$property_shorts = array(
			'Ameriloan' => 'ca',
			'500FastCash' => 'd1',
			'OneClickCash' => 'pcl',
			'UnitedCashLoans' => 'ucl',
			'USFastCash' => 'ufc',
		);

		$r = $react_sql->Query($database, $q, Debug_1::Trace_Code (__FILE__, __LINE__));
		if (!$react_sql->Row_Count($r)>0)
		{
			fwrite($fh, "No records to send out!!!\n");
		}

// 		include_once("prpc/client.php");

// 		$ole_react_mail = new Prpc_Client("prpc://smtp.2.soapdataserver.com/ole_smtp.1.php");
// 		$ole_react_mail->setPrpcDieToFalse();
		
	while ($row = $react_sql->Fetch_Array_Row($r))
		{
			$name = explode(' ', $row['email_primary_name']);
			$name = ucwords(strtolower($name[0]));
			$email_data = array(
				'site_name' => $row['site_name'],
				'site_name_email' => strtolower($row['site_name']),
				'link' => $row['link'],
				'email_primary' => $row['email_primary'],
				'email_primary_name' => ucwords(strtolower($row['email_primary_name'])),
				'email' => $row['email_primary'],
				'name' => $name,
				'expire_date' => date('F j, Y', strtotime('+2 weeks')),
			);

			if (!is_null($debug_email))
			{
				$email_data['email_primary'] = $debug_email;
			}

			$parts = explode('@', $row['email_primary']);
			$hostname = strtolower($parts[1]);


			if ($enable_watch_hosts && in_array($hostname, $watchhosts))
			{
				continue;
			}

// Query for the necessary information needed to hit statpro stat
			$query4 = "select distinct a.application_id,
						c.name_short,
						a.track_id,
						ci.promo_id,
						a.application_status_id,
						site.license_key
					FROM application a, company c
					JOIN campaign_info ci ON ci.campaign_info_id = (
					SELECT MAX(ci.campaign_info_id) FROM campaign_info ci
						WHERE ci.application_id = a.application_id)
					JOIN site ON ci.site_id = site.site_id
					WHERE a.company_id = c.company_id
					AND a.email = '{$email_data['email_primary']}'
					AND c.name_short='{$property_shorts[$row['site_name']]}'
					ORDER BY a.date_created DESC
					LIMIT 1";

			$result4 = $sms_sql->Query($ldb_server['db'], $query4, Debug_1::Trace_Code (__FILE__, __LINE__));
			$row4 = $sms_sql->Fetch_Array_Row($result4);
			// Hit the react_start stat for given email
			Start_React_Process($row['email_primary'], $row4);

			// for testing purposes
			//$email_data['email_primary'] = 'norbinn.rodrigo@sellingsource.com';
/*
			$count = 0;
			$react_mailing_id = '';
			$failed =FALSE;
			while($react_mailing_id = $ole_react_mail->Ole_Send_Mail ("react_email", NULL, $email_data)==0)
			{
				$failed = FALSE;
				$count++;
				fwrite($fh,  "Resend: " . $email_data['email_primary'] . "\n");
				sleep(1);
				$failed = TRUE;
				if ($count>5) break;
			}

			if (!$failed)
			{
				// We need to find the track id for this app
				$query3 = "select 
								track_id 
							from 
								application a,
								company c
							where 
								a.company_id=c.company_id
							and
								c.name_short='".$property_shorts[$row['site_name']]."'
							and
								phone_cell != ''
							and 
								ssn = '".$row['ssn']."' 
							order by application_id DESC LIMIT 1";
				$result3 = $sms_sql->Query($ldb_server['db'], $query3, Debug_1::Trace_Code (__FILE__, __LINE__));	
				// We found a SSN for this react so lets stat it and send them a SMS react
				while ($row3 = $sms_sql->Fetch_Array_Row($result3)) // get a row from our result set
				{
					if ($row3['track_id'])
					{
						$prpc_sms = new Prpc_Client($prpc_server, FALSE, 32);
						fwrite($fh, "  Track ID: ".$row3['track_id']."\n");
						$varsity = $prpc_sms->SMSCronReact($row3['track_id'], $property_shorts[$row['site_name']]);
					}
				}
			}*/

			fwrite($fh, "Processed: ".$email_data['email_primary']."\n");

			if (!is_null($debug_email))
			{
				die;
			}

// 			$up_q = "UPDATE send_list SET send_status='sent' WHERE id=".$row['id'];
// 			if (!$up_r = $update_sql->Query($database, $up_q, Debug_1::Trace_Code (__FILE__, __LINE__)))
// 			{
// 				fwrite($fh, "ID ".$row['id']." not updated!!!\n");
// 			}

		}

	}

	//Function to hit the 'react_start' stat given an email address
	function Start_React_Process($email, $reacts)
	{
		global $statpro_key, $statpro_pass, $testflag, $runmode;
		
		if($runmode != 'live')
		{
			$setup_db_mode = 'local';
			$statpro_mode = 'test';
		}
		else
		{
			$setup_db_mode = $statpro_mode = 'live';
		}
		$sql = Setup_DB::Get_Instance('management', $setup_db_mode);
		$config_obj = new Config_6($sql);
		$bin = '/opt/statpro/bin/spc_'.$statpro_key.'_'.strtolower($statpro_mode);
		$statpro = new StatPro_Client($bin, NULL, $statpro_key, $statpro_pass);
		$lic_key = $reacts['license_key'];
		$promo_id = $reacts['promo_id'];
		$promo_sub_code = $reacts['promo_sub_code'];
		if(!empty($lic_key) && $lic_key != 'Array')
		{
    		$config = $config_obj->Get_Site_Config($lic_key, $promo_id, $promo_sub_code);
 	  		$statpro->Get_Space_Key($config->page_id, $promo_id, $promo_sub_code);
 	  		$statpro->Track_Key($reacts['track_id']);
			$statpro->Record_Event('react_start');
		}
          	        
	}	

	// $enable_watch_hosts = TRUE; -> for the big 3 aol, hotmail, yahoo
	Process_Info(TRUE);
	Process_Info(FALSE, 'HOTMAIL.COM');
	Process_Info(FALSE, 'AOL.COM');
	Process_Info(FALSE, 'YAHOO.COM');

	fwrite($fh, "\n\nreact_send_email.php is now complete\n");
	fclose($fh);

?>
