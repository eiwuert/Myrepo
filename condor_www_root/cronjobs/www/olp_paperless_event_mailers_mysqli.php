<?php

/*
	cronjob to sent zero-hour emails to olp paperless applicants
	meant to be run from the cmdline
*/

//ini_set('include_path', '../pear:/virtualhosts/lib5:/virtualhosts/lib');
require_once 'config.6.php';
DEFINE('BFW_BASE_DIR', '/virtualhosts/bfw.1.edataserver.com/');		
DEFINE('BFW_MODULE_DIR', BFW_BASE_DIR . 'include/modules/');
DEFINE('BFW_CODE_DIR' , BFW_BASE_DIR . 'include/code/');
DEFINE('BFW_USE_MODULE', 'olp');
require_once BFW_CODE_DIR . 'setup_db.php';

define('RC', FALSE);
define('DEBUG', FALSE);
// if DEBUG is set to true, user's email will be replaced with this one
define('DEBUG_EMAIL',	'adam.englander@sellingsource.com');
// who to email when the script fails?
define('BAIL_NOTIFY',	'bail@tssmasterd.com');

define('PASSWORD_ENCRYPTION', 'ENCRYPT');

//$management = Setup_DB::Get_Instance("management", 'rc');
$management = Setup_DB::Get_Instance("management", 'live');

if (DEBUG)
{
	echo "DEBUG IS ON\n";
	sleep(1);
}

if (!DEBUG)
{
	// Make sure we keep running even if user aborts
	ignore_user_abort(TRUE);
}

// Let it run forever
set_time_limit(0);

// we need this early in case we need to bail!
require_once('/virtualhosts/lib/lib_mail.1.php');


// handle
if (4 <= $argc)
{

	define('OLE_EVENT',	$argv[1]);
	// how long after the signup should this go out? (in minutes)
	define('TIME_TARGET',	intval($argv[2]));
	// until how long after TIME_TARGET (in minutes) do we attempt to send this email?
	define('TIME_WINDOW',	intval($argv[3]));
	// only send it for this property
	if ($argv[4]) define('FORCE_PROPERTY', $argv[4]);
}
else if (DEBUG)
{
	// define legit event to use for testing
	//define('OLE_EVENT',	'OLP_PAPERLESS_2_HOUR_FOLLOWUP');
	
	define('OLE_EVENT',	'OLP_PAPERLESS_ACH_PROCESSED');
	define('TIME_TARGET',	700000);
	define('TIME_WINDOW',	 24);
}
else
{
	echo "Usage: {$argv[0]} <OLE_EVENT> <TIME_TARGET> <TIME_WINDOW>\n";
	bail("you're passing me an invalid argv of $argc elements: " . join(",", $argv) . ", what am i supposed to do?!");
}

if (DEBUG)
{
	echo "OLE_EVENT: '" . OLE_EVENT . "', TIME_TARGET: '" . TIME_TARGET . "', TIME_WINDOW: '" . TIME_WINDOW . "'\n";
}

//define('OLE_SMTP_URL',	'prpc://smtp.2.soapdataserver.com/ole_smtp.1.php');

define('CONFIG_URL',	'prpc://config.1.soapdataserver.com/init_4');

// cache for config objects
$CONFIGS = array();

// we cheat by hardcoding this stuff. we could always pull property_id out of management
// however, it's unlikely to change; also, "db" can not always be guarenteed to be in the
// for "olp_{property_short}_visitor", so just hardcode the shit

if (DEBUG && !RC)
{
	$companies = array("ufc");
}
else
{
	$companies = array("CA","PCL","UFC","D1","UCL");
}

// is there a forced property?
if ($companies[FORCE_PROPERTY])
{
	$temp_companies[FORCE_PROPERTY]	= $companies[FORCE_PROPERTY];

	// uset companies
	unset($companies);
	// set the forced property company array
	$companies = $temp_companies;
}

// require the rest of what we need
require_once('error.2.php');
require_once('debug.1.php');
require_once('mysql.4.php');
require_once('security.7.php');
require_once('prpc/client.php');

// define functions

function bail($msg)
{
	$hostname = trim(`hostname`);
	$self = $_SERVER["SCRIPT_NAME"];
	$date = date("Y-m-d H:i:s");
	$body = "$hostname:$self failed at $date with the message: $msg";
	Lib_Mail::mail(BAIL_NOTIFY, "$hostname:$self - $msg", $body);
	die($msg);
}

// only one site per property is "enterprise"
// this needs to go in a table for now it's ghetto!!!
function enterprise_site($property)
{
	$site = FALSE;
	switch (strtoupper($property))
	{
	case "PCL":
		$site['site_name'] = "oneclickcash.com";
		$site['name_view'] = "OneClickCash.com";
		break;
	case "UCL":
		$site['site_name'] = "unitedcashloans.com";
		$site['name_view'] = "UnitedCashLoans.com";
		break;
	case "CA":
		$site['site_name'] = "ameriloan.com";
		$site['name_view'] = "Ameriloan.com";
		break;
	case "UFC":
		$site['site_name'] = "usfastcash.com";
		$site['name_view'] = "USFastCash.com";
		break;
	case "D1":
		$site['site_name'] = "500fastcash.com";
		$site['name_view'] = "500FastCash.com";
		break;
	}
	return $site;
}

function format_phone($phone)
{
	$phone = preg_replace('/\D+/', '', $phone);
	$phone = preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '\1-\2-\3', $phone);
	return $phone;
}

function fetch_config($license_key)
{
	global $CONFIGS, $management;
	if (!array_key_exists($license_key, $CONFIGS))
	{
		$config_obj = new Config_6($management);
		$CONFIGS[$license_key] = $config_obj->Get_Site_Config($license_key);
		if (DEBUG)
		{
			echo "fetched this config object for license key '$license_key': " .
				print_r($CONFIGS[$license_key], 1);
		}
	}
	assert(is_object($CONFIGS[$license_key]));
	return $CONFIGS[$license_key];
}

// fetch customer services phone# and fax# for a property
function fetch_support($license_key)
{
	$config = fetch_config($license_key);
	return array(
		"support_phone"		=> $config->support_phone
		,"collections_phone"	=> $config->collections_phone
		,"support_fax"		=> $config->support_fax
	);
}

// sent is an array of application ids that were successfully sent mail
function record_sent(&$sqli, $property, &$sent, $flag)
{
	if (count($sent) == 0)
	{
		return;
	}

	foreach ($sent as $sent_id)
	{
		$query = "
		INSERT INTO application_flag SET date_created=NOW(), company_id = 
		(SELECT company_id FROM company WHERE upper(name_short) ='". strtoupper($property) ."'), 
		application_id = $sent_id, flag_type_id=(SELECT flag_type_id FROM flag_type WHERE name_short='".strtolower($flag)."')
		";

		if (DEBUG)
		{
			echo "record_sent(" . $property . ") query: " . $query . "\n";
		}
	
		if (!DEBUG || RC)
		{
			$rs = $sqli->Query( 'ldb', $query );
		}
	}
}

function create_confirm_url(&$user)
{

	$login_hash = md5($user["username"] . "l04ns");
	return "http://" . strtolower($user["site_name"]) .
		"/?application_id=" . urlencode( base64_encode($user["app_id"])) .
		"&page=ent_cs_login&login=$login_hash&ecvt";
}

function fetch_app_type()
{
	switch (OLE_EVENT)
	{
	case "OLP_PAPERLESS_2_HOUR_FOLLOWUP":
	case "OLP_PAPERLESS_4_HOUR_FOLLOWUP":
		// "i applied but haven't confirmed" status
		//return "app.application_status_id = (SELECT application_status_id FROM application_status_flat 
		//WHERE level0 = 'agree' AND level1 = 'prospect' AND level2 = '*root')";
		return "app.application_status_id = 5";
		break;
	case "OLP_PAPERLESS_ACH_PROCESSED":
	case "OLP_PAPERLESS_THANK_YOU":
		// funded "active" status
		return "app.application_status_id = 20";
		//return "app.application_status_id = (SELECT application_status_id FROM application_status_flat
		//WHERE level0 = 'active' AND level1 = 'servicing' AND level2 = 'customer' AND level3 = '*root')";
		
		break;
	}
}

function fetch_exception_type()
{
	switch (OLE_EVENT)
	{
		case "OLP_PAPERLESS_2_HOUR_FOLLOWUP":
			return '2HR_EMAIL';
			break;
		case "OLP_PAPERLESS_4_HOUR_FOLLOWUP":
			return '4HR_EMAIL';
			break;
		case "OLP_PAPERLESS_ACH_PROCESSED":
			return 'ACH_EMAIL';
			break;
		case "OLP_PAPERLESS_THANK_YOU":
			return 'TY_EMAIL';
			break;
	}
}


function create_template_data(&$user)
{

	// fetch phone/fax data for property
	$support = fetch_support($user["license_key"]);

	// associative array we send to OLE so it can populate the template
	// returns all data necessary to fill in all templates we support
	return array(
		// following two fields are required by the ole emailer
		"email_primary"		=> $user["email"]
		,"email_primary_name"	=> ucfirst($user["name_first"]) . " " . ucfirst($user["name_last"])
		// here's the data for the template
		// name of the site they signed up under...
		,"site_name"		=> $user["site_name"]
		,"name_view"		=> $user["name_view"]
		,"name"			=> ucfirst($user["name_first"]) . " " . ucfirst($user["name_last"])
		,"applicationid"	=> $user["app_id"]
		// do this formatting in php because of the nice and clean number_format() function
		// formats to 2 decimal places, adds commas for numbers 1e3+
		,"amount"		=> '$' . number_format($user["amount"], 2)
		,"username"		=> $user["username"]
		,"password"		=> $user["password"]
		// url they need to click on to confirm loan
		,"confirm"		=> create_confirm_url($user)
		// estimated fund dats->legal_documente
		,"date"			=> $user["est_fund_date"]

		,"phone"		=> format_phone($support["support_phone"])
		,"fax"			=> format_phone($support["support_fax"])

	);
}


function mysql_fetch_apps($sqli, $property_short)
{
	$target = (DEBUG && !RC ? strtotime("2005/10/26 12:00:00") : time()) - (TIME_TARGET * 60);
	$earliest = date("YmdHis", $target);
	$latest = date("YmdHis", $target + (TIME_WINDOW * 60));

	if (DEBUG)
	{
		printf("current: %s, earliest: %s, latest: %s\n",
			date("Y-m-d H:i:s"),
			date("Y-m-d H:i:s", $target),
			date("Y-m-d H:i:s", $target + (TIME_WINDOW * 60))
		);
	}

	$query = "
	SELECT
		app.application_id	AS app_id
		,app.name_first		AS name_first
		,app.name_last		AS name_last
		,app.email		AS email
		,DATE_FORMAT(app.date_fund_estimated,'%W, %M %D %Y')	AS est_fund_date
		,app.fund_qualified		AS amount
		,login.login		AS username
		,login.crypt_password		AS hashed_password
		,si.name			AS site_name
		,si.license_key		AS license_key
		,'" . $property_short . "' AS property_short
		, af.application_flag_id
	FROM
		application app
		JOIN login ON (login.login_id=app.login_id) 
		JOIN campaign_info ci ON (ci.application_id = app.application_id) 
		JOIN site si ON (si.site_id = ci.site_id) 
		LEFT JOIN application_flag af ON (app.application_id = af.application_id AND af.flag_type_id=(SELECT flag_type_id FROM flag_type WHERE name_short='".strtolower(fetch_exception_type())."'))
	WHERE
		".fetch_app_type()."
	AND
		app.date_created BETWEEN '" . $earliest . "' AND '" . $latest ."' 
	AND 
		app.company_id = (select company_id from company where name_short = '".strtolower($property_short)."')
	AND
		af.application_flag_id is null
	";

	$rs = $sqli->Query( 'ldb', $query );

	$i = 0;
	while($user_data = $sqli->Fetch_Array_Row($rs))
	{
		$users[$i] = $user_data;
		++$i;
	}

	return $users;
}

////////////////////// BEGIN MAIN LOGIC  ////////////////////////

require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
//require('/virtualhosts/lib/ole_smtp_lib.php');
// define mailing class
//$mailer = new Prpc_Client(OLE_SMTP_URL);

//$mailer = new Ole_Smtp_Lib();
$tx = new OlpTxMailClient(false,RC?'RC':'LIVE');

// Build the mysql object
//$sqli = new MySQL_4 ('db101.clkonline.com:3308', 'ldb_writer', '1canwr1t3', 'ldb');
$sqli = new MySQL_4 ('writer.ecashclk.ept.tss:3308', 'olp', 'dicr9dJA', 'ldb');

// Try the connection
$sqli->Connect();

foreach ($companies as $property_short)
{
	// setup security object
	$security7 = new Security_7($sqli, $property_short);

	// keep track of all the emails we send for this property that succeed so we can record them afterwards
	$sent = array( TRUE => array(), FALSE => array() );

	// retreive enterprise site_name and name_view
	$enterprise = enterprise_site($property_short);

	$rs = mysql_fetch_apps($sqli, $property_short);

	//For each customer found process them
	if (is_array ($rs) && count ($rs) > 0)
	{
		foreach ($rs as $user)
		{
			if (DEBUG)
				echo "fetched user '" . $user["email"] . "\n";

			// decrypt password for sending
			if (DEBUG && !RC)
			{
				echo "user: " . print_r($user, 1);
				echo "unmangling password '" . $user["hashed_password"] . "'...\n";
			}
			
			$user["password"] = $security7->_Decrypt_Password($user['hashed_password']);
			
			if (DEBUG && !RC)
			{
				echo "password unmangled: " . print_r($user["password"], 1) . "\n";
			}
			$user['site_name'] = $enterprise['site_name'];
			$user['name_view'] = $enterprise['name_view'];

			$data = create_template_data($user);

			// if we're in debug mode, actually send to the user who's debugging
			if (DEBUG)
			{
				$data["email"] = $data["email_primary"] = DEBUG_EMAIL;
				echo "data for property '$property_short': " . print_r($data, 1) . "\n";
			}

			// send mail and append app_id to the result (TRUE|FALSE) array of sent
			// pass NULL property_id for "generic" -- or so days The Don
			//$mail = $mailer->Ole_Send_Mail(OLE_EVENT, NULL, $data);
			try 
			{
				$mail = $tx->sendMessage('live', OLE_EVENT, $data['email_primary'], '', $data);
			}
			catch(Exception $e)
			{
				$mail = FALSE;
			}

			if (DEBUG && !RC)
			{
				echo "mail results: $mail\n";
			}

			$sent[(bool)($mail)][] = intval($user["app_id"]);

			// only send one record per property for testing
			if (DEBUG && !RC)
			{
				echo "sent for property '$property_short': $sent\n";
			}
		}
	}

	if (DEBUG)
	{
		echo "user is FALSE!, see?: "; var_dump($user); echo "\n";
		echo "result of send for '$property_short':" . print_r($sent, 1) . "\n";
	}

	// don't think we need to print anything out
	//print_r($sent);
	// after we've run through an entire property's worth, record results
	record_sent($sqli, $property_short, $sent[TRUE],fetch_exception_type());
}

?>
