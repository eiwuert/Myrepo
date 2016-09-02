<?php

/*
vim: set ts=8:

NOTE: this query is pretty big and ugly... check and make sure at least some of the columns
are indexed. the table is small now, but as it grows this will become increasingly important

mysql> desc mortgage;
+------------------+------------------+------+-----+---------------------+----------------+
| Field            | Type             | Null | Key | Default             | Extra          |
+------------------+------------------+------+-----+---------------------+----------------+
| id               | int(10) unsigned |      | PRI | NULL                | auto_increment |
| site             | char(24)         |      | MUL |                     |                |
| customer         | char(16)         |      |     |                     |                |
| property         | char(16)         |      |     |                     |                |
| app_id           | int(10) unsigned |      | MUL | 0                   |                |
| updated          | datetime         |      |     | 0000-00-00 00:00:00 |                |
| loan_type        | char(16)         |      |     |                     |                |
| property_type    | char(16)         |      |     |                     |                |
| property_value   | int(10) unsigned |      |     | 0                   |                |
| mortgage_balance | int(10) unsigned |      |     | 0                   |                |
| additional_cash  | int(10) unsigned |      |     | 0                   |                |
| consumer_credit  | char(10)         |      |     |                     |                |
| result           | char(10)         |      |     |                     |                |
| result_reason    | char(50)         |      |     |                     |                |
| fname            | char(30)         |      |     |                     |                |
| lname            | char(30)         |      |     |                     |                |
| email            | char(50)         |      |     |                     |                |
| phone            | char(20)         |      |     |                     |                |
| address1         | char(50)         |      |     |                     |                |
| address2         | char(50)         |      |     |                     |                |
| city             | char(30)         |      |     |                     |                |
| state            | char(2)          |      |     |                     |                |
| zip              | char(10)         |      |     |                     |                |
+------------------+------------------+------+-----+---------------------+----------------+
23 rows in set (0.06 sec)


*/

require_once("diag.1.php");
require_once("lib_mode.1.php");
require_once("hit_stats.1.php");

Diag::Enable();
define("DEBUG_EMAIL",	"david.bryant@thesellingsource.com");
define("MAIL_SERVER",	"prpc://smtp.2.soapdataserver.com/smtp.1.php");
define("STAT_COLUMN",	"h2"); /* harvest column 1 */

switch (Lib_Mode::Get_Mode())
{
case MODE_LOCAL:
	define("DEBUG",		true);
	define("DB_HOST",	"localhost");
	define("DB_USER",	"root");
	define("DB_PASS",	"");
	define("LICENSE_KEY",	"15e0bd14ec4952c6397cc1315e1f9fab");
	break;
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

define("MIN_PROPERTY_VALUE", 75000);
define("MIN_MORTGAGE_BALANCE", 75000);

$DB_DEBT = "lead_generation";

$STATES_EXCLUDE = array('','AK','DC','ID','MT','NJ','NM','PR','VA','VI','WV');
// ALASKA (AK)
// Washington, DC (DC)
// Idaho (ID)
// Montana (MT)
// New Jersey (NJ)
// New Mexico (NM)
// Puerto Rico (PR)
// Virginia (VA)
// Virgin Islands (VI) (i don't think we even collect that, but just in case)
// West Virginia (WV)

require_once("/virtualhosts/lib/prpc/client.php");
require_once("/virtualhosts/lib/mysql.3.php");
require_once("/virtualhosts/lib/lib_mail.1.php");

////////////////////////////////// define functions

function get_last_id(&$sql)
{

	global $DB_DEBT;

	$last_id = 0;

	$query = "
	SELECT
		MAX(last_id)
	FROM
		log_dmipartners";

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

	return $last_id;
}

function save_point(&$sql, $count, $last_id)
{
	global $DB_DEBT;

	$query = "
INSERT INTO log_dmipartners (
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
	
	//Diag::Dump("Could not connect to '" . MAIL_SERVER . "'... retrying...");
	//Lib_Mail::mail(DEBUG_EMAIL, "PRPC_Client(" . MAIL_SERVER . ") failed!", "");
	

	$mail->setPrpcDieToFalse();

	// Build the header
	$header = (object)array(
		"port"		=> 25,
		"url"		=> "maildataserver.com",
		"subject"	=> "Mortgage Leads From the Past 24 Hours",
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
		$recipient = array(
			(object)array("type" => "to", "name" => "DMIPartners", "address" => "track@dmipartners.com"),
			(object)array("type" => "bcc", "name" => "Stats", "address" => "stats@partnerweekly.com"),
			(object)array("type" => "bcc", "name" => "DMIPartners", "address" => "mel.leonard@thesellingsource.com"),
			(object)array("type" => "bcc", "name" => "DMIPartners", "address" => "laura.gharst@partnerweekly.com"),
			(object)array("type" => "bcc", "name" => "Programmer", "address" => DEBUG_EMAIL)
		);
	}
	
	$message = (object)array("text" => "$records records from " . date("m/d/Y h:i A T", strtotime("-24 hours")) . " to " . date("m/d/Y h:i A T") . " attached");
	
	$attachment = (object)array(
		"name" => "dmipartners-mortgageleads-" . date("Y-m-d") . ".csv",
		"content" => base64_encode($csv),
		"content_type" => "text/csv",
		"content_length" => strlen($csv),
		"encoded" => true
	);
	
	$mail_id = $mail->CreateMailing("PW_DMI_MORTGAGE_LEADS", $header, NULL, NULL);
	
	//Lib_Mail::mail(DEBUG_EMAIL, "Create_Mailing failed!", "");
	

	Diag::Out("mail_id: $mail_id");
	
	$package_id = $mail->AddPackage($mail_id, $recipient, $message, array($attachment));
	
	//Lib_Mail::mail(DEBUG_EMAIL, "Add_Package failed!", "");
	

	Diag::Out("package_id: $package_id");
	
	$sender = $mail->SendMail($mail_id);
	
	//Lib_Mail::mail(DEBUG_EMAIL, "SendMail failed!", "");
	

	Diag::Out("sender: $sender");

}

///////////////// BEGIN ACTUAL PROCEDURAL CODE


$sql = new MySQL_3();
$sql->Connect(NULL, DB_HOST, DB_USER, DB_PASS, Debug_1::Trace_Code(__FILE__, __LINE__));

$last_id = get_last_id($sql);

$query = "
SELECT
	id
	,ip
	,DATE_FORMAT(updated,'%m-%d-%Y %H:%i') AS date
	,fname
	,lname
	,email
	,phone
	,address1
	,address2
	,city
	,state
	,zip
	,loan_type
	,property_type
	,property_value
	,mortgage_balance
	,additional_cash
	,consumer_credit
FROM
	mortgage
WHERE
	id > " . intval($last_id) . "
AND
	fname != ''
AND
	fname != 'test'
AND
-- within the last 24 hours
	updated >= NOW() - INTERVAL 24 HOUR
AND
-- home is worth $75k+
	property_value >= " . intval(MIN_PROPERTY_VALUE) . "
AND
-- they're asking for $75k+
	mortgage_balance >=  " . intval(MIN_MORTGAGE_BALANCE) . "
AND
	property_type NOT IN ('mobile','manufactured')
AND
-- states to exclude:
	state NOT IN ('" . join("','", $STATES_EXCLUDE) . "')
GROUP BY
	email
ORDER BY
	id ASC
";

Diag::Out("query: $query");

$rs = $sql->Query(
	$DB_DEBT
	,$query
	,Debug_1::Trace_Code(__FILE__, __LINE__)
);

Error_2::Error_Test($rs, TRUE);

$morts = array();

while (FALSE !== ($rec = $sql->Fetch_Array_Row($rs)))
{
	if ("" == $rec["credit_counseling"])
		$rec["credit_counseling"] = "N";
	$morts[$rec["id"]] = $rec;
}

$sql->Free_Result($rs);

$csv = '"Date","ip","Fname","Lname","Address1","Address2","City","State","Zip","Phone","Email","LoanType","ConsCredit","HomeVal","PropType","LoanAmt"' . "\r\n";
reset($morts);
while (list($id,$rec) = each($morts))
{
	$csv .= '"'.join('","', array($rec["date"],@$rec["ip"],$rec["fname"],$rec["lname"],$rec["address1"],$rec["address2"],$rec["city"],$rec["state"],$rec["zip"],$rec["phone"],$rec["email"],$rec["loan_type"],$rec["consumer_credit"],$rec["property_value"],$rec["property_type"],$rec["mortgage_balance"]))."\"\r\n";
	$last_id = $rec["id"];
}

Diag::Out("csv: $csv");

$recs = count($morts);

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
	if (DEBUG)
	{
		echo "no records found!\n";
	}
}

send_mail($recs, $csv);

?>



