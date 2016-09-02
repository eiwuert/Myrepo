<?php

require_once("/virtualhosts/site_config/server.cfg.php");

$query = "SELECT
			application.application_id,
			campaign_info.promo_id,
			date_format(application.created_date, '%m/%d/%Y') as date,
			date_format(application.created_date, '%H:%i:%s') as time,
			personal.first_name,
			personal.last_name,
			personal.home_phone,
			employment.work_phone,
			personal.email,
			residence.address_1,
			residence.address_2,
			residence.apartment,
			residence.city,
			residence.state,
			residence.zip,
			ifnull(personal.doc_send_method, 'NOT_CHOSEN') as doc_send_method,
			ifnull(promo_lookup.phone_number, 'NA') as toll_free_number
		FROM
			application,
			personal,
			residence,
			employment,
			campaign_info
			left join promo_lookup on (campaign_info.promo_id = promo_lookup.promo_id)
		WHERE
			application.created_date between date_format(date_add(now(), interval -1 day), '%Y-%m-%d') and curdate()
			AND application.application_id = personal.application_id
			AND application.application_id = residence.application_id
			AND application.application_id = employment.application_id
			AND application.application_id = campaign_info.application_id
			AND campaign_info.active = 'TRUE'";

$result = $sql->Query("olp_ufc_visitor", $query);

$file = "Application_id|Promo_id|Date|Time|First Name|Last Name|Home Phone|Work Phone|Email|Address 1|Address 2|Apartment|City|State|Zip|Delivery Method|Toll Free Num\r\n";

while($row = $sql->Fetch_Array_Row($result))
{
	$file .= implode("|", $row);
	
	$file .= "\r\n";
}

$docs = $file;

// Build the header
$header = new StdClass ();
//$header->smtp_server = "mail.sellingsource.com";
$header->port = 25;
$header->url = "usfastcash.com";
$header->subject = "US Fast Cash ";
$header->sender_name = "UFC Cronjob";
$header->sender_address = "noreply@usfastcash.com";

// Build the attachment
$attachment1 = new StdClass ();
$attachment1->name = "ufcreport.csv";
$attachment1->content = base64_encode ($docs);
$attachment1->content_type = "text/html";
$attachment1->content_length = strlen ($docs);
$attachment1->encoded = "TRUE";

// Build the recipient -- Yah I shoulda used a loop, but I didn't want to pay myself royalties.
$recipient1 = new StdClass ();
$recipient1->type = "to";
$recipient1->name = "";
$recipient1->address = "syoakum@41cash.com";

$recipient2 = new StdClass ();
$recipient2->type = "to";
$recipient2->name = "";
$recipient2->address = "sboch@fc500.com";

$recipient3 = new StdClass ();
$recipient3->type = "to";
$recipient3->name = "";
$recipient3->address = "afinney@41cash.com";

$recipient4 = new StdClass ();
$recipient4->type = "to";
$recipient4->name = "";
$recipient4->address = "cchung@target-response.com";

$recipient5 = new StdClass ();
$recipient5->type = "to";
$recipient5->name = "";
$recipient5->address = "leads@target-response.com";

$message = "Nightly UFC Report";

require_once "/virtualhosts/lib/soap_smtp_client.3.php";

$mail = new SoapSmtpClient_3 ("soap.maildataserver.com");

$mailing_id = $mail->CreateMailing ("UFC_CRONJOB", $header, NULL, NULL);
$package_id =$mail->AddPackage ($mailing_id, array ($recipient1,$recipient2,$recipient3,$recipient4,$recipient5), $message, array ($attachment1));
$result = $mail->SendMail ($mailing_id);

?>