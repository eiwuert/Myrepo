<?php

/*
vim: set ts=8:

partnerweekly has found someone to buy debt leads off of us

we need to batch up a maximum of 125 per day of this data:

field				field
-----------------------------------------------------------------------------
fname				olp_{property}_visitor.personal.first_name
lname				olp_{property}_visitor.personal.last_name
residence			olp_{property}_visitor.residence.address_1
city				olp_{property}_visitor.residence.city
state				olp_{property}_visitor.residence.state
zip				olp_{property}_visitor.residence.zip
email				olp_{property}_visitor.personal.email
phone				olp_{property}_visitor.personal.home_phone
...all debt-related data...	lead_generation.debt.*

debt table:

mysql> desc debt;
+-------------------+------------------+------+-----+---------------------+----------------+
| Field             | Type             | Null | Key | Default             | Extra          |
+-------------------+------------------+------+-----+---------------------+----------------+
| id                | int(10) unsigned |      | PRI | NULL                | auto_increment |
| site              | char(24)         |      | MUL |                     |                |
| customer          | char(16)         |      |     |                     |                |
| property          | char(16)         |      |     |                     |                |
| app_id            | int(10) unsigned |      | MUL | 0                   |                |
| updated           | datetime         |      |     | 0000-00-00 00:00:00 |                |
| income            | int(10) unsigned |      |     | 0                   |                |
| total_debt        | int(10) unsigned |      |     | 0                   |                |
| monthly_expenses  | int(10) unsigned |      |     | 0                   |                |
| homeowner         | char(1)          |      |     | N                   |                |
| credit_counseling | char(1)          |      |     | N                   |                |
| result            | char(10)         |      |     |                     |                |
| result_reason     | char(50)         |      |     |                     |                |
| fname             | char(30)         |      |     |                     |                |
| lname             | char(30)         |      |     |                     |                |
| email             | char(50)         |      |     |                     |                |
| phone             | char(20)         |      |     |                     |                |
| address1          | char(50)         |      |     |                     |                |
| address2          | char(50)         |      |     |                     |                |
| city              | char(2)          |      |     |                     |                |
| state             | char(2)          |      |     |                     |                |
| zip               | char(10)         |      |     |                     |                |
+-------------------+------------------+------+-----+---------------------+----------------+
22 rows in set (0.05 sec)


*/

require_once("diag.1.php");
require_once("lib_mode.1.php");
require_once("hit_stats.1.php");

Diag::Enable();
define("DEBUG_EMAIL",	"ryanf@sellingsource.com");
define("MAIL_SERVER",	"prpc://smtp.2.soapdataserver.com/smtp.1.php");
define("STAT_COLUMN",	"h3"); /* harvest column 3 */

switch (Lib_Mode::Get_Mode())
{
case MODE_LOCAL:
/*
	define("DEBUG",		false);
	define("DB_HOST",	"localhost");
	define("DB_USER",	"root");
	define("DB_PASS",	"");
	define("LICENSE_KEY",	"15e0bd14ec4952c6397cc1315e1f9fab");
	break;
*/
/* no MODE_RC, this is a cronjob */
case MODE_LIVE:
	define("DEBUG",		false);
	define("DB_HOST",	"selsds001");
	define("DB_USER",	"sellingsource");
	define("DB_PASS",	"%selling\$_db");
	define("LICENSE_KEY",	"3301577eb098835e4d771d4cceb6542b");
	break;
default:
	die("What mode is this?");
	break;
}

$DB_DEBT = "lead_generation";
$DB_TABLE_LOG = "log_debtpro";
$PROPERTIES = array();

$LIMIT_DAILY	= 100; # maximum records to send per day
$MIN_TOTAL_DEBT	= 20000; # minimum debt to qualify
$STATES_EXCLUDE	= array("HI", "ID", "IN", "LA", "MS", "NM", "WY"); # states to exclude from results

require_once("prpc/client.php");
require_once("mysql.3.php");

////////////////////////////////// define functions

function get_last_id(&$sql)
{

	global $DB_DEBT, $DB_TABLE_LOG;

	$last_id = 0;

	$query = "
	SELECT
		COALESCE(MAX(last_id), 0)
	FROM
		$DB_TABLE_LOG
";
	Diag::Out("query: $query");
	
	if (!DEBUG)
	{
		$rs = $sql->Query(
			$DB_DEBT
			,$query
			,Debug_1::Trace_Code(__FILE__, __LINE__)
		);

		Error_2::Error_Test($rs, TRUE);

		list($last_id) = $sql->Fetch_Row($rs);
	}

	Diag::Out("last_id: $last_id");

	return $last_id;
}

function save_point(&$sql, $count, $last_id)
{
	global $DB_DEBT, $DB_TABLE_LOG;

	$query = "
INSERT INTO $DB_TABLE_LOG (
	id
	,updated
	,records_sent
	,last_id
) VALUES (
	NULL
	,NOW()
	," . intval($count) ."
	," . intval($last_id) . "
)";

	Diag::Out("query: $query");
	
	if (!DEBUG)
	{
		$rs = $sql->Query(
			$DB_DEBT
			,$query
			,Debug_1::Trace_Code(__FILE__, __LINE__)
		);
		Error_2::Error_Test($rs, TRUE);
	}

}

function send_mail($records, $csv)
{

	$mail = new Prpc_Client(MAIL_SERVER);
	
	// Build the header
	$header = (object)array(
		"port"		=> 25,
		"url"		=> "maildataserver.com",
		"subject"	=> "100 Test Debt Leads",
		"sender_name"	=> "Pamela S",
		"sender_address"=> "pamelas@partnerweekly.com"
	);
	
	// Build the recipient
	if (DEBUG)
	{
		$recipient = array(
			(object)array("type" => "to", "name" => "debugger", "address" => DEBUG_EMAIL)
		);
	}
	else
	{
		// nothing yet
		$recipient = array(
			(object)array("type" => "to", "name" => "debugger", "address" => DEBUG_EMAIL)
		);
	}
	
	//$message = (object)array("text" => "$records records from " . date("m/d/Y", strtotime("yesterday")) . " attached");
	$message = (object)array("text" => "$records test records");
	
	$attachment = (object)array(
		"name" => "thedebtprofessionals-debtleads-" . date("Y-m-d") . ".csv",
		"content" => base64_encode($csv),
		"content_type" => "text/csv",
		"content_length" => strlen($csv),
		"encoded" => true
	);
	
	if (FALSE == ($mail_id = $mail->CreateMailing("PW_TDP_DEBT_LEADS", $header, NULL, NULL)))
	{
		die("CreateMailing failed!\n");
	}

	Diag::Out("mail_id: $mail_id");
	
	if (FALSE == ($package_id = $mail->AddPackage($mail_id, $recipient, $message, array($attachment))))
	{
		die("AddPackage failed!\n");
	}

	Diag::Out("package_id: $package_id");
	
	if (FALSE == ($sender = $mail->SendMail($mail_id)))
	{
		die("SendMail failed!\n");
	}

	Diag::Out("sender: $sender");

}

///////////////// BEGIN ACTUAL CODE


$sql = new MySQL_3();
$sql->Connect(NULL, DB_HOST, DB_USER, DB_PASS, Debug_1::Trace_Code(__FILE__, __LINE__));

$last_id = get_last_id($sql);

# now 
$query = "
SELECT
	id
	,ip
	,updated
	,email
	,fname
	,lname
	,phone
	,address1
	,address2
	,city
	,state
	,zip
	,total_debt
FROM
	debt
WHERE
	id > " . intval($last_id) . "
AND
	total_debt >= " . intval($MIN_TOTAL_DEBT) . "
AND
	address1 != ''
AND
	email != ''
AND
	state NOT IN ('','" . join("','", $STATES_EXCLUDE) . "')
GROUP BY
	email
ORDER BY
	id ASC
LIMIT " . intval($LIMIT_DAILY) . "
";

Diag::Out("query: " . preg_replace('/\s+/', ' ', $query));

$rs = $sql->Query(
	$DB_DEBT
	,$query
	,Debug_1::Trace_Code(__FILE__, __LINE__)
);

Error_2::Error_Test($rs, TRUE);

$debts = array();

while (FALSE !== ($rec = $sql->Fetch_Array_Row($rs)))
{
	if ("" == $rec["credit_counseling"])
		$rec["credit_counseling"] = "N";
	$debts[$rec["id"]] = $rec;
}

$sql->Free_Result($rs);

$csv = "fname,lname,address1,address2,city,state,zip,email,phone,total_debt,date,time,ip\r\n";

reset($debts);
while (list($id,$rec) = each($debts))
{
	$ts = strtotime($rec["updated"]);
	$csv .= '"' . join('","',
		array(
			$rec["fname"]
			,$rec["lname"]
			,$rec["address1"]
			,$rec["address2"]
			,$rec["city"]
			,$rec["state"]
			,$rec["zip"]
			,$rec["email"]
			,$rec["phone"]
			,$rec["total_debt"]
			,date("m/d/Y", $ts)
			,date("H:i:s", $ts)
			,$rec["ip"]
		)
	) ."\"\r\n";
	$last_id = intval($rec["id"]);
}

$recs = count($debts);

// save our point, even if we retrieved 0 records
save_point($sql, $recs, $last_id);

Diag::Out("hitting stat '" . STAT_COLUMN . "' for " . $recs);

// hit stats to record how many records we sent these folks
Hit::Stats_Promoless(LICENSE_KEY, $sql, STAT_COLUMN, $recs);

Diag::Out("stats hit.");

// if we didn't get anything back then  tell us for debug, but email empty record anyways
// because otherwise the client will think our script is messed up when in fact it may not be the case
if (0 == $recs)
{
	Diag::Out("no records found!");
}

send_mail($recs, $csv);

?>

