<?php

	define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');
 	define('REACT_GEN_LOG', '/virtualhosts/cronjobs/react/log/react_gen_'.date("Ymd").'.log');
	$fh = fopen(REACT_GEN_LOG, "a");

	$testflag = 'False';
	$runmode = 'live';
	// Set to true for testing on RC, false for LIVE
//	$runmode = 'test';
	$mail_mode = ($runmode == 'live'?'LIVE':'RC');

	$skip_check = TRUE; // skip table check
//	$skip_check = FALSE; // skip table check

	$debug_email = NULL;  // turns debugging off
//	$debug_email = 'adam.englander@sellingsource.com';

	//echo "show_aba <br>";
	//echo "Route: " . $routing_number . "<br>";
	//echo "Account: " . $account_number . "<br>";
	
	// Set the include path to have the libraries available
	ini_set ("session.use_cookies", "1");
	ini_set ("magic_quotes_runtime", "0");
	ini_set ("magic_quotes_gpc", "1");

	// We need to include the some libs
	require_once ("library.1.php");
	require_once ("error.2.php");
	require_once("prpc/client.php");
	
	$lib_path = Library_1::Get_Library ("debug", 1, 0);
	Error_2::Error_Test ($lib_path);
	require_once ($lib_path);

	$lib_path = Library_1::Get_Library ("mysql", 3, 0);
	Error_2::Error_Test ($lib_path);
	require_once ($lib_path);
	require_once ('react_server.php');

	// Build the sql object
	$insert_sql = new MySQL_3 ();	
	$sms_sql = new MySQL_3();	// connection for SMS Messages / and LDB
	$react_sql2 = new MySQL_3();	// connection for output of react emails
	$react_sql = new MySQL_3 ();	
	// removed d1 from list for now
	//$data_files = array ("ca","d1","pcl","ucl","ufc");

	// UFC is now using the new method... woohoo!
	$data_files = array ("ca");
	$insert_result = $insert_sql->Connect ("BOTH", $olp_server['host'], $olp_server['user'], $olp_server['pass']);
	$result3 = $sms_sql->Connect ("BOTH", $ldb_server['host'].":".$ldb_server['port'], $ldb_server['user'], $ldb_server['pass']);
	$result2 = $react_sql2->Connect ("BOTH", $olp_server['host'], $olp_server['user'], $olp_server['pass']);
	$result = $react_sql->Connect ("BOTH", $olp_server['host'], $olp_server['user'], $olp_server['pass']);
		
	Error_2::Error_Test ($result, TRUE);

	if (!$skip_check)
	{
		if (is_null($debug_email) || $testflag=='False')
		{
			foreach ($data_files AS $property_short)
			{
				$query = "SHOW TABLE STATUS LIKE '{$property_short}'";
				$result = $react_sql->Query($olp_server['db'], $query);
				$row = $react_sql->Fetch_Array_Row($result);
				list($date_updated, $time_updated) = explode(" ", $row["Update_time"]);
				$num_rows = $row['Rows'];
				$message = "";

				// if the table wasn't updated today, stop the process
				if (strtotime($date_updated)!=strtotime(date("Y-m-d")))
				{
					$message = "The react tables haven't been updated today";

				}
				else
				{
					// are we still updating tables?
					sleep(3);
					$query = "SHOW TABLE STATUS LIKE '{$property_short}'";
					$result = $react_sql->Query($olp_server['db'], $query);
					$row = $react_sql->Fetch_Array_Row($result);
					$num_rows_now = $row['Rows'];
					if ($num_rows!=$num_rows_now)
					{
						$message = "It looks like we're still updating the tables";
					}
				}

				if (strlen($message))
				{
					$header = array
					(
						"sender_name" => "React Cron Job <no-reply@sellingsource.com>",
						"subject" 	=> "React Cron Job Error",
						"site_name" 	=> "sellingsource.com",
						"message" 	=> $message
					);
					if (is_null($debug_email))
					{
						$recipient = array
						(
							"email_primary_name" => "Chris Barmonde",
							"email_primary" => "christopher.barmonde@sellingsource.com",
						);
					}
					else
					{
						$recipient = array
						(
							"email_primary_name" => "Debug Email",
							"email_primary" => $debug_email
						);
					}

					$data = array_merge($recipient, $header);
					require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
					$tx = new OlpTxMailClient(false,$mail_mode);
					
					$tx->sendMessage('live','CRON_EMAIL_OLP',$data['email_primary'],'',$data);
					die;
				}

			}
		}
	}

	function Process_Info() {

		global
			$data_files,
			$database,
			$react_sql,
			$react_sql2,
			$sms_sql,
			$testflag,
			$debug_email,
			$insert_sql,
			$ldb_server,
			$olp_server,
			$fh;

		$email_list = array();
		$pcount = array();
		
		foreach ($data_files as $datafile)
		{
			$pcount[$datafile] = 0;
			$beforenoreccount[$datafile] = 0; // no record in LDB
			$beforemrecount[$datafile] = 0; // in master remove email
			$beforepregcount[$datafile] = 0; // failed preg
			$beforedoopcount[$datafile] = 0; // duplicate
			//echo "looking at " . $datafile .  "\n";
			$query =  "SELECT
					firstname,
					lastname,
					email,
					ssnumber
				FROM " . $datafile . "
				WHERE
					status = 'INACTIVE' and
					email !='NULL'";
			$result = $react_sql->Query($olp_server['db'], $query);
			$recknt = $react_sql->Row_Count($result);
			switch ($datafile)
			{
				case "ca":
					$datalink = "ameriloan.com";
					$site_name = "Ameriloan";
					$promo = "promo_id=26184"; 
					break;
				case "d1":
					$datalink = "500fastcash.com";
					$site_name = "500FastCash";
					$promo = "promo_id=26181";
					break;
				case "pcl":
					$datalink = "oneclickcash.com";
					$site_name = "OneClickCash";
					$promo = "promo_id=26182";
					break;
				case "ucl":
					$datalink = "unitedcashloans.com";
					$site_name = "UnitedCashLoans";
					$promo = "promo_id=26183";
					break;
				case "ufc":
					$datalink = "usfastcash.com";
					$site_name = "USFastCash";
					$promo = "promo_id=26185";
					break;
			}
			if ($testflag == 'True')
			{
				$datalink = "rc.".$datalink;
			}

			fwrite($fh, "\n ---> Records selected from " .$datafile ."=" . $recknt . " " . $datalink ."\n");
			Error_2::Error_Test ($result,TRUE);
			$counter = 0;
			while ($row = $react_sql->Fetch_Array_Row($result)) // get a row from our result set
			{

				$ldbq = "SELECT count(*) AS n FROM application WHERE ssn='".$row['ssnumber']."'";
				$ldbr = $sms_sql->Query($ldb_server['db'], $ldbq);
				$ldbrow = $sms_sql->Fetch_Array_Row($ldbr);
				$beforenoreccount[$datafile]++;
				if (!$ldbrow['n']>0)
				{
					fwrite($fh, "No record in LDB: ".$row['email']." (".$row['ssnumber'].")\n");
					continue;
				}


				$beforemrecount[$datafile]++;
				
				$master_remove = new Prpc_Client('prpc://cpanel.partnerweekly.com/service/unsub.php');
				if($master_remove->queryUnsubEmail($row['email']))
				{
					fwrite($fh, "In master remove email list: {$row['email']}\n");
					continue;
				}

				$today = date("F j, Y, g:i a");
				$linkkey = md5('tss_'.$row['firstname'].$row['lastname'].$today);
				$link = "http://".$datalink ."/?" . $promo . "&promo_sub_code=mkting_email_react&page=ent_cs_confirm_start&reckey=" . $linkkey;
				$email_data['site_name'] = $site_name;
				$email_data['link'] = $link;
				$email_data['email_primary'] = $row['email'];
				if (!is_null($debug_email))
				{
					$email_data['email_primary'] = $debug_email;
				}
				$beforepregcount[$datafile]++;
				if (!preg_match("/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/" , $email_data['email_primary']))
				{
					fwrite($fh, "Failed Regular Expression: ".$email_data['email_primary']."\n");
					continue;
				}
				$email_data['email_primary_name'] = $row['firstname']." ".$row['lastname'];

				$beforedoopcount[$datafile]++;
				// check to see if we've already attempted to send to this email
				if (isset($email_list[strtoupper($row['email'])]))
				{
					fwrite($fh, "Email already sent: ".$row['email']."\n");
					continue;
				}
				else
				{
					$email_list[strtoupper($row['email'])] = TRUE;
				}

				//$insert_q = "SELECT * FROM send_list WHERE send_status='not_sent' AND email_primary='".strtoupper($row['email'])."'";
				$insert_q = "SELECT * FROM send_list WHERE email_primary='".strtoupper($row['email'])."' AND date_added>='".date("Y-m-d", strtotime("last tuesday"))."'";
				$insert_r = $insert_sql->Query($olp_server['db'], $insert_q);
				$insert_row = $insert_sql->Fetch_Array_Row($insert_r);
				if ($insert_sql->Row_Count($insert_r)>0)
				{
					fwrite($fh, "Email already in send_list queue: ".$row['email']."\n");
					continue;
				}
				$ssnum = str_replace("-","",$row['ssnumber']);
				$insert_q = "INSERT INTO send_list SET
								date_added=CURDATE(),
								site_name='".$site_name."',
								link ='".$link."',
								email_primary='".$row['email']."',
								email_primary_name='".$row['firstname']." ".$row['lastname']."',
								ssn='".$ssnum."'";
				if (!$insert_r = $insert_sql->Query($olp_server['db'], $insert_q))
				{
					fwrite($fh, "Error inserting into send_list: ".$row['email']."\n");
				}

				$pcount[$datafile]++;

				$datesent = date('YmdHIs');

				$query2 = "Insert into react_verify set reckey = '". $linkkey .
					"',namelast='" . addslashes($row['lastname']) .
					"',namefirst='" . addslashes($row['firstname']) .
					"',datesent='" . $datesent .
					"',ssn='" . $ssnum .
					"',property_short='" . $datafile ."' ;";

				$result2 = $react_sql2->Query($olp_server['db'], $query2);
				Error_2::Error_Test ($result2,TRUE);
				$counter++;
				
				if (!is_null($debug_email))
				{
					fwrite($fh, "testing is finished\n");
					die;
				}
			}
			fwrite($fh, "---> ".$counter . " emails sent for " . $datafile . "\n");
			if (!$react_sql->Free_result($result))
			{
				  die ("Can't release query!");
			}
		}
		fwrite($fh, "before mre: \n");
		fwrite($fh, print_r($beforemrecount,1));
		fwrite($fh, "before preg: \n");
		fwrite($fh, print_r($beforepregcount,1));
		fwrite($fh, "before doop: \n");
		fwrite($fh, print_r($beforedoopcount,1));
		fwrite($fh, "final: \n");
		fwrite($fh, print_r($pcount,1));
	}

	Process_Info();
	fwrite($fh, "\n\nreact_gen_email.php is now complete\n");
	fclose($fh);

?>
