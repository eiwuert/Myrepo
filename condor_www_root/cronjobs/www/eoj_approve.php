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


define ('PRPC_SERVER_URL', 'prpc://store.soapdataserver.com/');
	

// Include
require_once ("/virtualhosts/lib/debug.1.php");
require_once ("/virtualhosts/lib/error.2.php");
require_once ("/virtualhosts/lib/mysql.3.php");
require_once ("/virtualhosts/lib/crypt.1.php");
require_once ("/virtualhosts/lib/prpc/client.php");

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
$client = new Prpc_Client (PRPC_SERVER_URL, $debug_on, $trace_level);


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
$query = "
	SELECT 
		transaction_0.* 
	FROM 
		`transaction_0`,
		`account`,
		`transaction_line_item`
          	WHERE 
		transaction_0.transaction_type = 'ORDER' 
	AND 
		transaction_source = 'EOJ' 
	AND 
		transaction_0.origination_date < '".date("Ymd", $day)."'
          	AND 
		account.cc_number = transaction_0.cc_number 
	AND 
		transaction_0.transaction_id = transaction_line_item.rel_transaction_id 
	AND 
		transaction_line_item.line_item_status = 'PENDING' 
	AND 
		account.account_status NOT IN('DENIED','WITHDRAWN','COLLECTIONS','CANCELLED')";
echo $query;

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
	// Update the EOJ Order
	$args = new stdClass();
	$args->order_id = $approved;
	$args->status = "APPROVED";
	$args->type = "EOJ";
		
	//$client->Update_Order($args);
	 
	
	// Set their status
	//old query -> $query = "update transaction set transaction_status = 'APPROVED', recieve_batch_date = NOW() where transaction_id in (".implode(",", $transactions).")";
	$query = "UPDATE `transaction_0` SET transaction_status = 'APPROVED', ach_total='0' WHERE transaction_id IN(".implode(",", $transactions).")";
	$result = $sql->cluster1->Query ("expressgoldcard", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result);
	
	// Update line item table
	$query = "UPDATE `transaction_line_item` SET line_item_status = 'APPROVED', line_item_balance='0' WHERE line_item_type = 'ACH'
               AND line_item_action = 'DOWN PAYMENT' AND rel_transaction_id IN(".implode(",", $transactions).")";
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
		"EssenceofJewels Approved for ".date ("Y-m-d")."\r\n".
		"--".$outer_boundry."\r\n".
		"Content-Type: text/plain;\r\n".
		" charset=\"us-ascii\";\r\n".
		" name=\"ExpressGoldCard - ".date ("md")."\"\r\n".
		"Content-Transfer-Encoding: 7bit\r\n".
		"Content-Disposition: attachment; filename=\"EssenceofJewels Approve - ".date ("md").".csv\"\r\n\r\n".
		$csv."\r\n".
		"--".$outer_boundry."--\r\n\r\n";

	//mail ("ndempsey@41cash.com", "EssenceofJewels Approve: ".date ("Y-m-d \a\\t H:i:s"), NULL, $batch_headers);
	//mail ("approval-department@expressgoldcard.com", "EssenceofJewels Approve: ".date ("Y-m-d \a\\t H:i:s"), NULL, $batch_headers);
	mail ("nickw@sellingsource.com", "EssenceofJewels Approve: ".date ("Y-m-d \a\\t H:i:s"), NULL, $batch_headers);
}
?>
