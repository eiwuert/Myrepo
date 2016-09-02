<?

	/*************************************
	*
	* This is a weekly cron job to gather multiple ssns used with
	* the same bank_aba and bank_account.  This is by no means
	* the most elegant solution as I'm sure Andrew would've been
	* able to do it all in one query.  But at least it works and
	* it's not an exponential nightmare.
	*
	*				-Norbinn
	*
	************************************/


	require_once('mysql.4.php');

	$current = strtotime('-7 days'); // start at 7/5
	$to = strtotime(date('Y-m-d'));

	$ldb = new MySQL_4('db1', 'norbinnr', 'Lw0CGQIR');
	$ldb->Connect();
	$csv = '';
	$application_ids = array();
	$bank_accounts = array();
	$bank_info = array();
	$app_id_used = array();
	$file = 'multiplessn_'.date("YmdHis").'.csv';
	$path = '/tmp/'.$file;

	$email_recipients = array(
		'norbinn.rodrigo@sellingsource.com',
		'ndempsey@fc500.com',
		'mike.genatempo@sellingsource.com',
	);

	$ldbq = "SELECT ".
					"bank_account, ".
					"count(bank_account) as n ".
				"FROM ".
					"application ".
				"WHERE ".
					"date_created>='".date("Y-m-d H:i:s", $current)."' and ".
					"date_created<'".date("Y-m-d H:i:s",$to)."' ".
				"GROUP BY bank_account HAVING n>1";

	$ldbr = $ldb->Query('ldb', $ldbq);
	if ($ldb->Row_Count($ldbr)>0) {
		while ($ldbres = $ldb->Fetch_Array_Row($ldbr)) {
			$bank_accounts[] = "'".$ldbres['bank_account']."'";
		}
	}

	$ldbq = "SELECT ".
					"bank_account, ".
					"bank_aba, ".
					"count(bank_aba) as n ".
				"FROM ".
					"application ".
				"WHERE ".
					"date_created>='".date("Y-m-d H:i:s", $current)."' and ".
					"date_created<'".date("Y-m-d H:i:s",$to)."' and ".
					"bank_account in (".implode(',',$bank_accounts).") ".
				"GROUP BY bank_aba HAVING n>1";
	$ldbr = $ldb->Query('ldb', $ldbq);
	if ($ldb->Row_Count($ldbr)>0) {
		while ($ldbres = $ldb->Fetch_Array_Row($ldbr)) {
			$bank_account = $ldbres['bank_account'];
			$bank_aba = $ldbres['bank_aba'];
			$bank_info[$bank_account] = $bank_aba;
		}
	}

	foreach ($bank_info as $bank_account => $bank_aba) {
		$ldbq = "SELECT ".
						"count(distinct ssn) as n ".
					"FROM ".
						"application ".
					"WHERE ".
						"date_created>='".date("Y-m-d H:i:s", $current)."' and ".
						"date_created<'".date("Y-m-d H:i:s",$to)."' and ".
						"bank_account='".$bank_account."' and ".
						"bank_aba='".$bank_aba."'";
		$ldbr = $ldb->Query('ldb', $ldbq);
		if ($ldb->Row_Count($ldbr)>0) {
			$ldbres = $ldb->Fetch_Array_Row($ldbr);
			if ($ldbres['n']>1) {
				$ldbq = "SELECT ".
								"application_id ".
							"FROM ".
								"application ".
							"WHERE ".
								"date_created>='".date("Y-m-d H:i:s", $current)."' and ".
								"date_created<'".date("Y-m-d H:i:s",$to)."' and ".
								"bank_account='".$bank_account."' and ".
								"bank_aba='".$bank_aba."'";
				$ldbr = $ldb->Query('ldb', $ldbq);
				if ($ldb->Row_Count($ldbr)>0) {
					while ($ldbres = $ldb->Fetch_Array_Row($ldbr)) {
						$application_ids[] = $ldbres['application_id'];
					}
				}
			}
		}
	}

	$ldbq = "
select
   DISTINCT application.date_created as app_created,
   application.application_id as app_id,
   application.ssn as ssn,
   application.name_first as name_first,
   application.name_last as name_last,
   application.bank_aba as bank_aba,
   application.bank_account as bank_account,
   site.name as url,
   prev_aba.value_before AS prev_aba,
   prev_aba.value_after AS new_aba,
   prev_acct.value_before AS prev_account,
   prev_acct.value_after AS new_account,
   prev_paydate.value_before AS prev_paydate_model,
   prev_paydate.value_after AS new_paydate_model,
   prev_dow.value_before AS prev_day_of_week,
   prev_dow.value_after AS new_day_of_week,
   prev_dom1.value_before AS prev_day_of_month_1,
   prev_dom1.value_after AS new_day_of_month_1,
   prev_dom2.value_before AS prev_day_of_month_2,
   prev_dom2.value_after AS new_day_of_month_2,
   prev_week1.value_before AS prev_week_1,
   prev_week1.value_after AS new_week_1,
   prev_week2.value_before AS prev_week_2,
   prev_week2.value_after AS new_week_2,
   campaign_info.promo_id as promo_id,
   ip_address,
   application.email as email
from
   application,
   campaign_info,
   site
      left join application_audit as prev_aba on
         prev_aba.application_id=application.application_id and
         prev_aba.column_name='bank_aba'
      left join application_audit as prev_acct on
         prev_acct.application_id=application.application_id and
         prev_acct.column_name='bank_account'
      left join application_audit as prev_paydate on
         prev_paydate.application_id=application.application_id and
         prev_paydate.column_name='paydate_model'
      left join application_audit as prev_dow on
         prev_dow.application_id=application.application_id and
         prev_dow.column_name='day_of_week'
      left join application_audit as prev_dom1 on
         prev_dom1.application_id=application.application_id and
         prev_dom1.column_name='day_of_month_1'
      left join application_audit as prev_dom2 on
         prev_dom2.application_id=application.application_id and
         prev_dom2.column_name='day_of_month_2'
      left join application_audit as prev_week1 on
         prev_week1.application_id=application.application_id and
         prev_week1.column_name='week_1'
      left join application_audit as prev_week2 on
         prev_week2.application_id=application.application_id and
         prev_week2.column_name='week_2'
   where
      application.application_id=campaign_info.application_id and
      campaign_info.site_id=site.site_id and
      application.application_id IN (".implode(',',$application_ids).")";
	$ldbr = $ldb->Query('ldb', $ldbq);
	if ($ldb->Row_Count($ldbr)>0) {
		while ($ldbres = $ldb->Fetch_Array_Row($ldbr)) {
			if (empty($csv)) {
				$header_args = array();
				foreach (array_keys($ldbres) as $header) {
					$header_args[] = '"'.$header.'"';
				}
				$csv .= implode(',', $header_args)."\n";
			}
			if (!isset($app_id_used[$ldbres['app_id']])) {
				$args = array();
				foreach ($ldbres as $key => $value) {
					$args[] = '"'.$value.'"';
				}
				$csv .= implode(',', $args)."\n";
			}
			$app_id_used[$ldbres['app_id']] = TRUE;
		}
	}

	require_once("prpc/client.php");

	$fh = fopen($path, 'w');
	fwrite($fh, $csv);
	fclose($fh);

	$subject = "Multiple SSN Report";
	$message = 'Multiple SSN Report from '.$current.' to '.$to.' (CSV attached)'."<br><br><br>".
	"	Paydate Codes and their meanings:<br><br>".
	"        DW - Day of Week (WEEKLY)<br>".
	"           model_data:  day_string_one<br>".
	"        DWPD - Day of Week, Next Pay Day (EVERY OTHER WEEK)<br>".
	"           model_data: day_string_one, next_pay_date<br>".
	"        DMDM - Day of Month, Day of Month (TWICE A MONTH)<br>".
	"           model_data: day_int_one, day_int_two<br>".
	"        WWDW - Week #, Week #, Day of Week (TWICE A MONTH)<br>".
	"           model_data: week_one, week_two, day_string_one<br>".
	"        DM - Day of Month (MONTHLY)<br>".
	"           model_data: day_int_one<br>".
	"        WDW - Week #, Day of Week (MONTHLY)<br>".
	"           model_data: week_one, day_string_one<br>".
	"        DWDM - Day of Week, Day of Month (MONTHLY)<br>".
	"           model_data: day_int_one, day_string_one";
	$from = 'norbinnr@ps1.clkonline.com';
	$to = implode(',',$email_recipients);

	$header = array
	(
		"sender_name" => "Selling Source <no-reply@sellingsource.com>",
		"subject" 	=> $subject,
		"site_name" 	=> "sellingsource.com",
		"message" 	=> $message
	);
	$recipients = array
	(
		array(	"email_primary_name" => "Norbinn Rodrigo",
					"email_primary" => "norbinn.rodrigo@sellingsource.com"),
		array(	"email_primary_name" => "Mike Genatempo",
					"email_primary" => "mike.genatempo@sellingsource.com"),
		array(	"email_primary_name" => "Natalie Dempsey",
					"email_primary" => "ndempsey@fc500.com"),
	);

	for($i=0; $i<count($recipients); $i++) 
	{
		$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/ole_smtp.1.php");						
		$data = array_merge($recipients[$i], $header);
		$data['attachment_id'] = $mail->Add_Attachment(file_get_contents($path), 'application/csv', $file, "ATTACH");
		$result = $mail->Ole_Send_Mail("CRON_EMAIL", 28400, $data);
		if(!$result)
		{
			mail('norbinn.rodrigo@sellingsource.com', 'Multiple Email SSN Error', 'OLE failed to send multiple email report');
		}
	}

	unlink($path);

?>
