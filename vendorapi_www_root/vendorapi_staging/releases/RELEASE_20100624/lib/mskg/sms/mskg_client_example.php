<?php
require_once('mskg/sms/Client.php');

/**
 * See the phpdocs in '/virtualhosts/mskg/mskg_client.php'.
 */
$env = 'DEV';

echo 'ENVIRONMENT = ' . $env . "\n";

$client = new mskg_api($env);
die();
// Your Company ID will be issued to you
$company_id = 1;

/**
echo "What messages can I send?\n";

 * You could go to the MSKG API web site -> Campaigns -> Manage Messages and look for
 * messages that have a Type of 'API' and a Status of 'Approved'
 * OR you could use the API.
 */
$result = $client->get_messages($company_id);
var_dump($result);
//list($error, $warnings_array, $messages) = $result;
die();
/**
echo "Verify the number.\n";

 * Cell number:
 * The '1' at the beginning is not required.
 * Non-digits will be stripped out, so you can leave in the '(', ')', '-', etc.
 */
$cell = '7022342749';
//$cell = '7028855985';
//$cell = '702-885-5985';
//$cell = '1-702-885-5985';
$result = $client->verify_number($cell);
var_dump($result);
//list($error, $warnings_array, $carrier) = $result;


echo "Send a message.\n";
/**
 * Campaign ID:
 * Go to the MSKG API web site -> Campaigns
 */
$campaign_id = 12;
/**
 * $message_key_or_id:
 * As described earlier about getting messages.
 */
$message_key_or_id = 20;
$tokens = array(
	'test' => 'asdfjklwqyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyeruiop',
	'this' => 'a BBQ Grill message things',
	'message' => 15
	);
echo 'Token length: ' . strlen($tokens['test']) . "\n";

$result = $client->msg($campaign_id, $cell, $message_key_or_id, $tokens);
var_dump($result);
list($error, $warnings_array, $transaction_id, $message_sent) = $result;
echo "\n\n\n\n";
/**
echo "Did it work?<br>";
if ($transaction_id !== null)
{
	echo 'Success... Message sent: '.$message_sent.'<br>';

	echo "Were there any warnings?\n";
	var_dump($warnings_array);

	// echo "What messages did we send to this number?\n";
	// $result = $client->get_cell_message_history($company_id, $cell);
	// var_dump($result);
	// list($error, $warnings_array, $cell_message_history) = $result;

	// echo "What's the history of this transaction?\n";
	// $result = $client->get_transaction_history($transaction_id);
	// var_dump($result);
	// list($error, $warnings_array, $transaction_history) = $result;
}
else
{
	echo 'Failure';

	// $error describes the error
	// $transaction_id is null
}

**/
