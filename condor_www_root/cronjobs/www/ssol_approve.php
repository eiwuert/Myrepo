<?php

ini_set ("magic_quotes_runtime", 0);

//define ('MIN_BUSINESS_DAYS', 17);
define("MIN_CALENDAR_DAYS", 5);

// Connection Info
$server = new stdClass ();


$server->cluster1 = new stdClass ();
$server->cluster1->host = 'write1.iwaynetworks.net';
$server->cluster1->user = 'sellingsource';
$server->cluster1->pass = 'password';

/*
$server->cluster1 = new stdClass ();
$server->cluster1->host = "localhost";
$server->cluster1->user = "root";
$server->cluster1->pass = "";
*/

define ('SSO_SOAP_SERVER_PATH', '/');
define ('SSO_SOAP_SERVER_URL', 'smartshopperonline.soapdataserver.com');
define ('SSO_SOAP_SERVER_PORT', 80);	

// Include
require_once ("/virtualhosts/lib/debug.1.php");
require_once ("/virtualhosts/lib/error.2.php");
require_once ("/virtualhosts/lib/mysql.3.php");
require_once ("/virtualhosts/lib/crypt.1.php");
require_once ("/virtualhosts/lib/xmlrpc_client.1.php");

$crypt = new Crypt_1 ();

// Support functions
function Is_Holiday ($day)
{
	global $holidays;
	
	return @$holidays[date ("Y-m-d", $day)];
}

function Is_Weekend ($day)
{
	return ((date ("w", $day) == 6) || (date ("w", $day) == 0));
}


// Create sql connection(s)
$sql = new stdClass ();

foreach ($server as $name => $info)
{
	$sql->$name = new MySQL_3 ();
	$result = $sql->$name->Connect (NULL, $info->host, $info->user, $info->pass, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
}

// Create the xmlrpc_client
$soap_client = new xmlrpc_client (SSO_SOAP_SERVER_PATH, SSO_SOAP_SERVER_URL, SSO_SOAP_SERVER_PORT);


// Build the holidays array
$result = $sql->cluster1->Query ("d2_management", "select * from holidays", Debug_1::Trace_Code (__FILE__, __LINE__));
Error_2::Error_Test ($result, TRUE);

$holidays = array ();
while ($row = $sql->cluster1->Fetch_Object_Row ($result))
{
	$holidays[$row->date] = TRUE;
}

// Calculate the cut off day
$now = time ();

$today = mktime (0, 0, 0, date ("n", $now), date ("j", $now), date ("Y", $now));

$day = strtotime ("-".MIN_CALENDAR_DAYS." days", $today);
echo $day."\n";

/*
$day = $today;
$days_passed = 0;

while ($days_passed < MIN_BUSINESS_DAYS)
{
	$day = strtotime ("-1 day", $day);

	if (! (Is_Holiday ($day) || Is_Weekend ($day)) )
	{
		$days_passed++;
	}
}
*/

// Get orders that should be considered "approved" now
$query = "SELECT transaction_0.* FROM `transaction_0`,`account`
          WHERE transaction_0.transaction_status = 'SENT' AND transaction_0.transaction_type = 'ORDER' AND transaction_0.transaction_source = 'SSO' AND origination_date < '".date("Ymd", $day)."120000'
          AND account.cc_number = transaction_0.cc_number AND account.account_status NOT IN('DENIED','WITHDRAWN','COLLECTIONS','CANCELLED')";

$result = $sql->cluster1->Query ("expressgoldcard", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
Error_2::Error_Test ($result, TRUE);

$approved = array ();
while ($row = $sql->cluster1->Fetch_Object_Row ($result))
{

     $approved[] = $row->cross_reference_id;
	$transactions[] = $row->transaction_id;
}

if (count ($approved))
{
	// Update the Smart Shopper Order
	$soap_args = array (
		"order_id" => $approved,
		"status" => "APPROVED"
	);
	
	//$soap_client->setDebug (1);
	
	$soap_call = new xmlrpcmsg ("Update_Order", array (xmlrpc_encode ($soap_args)));
	
	$soap_result = $soap_client->send ($soap_call);	
	
	if ($soap_result->faultCode ())
	{
		mail("nickw@sellingsource.com", "SOAP Fault", __FILE__."->".__LINE__."\nSOAP Fault:".$soap_result->faultCode ().":".$soap_result->faultString ()."\n");
		exit;
	}
 
	
	// Set their status
	//old query -> $query = "update transaction set transaction_status = 'APPROVED', recieve_batch_date = NOW() where transaction_id in (".implode(",", $transactions).")";
	$query = "UPDATE `transaction_0` SET transaction_status = 'APPROVED' WHERE transaction_id IN(".implode(",", $transactions).")";
	$result = $sql->cluster1->Query ("expressgoldcard", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result);
	
	// Update line item table
	$query = "UPDATE `transaction_line_item` SET line_item_status = 'APPROVED' WHERE line_item_type = 'ACH'
               AND line_item_status = 'SENT' AND line_item_action = 'DOWN PAYMENT' AND rel_transaction_id IN(".implode(",", $transactions).")";
     $result = $sql->cluster1->Query ("expressgoldcard", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result);
}
else
{
	$outer_boundry = md5 ("Outer Boundry");

	$csv = "";

	$batch_headers =
		"MIME-Version: 1.0\r\n".
		"Content-Type: Multipart/Mixed;\r\n boundary=\"".$outer_boundry."\"\r\n\r\n\r\n".
		"--".$outer_boundry."\r\n".
		"Content-Type: text/plain;\r\n".
		" charset=\"us-ascii\"\r\n".
		"Content-Transfer-Encoding: 7bit\r\n".
		"Content-Disposition: inline\r\n\r\n".
		"SmartShopperOnline Approved for ".date ("Y-m-d")."\r\n".
		"--".$outer_boundry."\r\n".
		"Content-Type: text/plain;\r\n".
		" charset=\"us-ascii\";\r\n".
		" name=\"ExpressGoldCard - ".date ("md")."\"\r\n".
		"Content-Transfer-Encoding: 7bit\r\n".
		"Content-Disposition: attachment; filename=\"SmartShopperOnline Approve - ".date ("md").".csv\"\r\n\r\n".
		$csv."\r\n".
		"--".$outer_boundry."--\r\n\r\n";

	mail ("ndempsey@41cash.com", "SmartShopperOnline Approve: ".date ("Y-m-d \a\\t H:i:s"), NULL, $batch_headers);
	mail ("approval-department@expressgoldcard.com", "SmartShopperOnline Approve: ".date ("Y-m-d \a\\t H:i:s"), NULL, $batch_headers);
	mail ("nickw@sellingsource.com", "SmartShopperOnline Approve: ".date ("Y-m-d \a\\t H:i:s"), NULL, $batch_headers);

}

?>
