<?php

require_once ('db2.1.php');

$db2 = new Db2_1 ('OLP', 'web_ufc', 'ufc_web');

Error_2::Error_Test ($db2->Connect (), 1);

$csv = "transaction_id,date_created,promo_sub_code,name_first,name_last,date_birth,bank_name,bank_aba,income_monthly,income_frequency,income_date_one,income_date_two,income_source,income_direct_deposit,email_address,phone_home,phone_fax,phone_cell,street,unit,city,state,zip,has_income,has_minimum_income,has_checking,minimum_age,opt_in,us_citizen,work_name,work_title,work_shift,phone_work,ref1_name,ref1_phone,ref1_relation,ref2_name,ref2_phone,ref2_relation\r\n";
$csv_start = strlen ($csv);

$sql = "
	SELECT
		t.transaction_id, t.date_created, promo_sub_code,
		name_first, name_last, date_birth,
		bank_name, bank_aba,
		DEC(income_monthly) AS income_monthly,
		inf.name AS income_frequency,
		income_date_one,
		income_date_two,
		ins.name AS income_source,
		income_direct_deposit,
		email_address,
		ph.phone_number AS phone_home,
		(SELECT phone_number FROM phone WHERE phone_id = t.active_fax_phone_id) AS phone_fax,
		(SELECT phone_number FROM phone WHERE phone_id = t.active_cell_phone_id) AS phone_cell,
		a.street,
		a.unit,
		a.city,
		(SELECT name FROM state WHERE state_id = a.state_id) AS state,
		a.zip,
		has_income, has_minimum_income, has_checking, minimum_age, opt_in, us_citizen,
		em.name AS work_name, em.title AS work_title, em.shift AS work_shift,
		(SELECT phone_number FROM phone WHERE phone_id = em.active_phone_id) AS phone_work
	FROM
		originating_source os,
		campaign_info ci,
		transaction t,
		customer c,
		income_frequency inf,
		income_source ins,
		email e,
		address a,
		phone ph,
		demographics d,
		employment em
	WHERE
		os.license_key = '421ceac29e229a8832c666ae2326e8ed'
		AND t.originating_source_id = os.originating_source_id
		AND t.transaction_id = ci.transaction_id
		AND t.customer_id = c.customer_id
		AND t.income_frequency_id = inf.income_frequency_id
		AND t.income_source_id = ins.income_source_id
		AND t.active_email_id = e.email_id
		AND t.active_address_id = a.address_id
		AND t.active_home_phone_id = ph.phone_id
		AND t.active_demographics_id = d.demographics_id
		AND t.active_employment_id = em.employment_id
		AND t.date_created BETWEEN ? AND ?
";

$qry = $db2->Query ($sql);
Error_2::Error_Test ($qry);

$sql2 = "
	SELECT
		name_full AS ref_name,
		phone_home AS ref_phone,
		relationship AS ref_relation
	FROM
		reference
	WHERE
		transaction_id = ?
";

$qry2 = $db2->Query ($sql2);
Error_2::Error_Test ($qry2);

$day_start = date ("Y-m-d 00:00:00", strtotime ("-1 day"));
$day_end = date ("Y-m-d 00:00:00");

$res = $qry->Execute ($day_start, $day_end);
Error_2::Error_Test ($res);

while ($row = $qry->Fetch_Array())
{
	$res2 = $qry2->Execute ($row['TRANSACTION_ID']);
	Error_2::Error_Test ($res2);

	$i = 1;
	while ($row2 = $qry2->Fetch_Array())
	{
		$row["REF{$i}_NAME"] = $row2["REF_NAME"];
		$row["REF{$i}_PHONE"] = $row2["REF_PHONE"];
		$row["REF{$i}_RELATION"] = $row2["REF_RELATION"];

		$i++;

		if ($i > 2)
			break;
	}

	foreach ($row as $k => $v)
	{
		$row[$k] = str_replace (',', '', $v);
	}

	$csv .= implode (",", $row)."\r\n";
}

$csv_end = strlen ($csv);

$header = new StdClass ();
$header->subject = 'plf_daily_submits';
$header->sender_name = 'noreply';
$header->sender_address = 'noreply@tssmasterd.com';

$recipient1 = new StdClass ();
$recipient1->type = 'to';
$recipient1->name = '';
$recipient1->address = 'ssleads@speeddog.com';
//$recipient1->address = 'rodric.glaser@thesellingsource.com';

$recipient2 = new StdClass ();
$recipient2->type = 'cc';
$recipient2->name = '';
$recipient2->address = 'don.adriano@thesellingsource.com';

$recipient3 = new StdClass ();
$recipient3->type = 'cc';
$recipient3->name = '';
$recipient3->address = 'john.hawkins@thesellingsource.com';

$recipient4 = new StdClass ();
$recipient4->type = 'cc';
$recipient4->name = '';
$recipient4->address = 'myya.perez@thesellingsource.com';

$recipients = array ($recipient1, $recipient2, $recipient3, $recipient4);

$message = new StdClass ();
$message->text = "Summary of Submits For $day_start to $day_end\r\n\r\n\r\n";

$attach = new stdClass ();
$attach->name = "plf_daily_submits_".str_replace(' 00:00:00', '', $day_start).".csv";
$attach->content = base64_encode ($csv);
$attach->content_type = "text/x-csv";
$attach->content_length = strlen ($csv);
$attach->encoded = "TRUE";

if ($csv_end > $csv_start)
{
	include_once("prpc/client.php");
	$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/smtp.1.php");
	
	$mailing_id = $mail->CreateMailing ("plf_daily_submits", $header, NULL, NULL);
	$package_id =$mail->AddPackage ($mailing_id, $recipients, $message, array ($attach));
	$result = $mail->SendMail ($mailing_id);
}

?>
