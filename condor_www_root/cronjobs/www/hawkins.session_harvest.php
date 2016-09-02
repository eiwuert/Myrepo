<?PHP

ob_start();

// require the rest of what we need
require_once('lib_mode.1.php');
require_once('diag.1.php');
require_once('error.2.php');
require_once('debug.1.php');
require_once('mysql.3.php');
require_once('hit_stats.1.php');

Diag::Enable();

define('STAT_COLUMN', 'h8');

# this runs as a cronjob, so it will never detect "rc" since that's done via a url
# the LICENSE_KEY is for "Harvest_User_Info" under the Partnerweekly
switch (Lib_Mode::Get_Mode())
{
case MODE_LOCAL:
	// Connection information
	define('DB_HOST',	'localhost');
	define('DB_USER',	'root');
	define('DB_PASS',	'');
	define('LICENSE_KEY','15e0bd14ec4952c6397cc1315e1f9fab');
	break;
case MODE_LIVE:
	define('DB_HOST',	'selsds001');
	define('DB_USER',	'sellingsource');
	define('DB_PASS',	'password');
	define('LICENSE_KEY','3301577eb098835e4d771d4cceb6542b');
	break;
default:
	die("can't resolve MODE!");
	break;
}

function mysql2ts($mysql)
{
	#echo "mysql$:$mysql:\n";
	#20040812000000
	return strtotime(preg_replace('/^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/', '\1-\2-\3 \4:\5:\6', $mysql));
}

// where should the email go
$email_stats = "stats@parterweekly.com";
$batch_size = "500";

// create an array of databases to pull form
$properties = array("olp_bb_visitor", "olp_pcl_visitor_archive", "olp_ca_visitor_archive", "olp_ucl_visitor_archive");

// run daily
$date_early = date("YmdHis", strtotime("yesterday 0:00:00"));
$date_late = date("YmdHis", strtotime("yesterday 23:59:59"));

// open the csv file
$filename = "/tmp/hawkins-$date_early-$date_late.csv";
if (FALSE === ($fp = fopen($filename, "w")))
{
	die("could not open ".$filename);
}

session_start();
ob_end_flush();

//connect to the database
$sql = new MySQL_3 ();
$rs = $sql->Connect('BOTH', DB_HOST, DB_USER, DB_PASS, Debug_1::Trace_Code(__FILE__, __LINE__));
Error_2::Error_Test($rs, TRUE);

$totals = array();
$master = array();
$file_contents = '';

reset($properties);
while (list($key, $database) = each($properties))
{
	// get the total amount of records
	$query = "
	SELECT
		count(*) as total
	FROM
		application
	JOIN
		session_site
	ON
		application.session_id = session_site.session_id
	WHERE
		application.type = 'VISITOR'
	AND
		application.created_date BETWEEN '$date_early' AND '$date_late'";

	Diag::Out("sending to $database: $query\n");

	$result = $sql->Query($database, $query, Debug_1::Trace_Code(__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);
	
	$count = $sql->Fetch_Object_Row($result);

	Diag::Out("found {$count->total} rows in '$database'...");
	
	$totals[$database]["session"]["bad"] = 0;
	$totals[$database]["session"]["good"] = 0;
	$low_end = 0;

	while($low_end < $count->total)
	{
	
		$query = "
		SELECT
			application_id
			,application.created_date as created
			,session_info
		FROM
			application
		JOIN
			session_site
		ON
			application.session_id = session_site.session_id
		WHERE
			application.type = 'VISITOR'
		AND
			application.created_date BETWEEN '$date_early' AND '$date_late'
		LIMIT {$low_end}, {$batch_size}";

		Diag::Out("pulling sessions {$low_end} - " . ($low_end + $batch_size) . " for '$database'...\n$query\n");

		$result = $sql->Query($database, $query, Debug_1::Trace_Code(__FILE__, __LINE__));

		Diag::Out("done.");
		
		Error_2::Error_Test($result, TRUE);

		$low_end += $sql->Row_Count($result);

		while (($row = $sql->Fetch_Object_Row($result)) /*&& $good < 5000*/ )
		{
			
			session_decode($row->session_info);	

			$data = array();
			// make the variable shorter
			$data = $_SESSION["data"];

			#die("_SESSION[data]:" . print_r($data, 1));
			
			if (isset($data["name_first"]) && isset($data["name_last"]) &&
				isset($data["home_street"]) && isset($data["home_city"]) && isset($data["home_state"]) &&
				isset($data["home_zip"]) && isset($data["phone_home"]) &&
				!isset($master[$data["email_primary"]])
			){
				$totals[$database]["session"]["good"]++;
				$master[$data["email_primary"]] = array(
					"created" => $row->created
					,"ip" => $data["client_ip_address"]
					,"fname" => $data["name_first"]
					,"lname" => $data["name_last"]
					,"street" => $data["home_street"]
					,"city" => $data["home_city"]
					,"state" => $data["home_state"]
					,"zip" => $data["home_zip"]
					,"phone" => $data["phone_home"]
				);
			}
			else 
			{
				$totals[$database]["session"]["bad"]++;	
			}	
		}
		
	}
}

// now lets get the information from the database
reset($properties);
while (list($key, $database) = each($properties))
{
	$query = "
	SELECT
		personal.first_name
		,personal.last_name
		,personal.home_phone
		,personal.email AS email
		,residence.address_1
		,residence.city
		,residence.state
		,residence.zip
		,account.modified_date AS modified_date
		,campaign_info.ip_address AS ip
		,campaign_info.url AS url
	FROM
	 	application
	JOIN
		personal
	ON
		application.application_id = personal.application_id
	JOIN
		residence
	ON
		application.application_id = residence.application_id
	JOIN
		account	
	ON
		application.application_id = account.active_application_id
	JOIN
		campaign_info	
	ON
		application.application_id = campaign_info.application_id
	WHERE
		application.type <> 'VISITOR'
	AND
		application.created_date BETWEEN '$date_early' AND '$date_late'";

	Diag::Out("pulling personal and residence from '$database'...\n$query\n");

	$result = $sql->Query($database, $query, Debug_1::Trace_Code(__FILE__, __LINE__));
	Error_2::Error_Test($result, TRUE);

	Diag::Out("done.");
	
	while ($data = $sql->Fetch_Array_Row($result))
	{

		if (!isset($master[$data["email"]])) {
			$totals[$database]["db"]["good"]++;
			$master[$data["email"]] = array(
				"created" => $data["modified_date"]
				,"ip" => $data["ip"]
				,"site_name" => $data["url"]
				,"fname" => $data["first_name"]
				,"lname" => $data["last_name"]
				,"street" => $data["address_1"]
				,"city" => $data["city"]
				,"state" => $data["state"]
				,"zip" => $data["zip"]
				,"phone" => $data["home_phone"]
			);
		} else {
			$totals[$database]["db"]["bad"]++;
		}
	}
	Diag::Dump($totals[$database], "stats for $database...");
}

Diag::Dump($totals, "Totals");

reset($master);
while (list($k,$v) = each($master))
{
	$file_contents .= join(
		",",
		array(
			$v["fname"]
			,$v["lname"]
			,$v["street"]
			,$v["city"]
			,$v["state"]
			,$v["zip"]
			,$v["phone"]
			,$k
			,$v["ip"]
			,$v["site_name"]
			,$v["created"]
		)
	) . "\r\n";
}

fwrite($fp, $file_contents);
fclose($fp);

$records = count($master);

Diag::Out("hitting stat column '" . STAT_COLUMN . "' for " . $records . "...");
Hit::Stats_Promoless(LICENSE_KEY, $sql, STAT_COLUMN, $records);
Diag::Out("stats done.");

include_once("prpc/client.php");
Diag::Out("creating prpc_client...");
$mail = new PRPC_Client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
$mail->setPrpcDieToFalse();
Diag::Out("done.");

/******************************************** begin receipt **/

// Build the header
$header = new stdClass ();
$header->port = 25;
$header->url = "sellingsource.com";
$header->subject = date("M d H:i", mysql2ts($date_early)) . " - " . date("M d H:i", mysql2ts($date_late)) . " " . $records . " - VP-NIGHTLY";
$header->sender_name = "Selling Source";
$header->sender_address = "no-reply@sellingsource.com";

// Build the recipient
$recipient1 = new stdClass ();
$recipient1->type = "to";
$recipient1->name = "Stats";
$recipient1->address = $email_stats;

// Build the message
$message = new stdClass ();
$message->text = "";

// Send the email
Diag::Out("creating mailing...");
$mailing_id = $mail->CreateMailing ("Incomplete apps", $header, NULL, NULL);
Diag::Out("done.");
Diag::Out("adding package...");
$package_id = $mail->AddPackage ($mailing_id, array($recipient1), $message, array());
Diag::Out("done.");
Diag::Out("sending mail...");
$result = $mail->SendMail ($mailing_id);
Diag::Out("done.");

/******************************************** end receipt **/

/******************************************** begin transfer **/

// Build the header
$header = new stdClass ();
$header->port = 25;
$header->url = "sellingsource.com";
$header->subject = "[" . date("Y-m-d", mysql2ts($date_early)) . "] - [" . $records . "] - VP-NIGHTLY";
$header->sender_name = "Selling Source";
$header->sender_address = "no-reply@sellingsource.com";

// Build the recipient
$recipient1 = new stdClass ();
$recipient1->type = "to";
$recipient1->name = 'Vendor';
$recipient1->address = $email;

$recip = array(
	(object)array(
		"type" => "to",
		"name" => "Vendor",
		"address" => "pwleads@19communications.com"
		//"address" => "david@dmbuyers.com"
		//"address" => "evangreenberg@idesktopmedia.com"
	),
	(object)array(
		"type" => "to",
		"name" => "Vendor",
		"address" => "myya.perez@thesellingsource.com"
	),
	(object)array(
		"type" => "to",
		"name" => "Vendor",
		"address" => "laura.gharst@partnerweekly.com"
	)
);
if (MODE == MODE_LOCAL) # just debugging
	$recip = array_slice($recip, 2, 1);

// Build the message
$message = new stdClass ();
$message->text = "Attached Report Files";

// Build the attachment
$attachment1 = new StdClass ();
$attachment1->name = $header->subject . ".csv";
$attachment1->content = base64_encode($file_contents);
$attachment1->content_type = "plain/text";
$attachment1->content_length = strlen ($file_contents);
$attachment1->encoded = "TRUE";

// Send the email
Diag::Out("creating mailing...");
$mailing_id = $mail->CreateMailing("", $header, NULL, NULL);
Diag::Out("done.");
Diag::Out("adding package (" . strlen($attachment1->content) . " bytes)...");
$package_id = $mail->AddPackage($mailing_id, $recip, $message, array ($attachment1));
Diag::Out("done.");
Diag::Out("sending mail...");
$result = $mail->SendMail($mailing_id);

Diag::Out("done.");

/******************************************** end transfer **/

?>
