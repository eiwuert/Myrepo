<?PHP

// require the rest of what we need
require_once('/virtualhosts/lib/error.2.php');
require_once('/virtualhosts/lib/debug.1.php');
require_once('/virtualhosts/lib/mysql.3.php');


$live = TRUE;
if ( !$live )
{
	// Connection information
	define('DB_HOST',	'localhost');
	define('DB_USER',	'root');
	define('DB_PASS',	'');
}
else
{
	define('DB_HOST',	'selsds001');
	define('DB_USER',	'sellingsource');
	define('DB_PASS',	'password');
}

// where should the email go
$email = "john.hawkins@thesellingsource.com";

// create an array of databases to pull form
$properties = array("olp_pcl_visitor", "olp_ca_visitor", "olp_ucl_visitor");

// open the csv file
$filename = "/tmp/hawkins.csv";
if( !$fp = fopen($filename, "a") )
{
	die("could not open ".$filename);
}


session_start();

//connect to the database
$sql = new MySQL_3 ();
$rs = $sql->Connect('BOTH',DB_HOST,DB_USER,DB_PASS,Debug_1::Trace_Code(__FILE__, __LINE__));
Error_2::Error_Test($rs, TRUE);

$totals = array();
$file_contents = '';
foreach( $properties AS $key => $database )
{
	$query = "SELECT application_id,session_info FROM application join session_site on (application.session_id = session_site.session_id)
			WHERE application.type = 'VISITOR' AND application.created_date BETWEEN '20040700000000' AND '20040726000000'";
	$result = $sql->Query($database, $query, Debug_1::Trace_Code(__FILE__, __LINE__));
	
	Error_2::Error_Test($result, TRUE);
	
	$totals[$database]["bad"] = 0;
	$totals[$database]["good"] = 0;
	while( ($row = $sql->Fetch_Object_Row($result)) /*&& $good < 5000*/ )
	{
		session_decode($row->session_info);	
			
		// make the variable shorter
		$data = $_SESSION["data"];
		
		if( isset($data["name_first"]) && isset($data["name_last"]) &&
			isset($data["home_street"]) && isset($data["home_city"]) && isset($data["home_state"]) &&
			isset($data["home_zip"]) && isset($data["phone_home"]) )
		{
			$totals[$database]["good"]++;
			
			$string = $data["name_first"].",";
			$string .= $data["name_last"].",";
			$string .= $data["home_street"].",";
			$string .= $data["home_city"].",";
			$string .= $data["home_state"].",";
			$string .= $data["home_zip"].",";
			$string .= $data["phone_home"]."\n";
			
			// create the file
			fwrite($fp, $string);
			$file_contents .= $string;
		}
		else 
		{
			$totals[$database]["bad"]++;	
		}	
	}
}

//echo "Good: $good\n<br>";
//echo "Bad: $bad\n<br>";

echo "<pre>";print_r($totals);

fclose($fp);



// Build the header
$header = new stdClass ();
$header->port = 25;
$header->url = "sellingsource.com";
$header->subject = "Incompleted Applications - ".date("Y-m-d h:i:s")."";
$header->sender_name = "Selling Source";
$header->sender_address = "no-reply@sellingsource.com";

// Build the recipient
$recipient1 = new stdClass ();
$recipient1->type = "to";
$recipient1->name = 'Vendor';
$recipient1->address = $email;

// Build the message
$message = new stdClass ();
$message->text = "Attached Report Files";

// Build the attachment
$attachment1 = new StdClass ();
$attachment1->name = "incompleteapps.csv";
$attachment1->content = base64_encode($file_contents);
$attachment1->content_type = "plain/text";
$attachment1->content_length = strlen ($file_contents);
$attachment1->encoded = "TRUE";

// Send the email
include_once("prpc/client.php");
$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
$mailing_id = $mail->CreateMailing ("New Account At ".$_SESSION["config"]->site_name, $header, NULL, NULL);
$package_id =$mail->AddPackage ($mailing_id, array ($recipient1), $message, array ($attachment1));
$result = $mail->SendMail ($mailing_id);

?>