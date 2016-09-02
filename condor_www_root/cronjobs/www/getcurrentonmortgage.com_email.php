<?php
require_once ('/virtualhosts/lib/mysql.3.php');

$sql = new MySQL_3 ();

Error_2::Error_Test ($sql->Connect (NULL, 'selsds001', 'sellingsource', 'password'), 1);

$day_start = date("Y-m-d", strtotime ("-3 day"));
$table = "stats38980_" . date("Y_m", strtotime ("-3 day"));


$query= "SELECT * FROM getcurrentonmortgage where created_date like '%".$day_start."%'";
$result = $sql->Query ('emv_visitor', $query);
Error_2::Error_Test ($result, 1);

$csv = '';
$data = '';
$new_count = 0;
while ($row = $sql->Fetch_Array_Row ($result))
{
 if ((strtoupper($row['first_name']) != "TEST") && ($row['first_name'] != ""))
 {
	
	 $email_info['text_message'] .= "
	
	
	 First Name: ".$row['first_name']."
	 Last Name: ".$row['last_name']."
	 Street Address: ".$row['home_street']."
	 City: ".$row['home_city']."
	 State: ".$row['home_state']."
	 Zip: ".$row['home_zip']."
	 Email: ".$row['email']."
	 Home Phone: ".$row['phone_primary']."
	 Work Phone: ".$row['phone_secondary']."
	 Best Time To Call: ".$row['best_call_time']."
	 1st Mortgage Lender: ".$row['mortgage_lender_first']."
	 Mortgage Loan Type: ".$row['loan_type']."
	 Monthly Payment: ".$row['mortgage_first_monthly_payment']."
	 Months Behind: ".$row['mortgage_first_months_behind']."
	 Foreclosure Date: ".$row['foreclosure_month']."-".$row['foreclosure_day']."-".$row['foreclosure_year']."
	 Sale Date: ".$row['sale_month']."-".$row['sale_day']."-".$row['sale_year']."
	 2nd Mortgage Lender: ".$row['mortgage_lender_second']."
	 Monthly Payment: ".$row['mortgage_second_monthly_payment']."
	 Months Behind: ".$row['mortgage_second_months_behind']."
	 Mortgage Co. recently turned down a payment: ".$row['turned_down']."
	 Home Purchase Price: ".$row['home_purchase_price']."
	 Owed Balance: ".$row['owed_balance']."
	";
	
   $line = '';  
   $line .= str_replace(',', '', $row['first_name']) . ",";
	 $line .= str_replace(',', '', $row['last_name']) . ",";
	 $line .= str_replace(',', '', $row['home_street']) . ",";
	 $line .= str_replace(',', '', $row['home_city']) . ",";
	 $line .= str_replace(',', '', $row['home_zip']) . ",";
	 $line .= str_replace(',', '', $row['email']) . ",";
	 $line .= str_replace(',', '', $row['phone_primary']) . ",";
	 $line .= str_replace(',', '', $row['phone_secondary']) . ",";
	 $line .= str_replace(',', '', $row['best_call_time']) . ",";
	 $line .= str_replace(',', '', $row['mortgage_lender_first']) . ",";
	 $line .= str_replace(',', '', $row['loan_type']) . ",";
	 $line .= str_replace(',', '', $row['mortgage_first_monthly_payment']) . ",";
	 $line .= str_replace(',', '', $row['mortgage_first_months_behind']). ",";
	 $line .= $row['foreclosure_month'] ."-" .$row['foreclosure_day'] . "-" .$row['foreclosure_year'].",";
	 $line .= $row['sale_month'] ."-" .$row['sale_day'] . "-" .$row['sale_year'].",";
	 $line .= str_replace(',', '', $row['mortgage_lender_second']) . ",";
	 $line .= str_replace(',', '', $row['mortgage_second_monthly_payment']) . ",";
	 $line .= str_replace(',', '', $row['mortgage_second_months_behind']) . ",";
	 $line .= str_replace(',', '', $row['turned_down']) . ",";
	 $line .= str_replace(',', '', $row['home_purchase_price']) . ",";
	 $line .= str_replace(',', '', $row['owed_balance'])."\n";
	 $new_count++;
	 }	
	 $data .= trim($line)."\n";
	 
}
$csv = str_replace("\r","",$data); 
$email_info['text_message'] = ($new_count != 0) ? $email_info['text_message'] : "There has not been any new entries.";	
    

$header = new StdClass ();
$header->subject = 'GetCurrentOnMortgage Leads';
$header->sender_name = 'noreply';
$header->sender_address = 'no-reply@getcurrentonmortgage.com';

$recipient1 = new StdClass ();
$recipient1->type = 'to';
$recipient1->name = '';
$recipient1->address = 'mel.leonard@thesellingsource.com';
//apparently, these people are not going to get the leads till they pay
//$recipient1->address = 'gsmithi2000@hotmail.com';

$recipient2 = new StdClass ();
$recipient2->type = 'Bcc';
$recipient2->name = '';
$recipient2->address = 'mel.leonard@thesellingsource.com';

$recipients = array ($recipient1, $recipient2);

$message = new StdClass ();
$message->text = "Daily Submits For ".$day_start."\r\n".$email_info['text_message'];

if ($new_count != 0) 
{
 $attach = new stdClass ();
 $attach->name = "GetCurrentOnMortgage.com_".$day_start.".csv";
 $attach->content = base64_encode ($csv);
 $attach->content_type = "text/x-csv";
 $attach->content_length = strlen ($csv);
 $attach->encoded = "TRUE";
}

include_once("prpc/client.php");
$mail = new prpc_client("prpc://smtp.2.soapdataserver.com/smtp.1.php");

$mailing_id = $mail->CreateMailing ("GetCurrentOnMortgage.com Daily Submits", $header, NULL, NULL);
$package_id =$mail->AddPackage ($mailing_id, $recipients, $message, array ($attach));
$result = $mail->SendMail ($mailing_id);

//find out the block_id for the default promo id
$query2= "SELECT * FROM id_blocks where page_id = '38980' and promo_id = '10000' and stat_date = '".$day_start."'";
$result2 = $sql->Query ('emv_tracking', $query2);
Error_2::Error_Test ($result2, 1);
$row2 = $sql->Fetch_Array_Row ($result2);
$block_id = $row2["block_id"];

//turn off the stats till the email is turned back on
//update the stats
//$update= "Update $table Set vendor3 = '".$new_count."' where block_id = '".$block_id."'";
//$result3 = $sql->Query ('emv_tracking', $update);
//Error_2::Error_Test ($result3, 1);

?>