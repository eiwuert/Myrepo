<?
/**
 * Smile Funding (smf) nightly batch process.
 * Sends email of the previous days' leads.
 * 
 * * Usage
 * #php ./batch.nightly.smilefunding.php LIVE
 * #php ./batch.nightly.smilefunding.php LOCAL
 * 
 * Notes:
 * * We use function mysql_fetch_array() directly.
 * 
 * @link https://webadmin2.sellingsource.com/index.php?m=tasks&a=view&task_id=12365 New Batch Campaign - Smile Funding (smf)
 * 
 * References:
 * @link https://webadmin2.sellingsource.com/index.php?m=tasks&a=view&task_id=12327 New Batch Campaign - Payday Loan Cash Now (plc)
 * @author: Demin Yin (Demin.Yin@SellingSource.com
 */

define('BFW_CODE_DIR','/virtualhosts/bfw.1.edataserver.com/include/code/');

require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
require_once(BFW_CODE_DIR.'server.php');
require_once('mysql.4.php');

/**
 * **********************************************
 * SETTING STARTS HERE
 * **********************************************
 */

$_S['limit'] 			= 30; // limit should be set in webadmin2. This setting is for safe purpose only.
$_S['property_short'] 	= 'SMF';
$_S['mysql_date_format']= '%Y-%m-%d'; // MySQL date_format
$_S['duration'] 		= 24*60; // minutes (default 24 hours)
$_S['mode'] 			= ($argv[1]) ? strtoupper($argv[1]) : 'LOCAL'; // LOCAL, RC, or LIVE
$_S['trendex_mode'] 	= 'live';
$_S['debug'] 			= false; // debug status or not

// If (potential) bug found, send email to following email(s).
$_S['tech_support']		= array("email_primary_name" => "Demin Yin", "email_primary" => "Demin.Yin@SellingSource.com");

// Trendex settings
$_S['email_id']			= 'CRON_EMAIL';
$_S['email_track_key']	= '61f2de54e95a4fc6791eff1c89d4a025'; // anything

// Where to send leads
$_S['recipients'] 	= array(
	array("email_primary_name" => "Hope Pacariem", 		"email_primary" => "Hope.Pacariem@PartnerWeekly.com"),
	array("email_primary_name" => "Jeffrey Densberger",	"email_primary" => "jeff@smilepayday.com"),
);

$_S['timestamp'] 		= date("Y-m-d", time()); // used as attachment name, email subject, etc.
$_S['header']		= array( // Email header info
	"sender_name"		=> "The Selling Source <no-reply@sellingsource.com>",
	"subject"			=> "Leads to Smile Funding for {$_S['timestamp']}",
	"site_name" 		=> "sellingsource.com",
);

$_S['attachment']	= array( // Email attachment info
	'method' 			=> 'ATTACH',
	'filename' 			=> "smilefunding_{$_S['timestamp']}.txt",
	'mime_type' 		=> 'text/plain',
);

// queries used to retrieve application info.
$_S['queries'] = array(
	array(
		'table'	=> 'bank_info', 
		'using' => 'application_id',
		'limit' => 1,
		'fields'=> array(
			'Bank Name' 		=> 'bank_name', 
			'Account #' 		=> 'account_number', 
			'ABA #' 			=> 'routing_number', 
			'Direct Deposit' 	=> 'IF (STRCMP(direct_deposit, \'TRUE\'), \'NO\', \'YES\')', 
		)
	),
	array(
		'table'	=> 'employment', 		
		'using' => 'application_id',
		'limit' => 1,
		'fields'=> array(
			'Employer' 			=> 'employer', 
			'Work Phone' 		=> 'work_phone', 
			'Income Type'		=> 'income_type',
		)
	),	
	array(
		'table'	=> 'income', 		
		'using' => 'application_id',
		'limit' => 1,
		'fields'=> array(
			'Monthly Income' 	=> 'monthly_net', 
			'Pay Period' 		=> 'pay_frequency', 
			'Pay Date 1' 		=> "date_format(pay_date_1,'{$_S['mysql_date_format']}') as pay_date_1", 
			'Pay Date 2' 		=> "date_format(pay_date_2,'{$_S['mysql_date_format']}') as pay_date_2",
		)
	),	
	array(
		'table'	=> 'personal', 		
		'using' => 'application_id',
		'limit' => 1,
		'fields'=> array(
			'First Name' 		=> 'first_name', 
			'Last Name' 		=> 'last_name', 
			'Home Phone' 		=> 'home_phone',
			'Cell Phone'		=> 'cell_phone',
			'Best Call Time'	=> 'best_call_time', 
			'Email' 			=> 'email',
			'DOB' 				=> 'date_of_birth',
			'p_contact_id_1' 	=> 'contact_id_1', 
			'p_contact_id_2' 	=> 'contact_id_2', 
			'SSN' 				=> 'social_security_number',
			'DL#' 				=> 'drivers_license_number',
		)
	),
	array(
		'table'	=> 'personal_contact', 		
		'using' => 'application_id',
		'limit' => 2,
		'fields'=> array(
			'pc_contact_id_%d'	=> 'contact_id',
			'Ref%d Name' 		=> 'full_name', 
			'Ref%d Phone' 		=> 'phone', 
			'Ref%d Relationship'=> 'relationship',
		)
	),
	array(
		'table'	=> 'residence', 
		'using' => 'application_id',
		'limit' => 1,
		'fields'=> array(
			'Address' 			=> 'address_1', 
			'Apt' 				=> 'apartment', 
			'City' 				=> 'city', 
			'State Selection' 	=> 'state', 
			'Zip' 				=> 'zip', 
		)
	),
);
foreach ($_S['queries'] as $key => $query) {
	$_S['queries'][$key]['limit_field'] = "{$_S['queries'][$key]['table']}_limit";
}

$_S['end_date'] = date("Ymd235959", (strtotime("yesterday")));

$_S['template']	= <<<TEMPLATE
<<Basic Info>>
First Name
Last Name
DOB
<<Contact Info>>
Home Phone
Cell Phone
Best Call Time
Email
<<Income and Paydate Info>>
Monthly Income
Pay Period
Pay Date 1
Pay Date 2
<<Bank Info>>
Bank Name
Account #
ABA #
Direct Deposit
<<Employee Info>>
Income Type
Employer
Work Phone
<<ID Info>>
SSN
DL#
<<Address Info>>
Address
Apt
City
State Selection
Zip
<<References>>
Ref1 Name
Ref1 Phone
Ref1 Relationship
Ref2 Name
Ref2 Phone
Ref2 Relationship





TEMPLATE;

switch(strtoupper($_S['mode']))
{
	case 'LOCAL':
	case 'RC':
		$_S['debug'] 	= true;
		break;
	case 'LIVE':
		$_S['debug'] 	= false;
		break;
	default:
		die("Please specify an environment (LOCAL, RC or LIVE)\n");
		break;
}

if($_S['debug'] == true) {
	$_S['property_short'] 	= 'SMF';	
	$_S['duration'] 		= 5*24*60; // minutes (5 days)
	$_S['end_date'] 		= date("Ymd235959", (strtotime("today")));	
	$_S['recipients'] 		= array( 
		array("email_primary_name" => "Debug",
		 		"email_primary" => "Demin.Yin@SellingSource.com"),
	);
}

/**
 * **********************************************
 * SETTING ENDS HERE
 * **********************************************
 */

/**
 * **********************************************
 * MAIN PROGRAM STARTS HERE
 * **********************************************
 */

$error	= array(); // track errors
$tx = new OlpTxMailClient(false,$_S['mode']);
$server = Server::Get_Server($_S['mode'], 'BLACKBOX');
$sql 	= new Mysql_4($server['host'], $server['user'], $server['password']);
$sql->Connect();

$qry 	= "
				SELECT
					target_id
				FROM
					target
				WHERE
					property_short  = '{$_S['property_short']}'
					AND status = 'ACTIVE'
";
		
$res = $sql->Query($server['db'], $qry);
if ($cnt = $sql->Affected_Row_Count()) {
	if ($cnt != 1)
		$error[] = sprintf('More than one active target with name \'%s\'. (line %d)', $_S['property_short'], __LINE__);
		
	$row =  $sql->Fetch_Array_Row($res);
	$target_id = $row['target_id'];
	
	$qry 	= "
				SELECT
					application_id,
					date_format(created_date,'%Y-%m-%d %h:%i %p') as created_date
				FROM
					application
				WHERE
					created_date BETWEEN DATE_SUB(NOW(), INTERVAL {$_S['duration']} MINUTE) AND {$_S['end_date']}
					AND application_type  = 'COMPLETED'
					AND target_id = '{$target_id}'
				ORDER BY
					application_id DESC
				LIMIT 
					{$_S['limit']};
	";
	$res = $sql->Query($server['db'], $qry);
	if ($cnt = $sql->Affected_Row_Count()) {
		$leads = array();
		while($row =  $sql->Fetch_Array_Row($res)) {
			$leads[$row['application_id']]['Application ID'] 			= $row['application_id'];
			$leads[$row['application_id']]['Created Date'] 				= $row['created_date'];
			foreach ($_S['queries'] as $query) {
				$leads[$row['application_id']][$query['limit_field']] 	= $query['limit'];
			}
		}
	
		$application_ids = implode(',', (array_keys($leads)));
	
		foreach ($_S['queries'] as $query) {
			$qry2 = "
				SELECT 
					{$query['using']},
					" . implode(",
					", $query['fields']) . "
				FROM
					{$query['table']}
				WHERE
					{$query['using']} IN ({$application_ids})
				ORDER BY
					{$query['using']};
			";
			$res2 = $sql->Query($server['db'], $qry2);
			if ($sql->Affected_Row_Count()) {				
				$old = null;
				while($row2 = mysql_fetch_array($res2)) { // !!
					if (is_null($old) || $old != $row2[0] ) { // 1st time
						$old = $row2[0]; 
						$i = 1; // used for references' key values 
					} else {
						$i++;
					}					
					
					$j = 1; // 1st column is field [using] !!
					if ($query['limit'] == 1) {
						foreach ($query['fields'] as $field_title => $field_name)
							$leads[$row2[$query['using']]][$field_title] 	= $row2[$j++];
					} else { // query on table 'personal_contact' where each app_id is associated with 2 records
						foreach ($query['fields'] as $field_title => $field_name) { // todo
							$field_title = sprintf($field_title, $i);
							$leads[$row2[$query['using']]][$field_title]	= $row2[$j++];
						}
					}
					$leads[$row2[$query['using']]][$query['limit_field']]--;					
				}
				
				if ($leads[$row2[$query['using']]][$query['limit_field']] < 0) {
					$error[] = sprintf(
						'App \'%d\' has some inconsistent data in table \'%s\'. (line %d)',
						$leads[$row2[$query['using']]], 
						$query['table'], 
						__LINE__
					);
				}				
			} else {
				$error[] = sprintf(
					'No result found when querying on table \'%s\'. (line %d)', 
					$query['table'],
					__LINE__
				);
			}
		}
		
		$i 				= 1;
		$email_content 	= "";
		foreach($leads as $lead) { // prepare attachment
			$email_content .= Parse_Template($lead, $i++);
			
			$a1 = array($lead['p_contact_id_1'],  $lead['p_contact_id_2']);
			$a2 = array($lead['pc_contact_id_1'], $lead['pc_contact_id_2']);
			if ( sort($a1) != sort($a2) ) {
				$error[] = sprintf('App \'%d\'\'s contact_ids are different. (line %d)', $lead['application_id'], __LINE__);
			}	
		}
		
		foreach($_S['recipients'] as $recipient) { // send email
			Send_Email_By_Trendex($recipient, $email_content);
		}	
	} else {
		$error[] = sprintf('No leads found. (line %d)', __LINE__);
	}		
} else {
	$error[] = sprintf('No active target with name \'%s\' found. (line %d)', $_S['property_short'], __LINE__);
}

Exception_Handler($error); // handle errors.

/**
 * **********************************************
 * MAIN PROGRAM ENDS HERE
 * **********************************************
 */

/**
 * **********************************************
 * CLASS/FUNCTION DEFINITION STARTS HERE
 * **********************************************
 */

/**
 * Send email through Trendex
 *
 * @param string $recipient email recipient.
 * @param string $content email content.
 * @return boolean
 */
function Send_Email_By_Trendex($recipient, $content) {
	global $_S, $tx;
	
	$header = array_merge($_S['header'], $recipient);
	
	$_S['attachment']['file_data_size'] = strlen($content);
	$_S['attachment']['file_data'] 		= gzcompress($content);
	
	return $tx->sendMessage(
		$_S['trendex_mode'], 
		$_S['email_id'],
		$header['email_primary'], 
		$_S['email_track_key'],
		$header,
		array($_S['attachment'])
	);	
}

/**
 * Create email content for each lead.
 *
 * @param array $lead
 * @param int $i lead index. Starts from 0, and ends at 30.
 * @return string email content for given lead.
 */
function Parse_Template($lead, $i) {
	global $_S;
	$format = "%25s:  %-30s";
	$len 	= 80;
	
	$str .= sprintf($format . "                  (%02d)\n", 'Application ID', $lead['Application ID'], $i);
	$str .= sprintf($format . "\n", 'Created Date', $lead['Created Date']);
		
	$a = explode("\n", $_S['template']);
	foreach ($a as $key => $val) {
		$val = trim($val);
		if (key_exists($val, $lead)) {
			$a[$key] = sprintf($format, $val, $lead[$val]);
		} else if (!empty($val)) {
			if (strpos($val, "<<") !== false) { // section title
				$s = ($len - strlen($val)) / 2;
				$a[$key] = str_repeat("-", $s) . $val . str_repeat("-", $s + strlen($val) % 2);
			} else { // other cases
				$a[$key] = sprintf($format, $val, "");
			}
		}		
	}
	
	$str .= implode("\n", $a);
	return $str;
}

/**
 * Handle errors.
 *
 * @param array $error Errors.
 * @return boolean
 */
function Exception_Handler($error = null) {
	global $_S;
	
	if (is_array($error) && !empty($error)) {
		$msg = implode("\n\n", $error);
		return Send_Email_By_Trendex($_S['tech_support'], $msg);
	} else {
		return true;
	}
}

/**
 * **********************************************
 * CLASS/FUNCTION DEFINITION ENDS HERE
 * **********************************************
 */

?>