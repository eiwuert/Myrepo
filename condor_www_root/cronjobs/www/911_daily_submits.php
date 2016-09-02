<?php


require_once ('mysql.3.php');


$sql = new MySQL_3 ();

Error_2::Error_Test ($sql->Connect (NULL, 'selsds001', 'sellingsource', 'password'), 1);

$day_start = date("Y-m-d", strtotime ("-1 day"));
$day_end = date("Y-m-d");

$query = "
	SELECT
		DATE_FORMAT(a.created_date, '%Y-%m-%d %H:%i:%s') AS date_created,
		first_name,
		last_name,
		email
	FROM
		application a,
		campaign_info ci,
		personal p
	WHERE
		a.application_id = ci.application_id AND
		a.application_id = p.application_id AND
		license_key = '8b1acc9a8e5e77d48805506765473f60' AND
		a.created_date between '{$day_start}' and '{$day_end}'
	";

$result = $sql->Query ('olp_bb_visitor', $query);
Error_2::Error_Test ($result, 1);

$csv = "date_created,first_name,last_name,email\r\n";

while ($row = $sql->Fetch_Array_Row ($result))
{
	$csv .= implode (",", $row)."\r\n";
}

$header = new StdClass ();
$header->subject = '911_daily_submits';
$header->sender_name = 'noreply';
$header->sender_address = 'noreply@tssmasterd.com';

$recipient1 = new StdClass ();
$recipient1->type = 'to';
$recipient1->name = '';
$recipient1->address = 'service@911paydayadvance.com';

$recipient2 = new StdClass ();
$recipient2->type = 'cc';
$recipient2->name = '';
$recipient2->address = 'don.adriano@thesellingsource.com';

$recipients = array ($recipient1, $recipient2);

$message = new StdClass ();
$message->text = "Daily Submits For ".$day_start."\r\n";

$attach = new stdClass ();
$attach->name = "911_daily_submits_".$day_start.".csv";
$attach->content = base64_encode ($csv);
$attach->content_type = "text/x-csv";
$attach->content_length = strlen ($csv);
$attach->encoded = "TRUE";

include_once("prpc/client.php");
$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/smtp.1.php");

$mailing_id = $mail->CreateMailing ("911_daily_submits", $header, NULL, NULL);
$package_id =$mail->AddPackage ($mailing_id, $recipients, $message, array ($attach));
$result = $mail->SendMail ($mailing_id);

?>
