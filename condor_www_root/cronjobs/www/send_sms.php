<?

/**

	Desc:
		Queue SMS messages by inserting into the sms database
		4 days prior to due date

	*** TESTING ***
	In order to test these crons, I've provided a few utilities.  $debug will run
	the first iteration of any loop (using TEST_NUMBER) and display the results
	to the screen

	$mode = test: set this to test lib/send_sms.1.php in lib mode.  The test numbers
	are located in the lib file.  Comment our the "return TRUE" and uncomment a test
	number in send_sms.1.php::Send_Message in order to test.

	Auth:
		N. Rodrigo
 
	Date:
		09/12/05

Usage example:
/usr/lib/php5/bin/php send_sms.php due_date impact
/usr/lib/php5/bin/php send_sms.php agreed impact
/usr/lib/php5/bin/php send_sms.php due_date clk
/usr/lib/php5/bin/php send_sms.php agreed clk


 */

define('EMAIL_NOTIFY', 'christopher.barmonde@sellingsource.com');
define('TEST_NUMBER', '7025809495');
$sql = '';
$page_id_obj = new page_id_class();

// increment to set agreed stat
define('INCREMENT', 15);

if (!isset($_SERVER['argv'][1]))
{
	die ("Invalid command line option.\nUsage: send_sms.php [procecure=due_date|agreed|funded [type=impact|clk");
}
else
{
	$proc = $_SERVER['argv'][1];
}

if (!isset($_SERVER['argv'][2]))
{
	die ("Invalid command line option.\nUsage: send_sms.php [procecure=due_date|agreed|funded [type=impact|clk");
}
else
{
	$type = $_SERVER['argv'][2];
}

//$debug = TRUE;
$debug = FALSE;
define('MODE', 'live');
define('MAIL_MODE','LIVE');
define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');
//define('MODE', 'test');
//define('MAIL_MODE','RC');

//ini_set('error_reporting', 'E_ALL & ~E_NOTICE & ~E_WARNING');

require_once('/virtualhosts/lib/send_sms.1.php');
require_once('/virtualhosts/lib/mysql.4.php');
require_once('/virtualhosts/lib/statpro_client.php');
require_once ("prpc/client.php");

require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
require_once(BFW_CODE_DIR.'Enterprise_Data.php');

//$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/ole_smtp.1.php");
$sms =& new Send_SMS();


switch ($proc)
{
	case 'due_date':
	{
		switch ($type)
		{
			case 'impact':
				$statpro_key = 'imp';
				$statpro_pass = 'h0l3iny0urp4nts';
				$which_db = 'ldb_impact';
				break;
			case 'clk':
			default:
				$statpro_key = 'clk';
				$statpro_pass = 'dfbb7d578d6ca1c136304c845';
				$which_db = 'ldb';
				break;
		}

		define('STATPRO_2_BIN', '/opt/statpro/bin/spc_'.$statpro_key.'_'.MODE);
		define('STATPRO_2_OPT', '-v');
		$statpro2 = new StatPro_Client(STATPRO_2_BIN, STATPRO_2_OPT, $statpro_key, $statpro_pass);

		// SMS notify due dates four days from now.  Run once daily.
		// {php5_bin} send_sms.php due_date
		db_config($which_db);

		$date_first_payment = date("Y-m-d", strtotime('+4 days'));
		
		$q = "
			SELECT DISTINCT
				application.phone_cell,
				application.application_id,
				application.date_first_payment,
				application.track_id,
				site.license_key,
				campaign_info.promo_id,
				campaign_info.promo_sub_code,
				company.name_short as company
			FROM
				application
				JOIN campaign_info ON campaign_info.campaign_info_id = (
					SELECT MAX(ci2.campaign_info_id)
					FROM campaign_info ci2
					WHERE ci2.application_id = application.application_id
				)
				JOIN site ON campaign_info.site_id = site.site_id
				JOIN company ON application.company_id = company.company_id
			WHERE
				application.application_status_id = 20
				AND application.date_first_payment = '$date_first_payment'";

		$cell_phone_list = array();
		if ($r = $sql->Query($which_db, $q))
		{
			while ($res = $sql->Fetch_Array_Row($r))
			{
				$cell_phone = trim($res['phone_cell']);
				$company = $res['company'];
				
				// Set StatPro variables and hit payment_due
				$page_id = $page_id_obj->Get_Page_ID($res['license_key']);
				$space_key = $statpro2->Get_Space_Key($page_id, $res['promo_id'], $res['promo_sub_code']);
				$track_key = $statpro2->Track_Key($res['track_id']);
				$stat_processed = $statpro2->Record_Event('payment_due');
				
				$data = array(
					'msg_override' => "Your loan with {$sms->ent_sites[strtolower($company)]['site']} is due soon.  Be sure money is in your checking account to cover the amount due. {$sms->ent_sites[strtolower($company)]['phone']}",
				);
				
				if ($cell_phone && !in_array($cell_phone, $cell_phone_list))
				{
					if ($debug)
					{
						$cell_phone = TEST_NUMBER;
					}
					echo("Trying {$cell_phone}...\n");
					if ($status = $sms->Send_Message($cell_phone, $proc, $company, $space_key, $track_key, MODE, $data))
					{
						$stat_processed = $statpro2->Record_Event('sms_due_date');
					}
					else
					{
						// record event in statpro that we tried to send to a removed number
						$stat_processed = $statpro2->Record_Event('sms_due_date_removed');
					}
					$cell_phone_list[] = $cell_phone;
				}
				
				if ($debug)
				{
					Debug_Output();
					break;
				}
			}
		}
	}
	break;
	case 'agreed':
	{

		switch ($type)
		{
			case 'impact':
				$statpro_key = 'imp';
				$statpro_pass = 'h0l3iny0urp4nts';
				$which_db = 'olp';
				$companies = Enterprise_Data::getCompanyProperties(Enterprise_Data::COMPANY_IMPACT);
				$query_start_date = date("YmdHis", strtotime('-16 minutes'));
				$query_end_date = date("YmdHis", strtotime('-1 minutes'));
				break;
			case 'clk':
			default:
				$statpro_key = 'clk';
				$statpro_pass = 'dfbb7d578d6ca1c136304c845';
				$which_db = 'olp';
				$companies = array('ca', 'd1', 'pcl', 'ucl', 'ufc');
				$query_start_date = date("YmdHis", strtotime('-2 hours'));
				$query_end_date = date("YmdHis", strtotime('-1 hour -45 minutes'));
				break;
		}

		define('STATPRO_2_BIN', '/opt/statpro/bin/spc_'.$statpro_key.'_'.MODE);
		define('STATPRO_2_OPT', '-v');
		$statpro2 = new StatPro_Client(STATPRO_2_BIN, STATPRO_2_OPT, $statpro_key, $statpro_pass);


		// SMS notify agreed (to confirm) 2 hours from agreeing.  Run every 15 minutes.
		// {php5_bin} send_sms.php agreed
		db_config($which_db);

		
		$q = "
			SELECT STRAIGHT_JOIN
				application.application_id AS app_id,
				cell_phone,
				property_short AS company,
				license_key,
				promo_id,
				promo_sub_code
			FROM
				application
				JOIN campaign_info ON application.application_id = campaign_info.application_id
				JOIN target ON application.target_id = target.target_id
				JOIN personal_encrypted ON application.application_id = personal_encrypted.application_id
			WHERE
				application_type = 'AGREED'
				AND application.created_date BETWEEN $query_start_date AND $query_end_date
				AND cell_phone != ''
				AND property_short IN ('".implode("', '", $companies)."')";

		if ($r = $sql->Query($which_db, $q))
		{
            $fname = "$type_".date('Y-m-d',time()/*strtotime('tomorrow')*/).'.log';
            //if the log file doesn't exist, it means it's a new day
            //or the first run. We'll attempt and mail the log from 
            //yesterday if we have a log file.
            $send_yesterday = !file_exists($fname);

			//open the file for writing	
			$file = fopen($fname,'a');
			if($sql->Row_Count($r) > 0)
			{
				// Tell the cron what company we're sending this for.
				fwrite($file, 'Company: ' . strtoupper($type) . "\n\n");
			}
			
			while ($res = $sql->Fetch_Array_Row($r))
			{
				$cell_phone = !$debug ? $res['cell_phone'] : TEST_NUMBER;
				$company = $res['company'];
				$promo_id = $res['promo_id'];
				$promo_sub_code = $res['promo_sub_code'];
				$page_id = $page_id_obj->Get_Page_ID($res['license_key']);
				$app_id = $res['app_id'];
				$space_key = $statpro2->Get_Space_Key($page_id, $res['promo_id'], $res['promo_sub_code']);
				$track_key = $statpro2->Create_Track();
				

				if(Enterprise_Data::isCompanyProperty(Enterprise_Data::COMPANY_IMPACT, $company))
				{
					$impact_data = Enterprise_Data::getEnterpriseData($company);
					$data['msg_override'] = "To receive your cash loan, please call {$impact_data['legal_entity']} immediately to confirm. {$impact_data['phone']}.";
				}
				else
				{
					$data['msg_override'] = "Reminder: You must confirm to get your cash from {$sms->ent_sites[strtolower($company)]['site']}.  Check your email to confirm or call {$sms->ent_sites[strtolower($company)]['support']}.";
				}
				
				if ($status = $sms->Send_Message($cell_phone, $proc, $company, $space_key, $track_key, MODE, $data))
				{
					$stat_processed = $statpro2->Record_Event('sms_confirm_reminder');
					
					// Echo to cron, so that we get a list of who is being sent text messages [BrianF]
					//echo "Sent - App ID: $app_id, Number: $cell_phone\n";
					fwrite($file, "Sent - App ID: $app_id, Number: $cell_phone\n");
				}
				else
				{
					$msg = "Application ID: {$app_id}\nCell Phone: {$cell_phone}";
					send_mail($msg, "Cron Record Failed ({$proc})");
					// record event in statpro that we tried to send to a removed number
					$stat_processed = $statpro2->Record_Event('sms_confirm_reminder_removed');
				}
				
				if ($debug)
				{
					Debug_Output();
					fclose($file);
					//exit;
					break;
				}
			}
			if(isset($send_yesterday) && $send_yesterday === TRUE)
			{
				$fname = "$type_".date('Y-m-d',strtotime('Yesterday') ).'.log';
				Send_Yesterdays_Log($fname);
			}
		}
	}
	break;
	case 'funded':
	{
		// this will be run from olp_mysql_paperless_event_mailers.php:
		//  {php5_binary} send_sms.php funded {app_id}
		if (!isset($_SERVER['argv'][2]))
		{
			$msg = "<pre>App ID not supplied for funded trigger<br><br>".print_r($_SERVER['argv'],1);
			send_mail($msg, "Funded Trigger Error");
			die;
		}
		else
		{
			$application_id = $_SERVER['argv'][2];
		}
		$db = 'ldb';
		db_config($db);
		$q = "SELECT ".
			"distinct phone_cell, ".
			"company.name_short as company, ".
			"application.application_id, ".
			"license_key, ".
			"promo_id, ".
			"promo_sub_code, ".
			"date_first_payment ".
		"FROM ".
			"company, ".
			"campaign_info, ".
			"site, ".
			"application ".
		"WHERE ".
			"application.company_id=company.company_id AND ".
			"application.application_id=campaign_info.application_id AND ".
			"campaign_info.site_id=site.site_id AND ".
			"application.application_id=".$application_id;
		if ($r = $sql->Query($db, $q)) {
			$res = $sql->Fetch_Array_Row($r);
			$cell_phone = !$debug ? $res['phone_cell'] : TEST_NUMBER;
			$company = $res['company'];
			$page_id = $page_id_obj->Get_Page_ID($res['license_key']);
			$space_key = $statpro2->Get_Space_Key($page_id, $res['promo_id'], $res['promo_sub_code']);
			$track_key = $statpro2->Create_Track();
			$data = array(
				'msg_override' => "Reminder: You're due date in on ".date("m/d/Y", strtotime($res['date_first_payment'])),
			);
			if ($status = $sms->Send_Message($cell_phone, $proc, $company, $space_key, $track_key, MODE, $data))
			{
				$stat_processed = $statpro2->Record_Event('sms_funded');
			}
			else
			{
				// record event in statpro that we tried to send to a removed number
				$stat_processed = $statpro2->Record_Event('sms_funded_removed');
			}
			
			if ($debug)
			{
				Debug_Output();
				break;
			}
		}
	}
	break;

}

function Get_Page_ID($license_key)
{
	global $sql;
	$db = 'management';
	db_config($db);
	$q = "SELECT page_id FROM license_map WHERE license='".$license_key."' LIMIT 1";
	$r = $sql->Query($db, $q);
	$res = $sql->Fetch_Array_Row($r);
	return $res['page_id'];
}

function Debug_Output()
{
	global $page_id, $promo_id, $promo_sub_code, $space_key, $track_key, $company, $stat_processed, $status;
	echo "Page ID: ".$page_id."\n";
	echo "Promo ID: ".$promo_id."\n";
	echo "Promo Sub Code: ".$promo_sub_code."\n";
	echo "Space Key: ".$space_key."\n";
	echo "Track Key: ".$track_key."\n";
	echo "Company: ".$company."\n";
	echo "Stat Processed?: ".$stat_processed."\n";
	echo "Status: ".$status."\n";
}

function send_mail($msg, $subject='Message from send_sms.php')
{
	$tx = new OlpTxMailClient(false,MAIL_MODE);
	//var_dump(MAIL_MODE);exit;
	$data = array(
		'sender_name' => 'SMS Alerts <'.EMAIL_NOTIFY.'>',
		'subject' => $subject,
		'message' => $msg,
		'email_primary_name' => 'Chris Barmonde',
		'email_primary' => EMAIL_NOTIFY,
	);
	
	try 
	{
		$result = $tx->sendMessage('live', 'SMS_ALERTS', $data['email_primary'], '', $data);
	}
	catch(Exception $e)
	{
		$result = FALSE;
	}
	
	return ($result === FALSE) ? FALSE : TRUE;
}
function Send_Yesterdays_Log($log_file)
{
	if(file_exists($log_file))
	{
		$tx = new OlpTxMailClient(false,MAIL_MODE);
		$data = array(
			'sender_name' => 'SMS Alerts <'.EMAIL_NOTIFY.'>',
			'subject'=> 'SEND_SMS Report',
			'message' => "SMS Alerts from yesterday.",
			'email_primary_name' => 'Chris Barmonde',
			'email_primary' => EMAIL_NOTIFY,
		);
		$d = file_get_contents($log_file);
		$attach = array(
			array(
				'method' => 'ATTACH',
				'filename' => $log_file,
				'mime_type' => 'text/plain',
				'file_data' => gzcompress($d),
				'file_data_size' => strlen($d)
			)
		);
		try 
		{
			$result = $tx->sendMessage('live', 'CRON_EMAIL_OLP', $data['email_primary'], '', $data,$attach);
			unlink($log_file);
		}
		catch(Exception $e)
		{
			$result = FALSE;
		}
	}
	
	return ($result === FALSE) ? FALSE : TRUE;	
}

function db_config($type)
{
	global $sql;
	switch ($type)
	{
		case 'ldb':
			if (MODE=='test')
			{
				$db = array(
					'username' => 'ldb_writer',
					'hostname' => 'db101.clkonline.com:3308',
					'password' => 'password',
				);
			}
			else
			{
				$db = array(
					'username' => 'olp',
					'hostname' => 'writer.ecashclk.ept.tss:3308',
					'password' => 'password',
				);
			}
		break;
		case 'olp':
		case 'management':
			if (MODE=='test')
			{
				$db = array(
					'username' => 'sellingsource',
					'hostname' => 'writer.olp.ept.tss',
					'password' => 'password',
				);
			}
			else
			{
				$db = array(
					'username' => 'sellingsource',
					'hostname' => 'writer.olp.ept.tss',
					'password' => 'password',
				);
			}
		break;
		case 'ldb_impact':
			if (MODE=='test')
			{
				$db = array(
					'username' => 'ecash',
					'hostname' => 'monster.tss:3318',
					'password' => 'password',
				);
			}
			else
			{
				$db = array(
					'username' => 'olp',
					'hostname' => 'writer.ecashimpact.ept.tss:3307',
					'password' => 'password',
				);
			}
		break;
		default:
			return FALSE;
		break;
	}
	$sql = new MySQL_4($db['hostname'], $db['username'], $db['password']);
	$sql->Connect();
}

class page_id_class {

	private $sql;

	function __construct()
	{

		if (preg_match("/^ds(.*)\.tss$/", $_SERVER['HOSTNAME']))
		{
			$db = array(
				'username' => 'sellingsource',
				'hostname' => 'writer.olp.ept.tss',
				'password' => 'password',
			);
		}
		else
		{
			$db = array(
				'username' => 'sellingsource',
				'hostname' => 'writer.olp.ept.tss',
				'password' => 'password',
			);
		}

		require_once('/virtualhosts/lib/mysql.4.php');

		$this->sql = new MySQL_4($db['hostname'], $db['username'], $db['password']);
		$this->sql->Connect();

	}

	function Get_Page_ID($license)
	{
		$q = "SELECT page_id FROM license_map WHERE license='".$license."' LIMIT 1";
		$r = $this->sql->Query('management', $q);
		$res = $this->sql->Fetch_Array_Row($r);
		return $res['page_id'];
	}

}

?>
