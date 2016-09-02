#!/usr/lib/php5/bin/php
<?php
/**
 * Check for Pending Confirmed Impact Apps
 * 
 * Pickup any applications within the past 15-30 minutes that have gone to Impact and are in the 'Pending' or 'Confirmed' status.
 * 
 * @author Rob Voss (rob.voss@sellingsource.com>
 * 
 * @version
 * 	    1.0.0 Jul 16, 2007 - Rob Voss (rob.voss@sellingsource.com>
 */

define('BFW_OLP_DIR', '/virtualhosts/bfw.1.edataserver.com/include/modules/olp/');
define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');
// define applog constants
if(!defined('APPLOG_SIZE_LIMIT')) define('APPLOG_SIZE_LIMIT', '1000000000');
if(!defined('APPLOG_FILE_LIMIT')) define('APPLOG_FILE_LIMIT', '20');
if(!defined('APPLOG_ROTATE')) define('APPLOG_ROTATE', FALSE);
if(!defined('APPLOG_ALL_SUBDIRECTORY')) define('APPLOG_ALL_SUBDIRECTORY','all');
if(!defined('APPLOG_UMASK')) define('APPLOG_UMASK',002);

require_once('mysql.4.php');
require_once(BFW_CODE_DIR.'server.php');
require_once(BFW_CODE_DIR.'setup_db.php');
require_once('automode.1.php');
require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');

$mail_recip = 'applications@impactpayments.com';
//$mail_recip = 'adam.englander@sellingsource.com';

//Get the mode
if(isset($argv[1]))
{
	$mode = strtoupper($argv[1]);
}
else
{
	// Check autmode for the current mode
	$auto_mode = new Auto_Mode();
	$mode = $auto_mode->Fetch_Mode($_SERVER['SERVER_NAME']);
}

// Create trendex mail object now that the mode has been determined
$tx = new OlpTxMailClient(false,$mode);

// Pull the applications to email
$applications = Get_Pending_Complete_Apps($mode);

if(count($applications))
{
	// Send an email for each Application
	foreach($applications as $app_data)
	{
		Send_Mail($tx, $app_data, $mail_recip);
	}
}
else 
{
	$ole_applog = Applog_Singleton::Get_Instance(APPLOG_ALL_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE, APPLOG_UMASK);
	$ole_applog->Write("No records found!");
}

function Send_Mail(&$tx, $data, $mail_recip)
{
	$login_hash = md5($data['application_id'] . 'l04ns');
	
	$email_data = array(
		'email_primary'			=> $mail_recip,
		'email_primary_name'	=> $mail_recip,
		'source' 				=> 'ic',
		'signup_date'			=> $data['application_date'],
		'ip_address'			=> $data['ip_address'],
		'firstname'				=> $data['name_first'],
		'lastname'				=> $data['name_last'],
		'transaction_id'		=> $data['application_id'],
		'ent_url'				=> 'http://impactcashusa.com/?application_id='.urlencode(base64_encode($data['application_id'])).'&page=ent_cs_login&login='.$login_hash
	);

	$email_data = array_merge($email_data, $data);
	
	try 
	{
		$r = $tx->sendMessage('live', 'Unsigned_App_Info', $email_data['email_primary'], '', $email_data);
	}
	catch(Exception $e)
	{
		$r = FALSE;
	}

	if($r === FALSE)
	{
		$ole_applog = Applog_Singleton::Get_Instance(APPLOG_ALL_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE, APPLOG_UMASK);
		$ole_applog->Write("TrendEx Send Mail failed.  Last message: \n" . print_r($email_data, true) . "\nCalled from " . __FILE__ . ':' . __LINE__);
	}
}

function Get_Pending_Complete_Apps($mode)
{
	// Set the dates for 15 and 30 minutes ago.
	$fifteen_ago  = date('YmdHis', mktime(date('H'), date("i")-15, 0, date("m")  , date("d"), date("Y")));
	$thirty_ago = date('YmdHis', mktime(date('H'), date("i")-30, 0, date("m"), date("d"), date("Y")));

	if(strcasecmp($mode, 'live') === 0)
	{
		$mode = 'slave';
	}
	
	$olp_sql = Setup_DB::Get_Instance("Blackbox", $mode);
	$olp_db = $olp_sql->db_info["db"];

	// Add 227 to search target_id where statement to include ic_t1. GForge #3034 [DW]
	$query = "
			SELECT DISTINCT
				a.application_id,
				a.created_date	AS application_date,
				p.first_name	AS name_first,
				p.last_name		AS name_last,
				p.email			AS email,
				p.cell_phone	AS phone_cell,
				p.home_phone	AS phone_home,
				e.date_of_hire	AS work_date_of_hire,
				e.employer		AS work_name,
				e.shift			AS work_shift,
				e.work_phone	AS phone_work,
				i.pay_frequency	AS income_frequency,
				i.monthly_net	AS income_amount,
				r.address_1		AS address_street,
				r.apartment		AS address_unit,
				r.city			AS address_city,
				r.state			AS address_state,
				r.zip			AS address_zip,
				c.url			AS originating_address,
				c.ip_address	AS ip_address
			FROM application a
				JOIN personal_encrypted p USING (application_id)
				JOIN employment e USING (application_id)
				JOIN income i USING (application_id)
				JOIN residence r USING(application_id)
				JOIN status_history h USING(application_id)
				JOIN application_status s ON h.application_status_id = s.application_status_id
				JOIN campaign_info c ON c.application_id = a.application_id
			        AND c.active = 'TRUE'
			WHERE
				a.application_type IN ('PENDING', 'CONFIRMED')		-- current application status
				AND s.name IN ('PENDING', 'CONFIRMED')				-- status that recently happened, enables date_created clause 
				AND h.date_created BETWEEN '{$thirty_ago}' AND '{$fifteen_ago}'
				AND a.target_id IN ('74','227','241')";

	$result = $olp_sql->Query($olp_db, $query);
	
	while(($row = $olp_sql->Fetch_Array_Row($result)))
	{
		$apps_changed[] = $row;
	}

	return $apps_changed;
}
?>
