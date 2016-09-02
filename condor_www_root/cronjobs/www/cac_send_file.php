#!/usr/local/bin/php
<?php

$database = "cac";

ignore_user_abort (TRUE);
set_time_limit (0);

define ('DB_HOST', 'selsds001');
define ('DB_USER', 'sellingsource');
define ('DB_PASS', 'password');

require_once ("/virtualhosts/lib/mysql.3.php");

$sql = new MySQL_3 ();
$sql->Connect ('', DB_HOST, DB_USER, DB_PASS);

$target_date = date ("Y-m-d", strtotime ("yesterday"));

$query = "SELECT * FROM page2_information WHERE date_time LIKE '".$target_date."%'";

$result = $sql->Query ($database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
Error_2::Error_Test ($result, TRUE);

$file_data = "";

// If field names are not wanted, comment this section out:
$file_data .= "\"date_time\",";
$file_data .= "\"first_name\",";
$file_data .= "\"last_name\",";
$file_data .= "\"address\",";
$file_data .= "\"city\",";
$file_data .= "\"state\",";
$file_data .= "\"zip\",";
$file_data .= "\"email\",";
$file_data .= "\"home_phone\",";
$file_data .= "\"work_phone\",";
$file_data .= "\"home_owner\",";
$file_data .= "\"contact_time\",";
$file_data .= "\"total_debt\",";
$file_data .= "\"creditor_1\",";
$file_data .= "\"creditor_2\",";
$file_data .= "\"balance_1\",";
$file_data .= "\"balance_2\",";
$file_data .= "\"payment_1\",";
$file_data .= "\"payment_2\",";
$file_data .= "\"debt_type_1\",";
$file_data .= "\"debt_type_2\",";
$file_data .= "\"behind_1\",";
$file_data .= "\"behind_2\",";
$file_data .= "\"promo_id\",";
$file_data .= "\"promo_sub_code\"\r\n";

$empty_length = strlen ($file_data);

while ($row = $sql->Fetch_Object_Row ($result))
{
	$file_data .= "\"".$row->date_time."\",";
	$file_data .= "\"".$row->first_name."\",";
	$file_data .= "\"".$row->last_name."\",";
	$file_data .= "\"".$row->address."\",";
	$file_data .= "\"".$row->city."\",";
	$file_data .= "\"".$row->state."\",";
	$file_data .= "\"".$row->zip."\",";
	$file_data .= "\"".$row->email."\",";
	$file_data .= "\"".$row->home_phone."\",";
	$file_data .= "\"".$row->work_phone."\",";
	$file_data .= "\"".$row->home_owner."\",";
	$file_data .= "\"".$row->contact_time."\",";
	$file_data .= "\"".$row->total_debt."\",";
	$file_data .= "\"".$row->creditor_1."\",";
	$file_data .= "\"".$row->creditor_2."\",";
	$file_data .= "\"".$row->balance_1."\",";
	$file_data .= "\"".$row->balance_2."\",";
	$file_data .= "\"".$row->payment_1."\",";
	$file_data .= "\"".$row->payment_2."\",";
	$file_data .= "\"".$row->debt_type_1."\",";
	$file_data .= "\"".$row->debt_type_2."\",";
	$file_data .= "\"".$row->behind_1."\",";
	$file_data .= "\"".$row->behind_2."\",";
	$file_data .= "\"".$row->promo_id."\",";
	$file_data .= "\"".$row->promo_sub_code."\"\r\n";
}

$filename = "cac_".$target_date.".csv";

if (strlen($file_data) > $empty_length)
{
	$outer_boundary = md5 ("Outer Boundary");
	$inner_boundary = md5 ("Inner Boundary");

	$batch_headers  = "MIME-Version: 1.0\r\n";
	$batch_headers .= "Content-Type: Multipart/Mixed;";
	$batch_headers .= "boundary=\"".$outer_boundary."\"\r\n\r\n";
	$batch_headers .= "--".$outer_boundary."\r\n";
	$batch_headers .= "Content-Type: text/plain;";
	$batch_headers .= " charset=\"us-ascii\"\r\n";
	$batch_headers .= "Content-Transfer-Encoding: 7bit\r\n";
	$batch_headers .= "Content-Disposition: inline\r\n\r\n";
	$batch_headers .= "CAC Submits ".$target_date."\r\n\r\n";
	$batch_headers .= "--".$outer_boundary."\r\n";
	$batch_headers .= "Content-Type: text/plain;";
	$batch_headers .= " name=\"".$filename."\"\r\n";
	$batch_headers .= "Content-Transfer-Encoding: 7bit\r\n";
	$batch_headers .= "Content-Disposition: attachment;";
	$batch_headers .= " filename=\"".$filename."\"\r\n\r\n";
	$batch_headers .= $file_data."\r\n\r\n";
	$batch_headers .= "--".$outer_boundary."\r\n\r\n";

	mail ("rahuja@edrnow.com", $filename, NULL, $batch_headers);
	mail ("david@dmbuyers.com", $filename, NULL, $batch_headers);
	mail ("traffic@dmbuyers.com", $filename, NULL, $batch_headers);
	mail ("johnh@sellingsource.com", $filename, NULL, $batch_headers);
	mail ("davidb@sellingsource.com", $filename, NULL, $batch_headers);
}

?>
