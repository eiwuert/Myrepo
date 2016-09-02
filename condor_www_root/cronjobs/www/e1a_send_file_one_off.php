#!/usr/local/bin/php
<?php

$database = "equity1auto";

ignore_user_abort (TRUE);
set_time_limit (0);

define ('DB_HOST', 'write1.iwaynetworks.net');
define ('DB_USER', 'sellingsource');
define ('DB_PASS', 'password');

require_once ("/virtualhosts/lib/mysql.3.php");

$sql = new MySQL_3 ();
$sql->Connect ('', DB_HOST, DB_USER, DB_PASS);

$target_date = "2003-11-24";

$query  = "SELECT full_name, email, homephone, order_date, promo_id, sub_code ";
$query .= "FROM applicant_information AS a, vendor_information AS v ";
$query .= "WHERE (order_date < '".$target_date."%') AND (a.applicant_id = v.applicant_id) ";
$query .= "ORDER BY order_date, a.applicant_id";

$result = $sql->Query ($database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
Error_2::Error_Test ($result, TRUE);

$file_data = "";

// If field names are not wanted, comment this section out:
$file_data .= "\"signup_date\",";
$file_data .= "\"name\",";
$file_data .= "\"email\",";
$file_data .= "\"home_phone\",";
$file_data .= "\"promo_id\",";
$file_data .= "\"promo_sub_code\"\r\n";

$empty_length = strlen ($file_data);

while ($row = $sql->Fetch_Object_Row ($result))
{
	$file_data .= "\"".$row->order_date."\",";
	$file_data .= "\"".$row->full_name."\",";
	$file_data .= "\"".$row->email."\",";
	$file_data .= "\"".$row->homephone."\",";
	$file_data .= "\"".$row->promo_id."\",";
	$file_data .= "\"".$row->sub_code."\"\r\n";
}

$filename = "e1a_prior_to_".$target_date.".csv";

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
	$batch_headers .= "Equity1Auto Submits ".$target_date."\r\n\r\n";
	$batch_headers .= "--".$outer_boundary."\r\n";
	$batch_headers .= "Content-Type: text/plain;";
	$batch_headers .= " name=\"".$filename."\"\r\n";
	$batch_headers .= "Content-Transfer-Encoding: 7bit\r\n";
	$batch_headers .= "Content-Disposition: attachment;";
	$batch_headers .= " filename=\"".$filename."\"\r\n\r\n";
	$batch_headers .= $file_data."\r\n\r\n";
	$batch_headers .= "--".$outer_boundary."\r\n\r\n";

	mail ("carolinem@sellingsource.com", $filename, NULL, $batch_headers);
	//mail ("markm@telewebmarketing.com", $filename, NULL, $batch_headers);
	//mail ("johnh@sellingsource.com", $filename, NULL, $batch_headers);
	//mail ("davidb@sellingsource.com", $filename, NULL, $batch_headers);

}

?>
