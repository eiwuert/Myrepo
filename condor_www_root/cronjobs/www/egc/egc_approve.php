<?php

	//Set the doc root
	$outside_web_space = realpath ("../")."/";
	$inside_web_space = realpath ("./")."/";
	define ("OUTSIDE_WEB_SPACE", $outside_web_space);
	define ("DATABASE", "expressgoldcard");

	require_once ("/virtualhosts/lib/debug.1.php");
	require_once ("/virtualhosts/lib/error.2.php");
	require_once ("/virtualhosts/lib/mysql.3.php");
	require_once ("/virtualhosts/lib/crypt.3.php");
	require_once ("/virtualhosts/lib/xmlrpc_client.2.php");
	require_once ("/virtualhosts/lib/setstat.1.php");
	require_once ("/virtualhosts/lib/smtp_mail_soap_client.1.php");

	$server = new stdClass ();
	$server->host = "read1.iwaynetworks.net";
	$server->user = "sellingsource";
	$server->pass = "%selling\$_db";

	// Connection Info
	define('SSO_SOAP_SERVER_PATH', '/');
	define('SSO_SOAP_SERVER_URL', 'smartshopperonline.soapdataserver.com');
	define('SSO_SOAP_SERVER_PORT', 80);

	// Create sql connection(s)
	$sql = new MySQL_3 ();
	$result = $sql->Connect (NULL, $server->host, $server->user, $server->pass, Debug_1::Trace_Code (__FILE__, __LINE__));

	$date_stat = date("Y-m-d", strtotime("-13 day")); //-20 is 21 days

	$query = "SELECT customer.promo_id, customer.promo_sub_code, account.cc_number FROM account,customer WHERE customer.cc_number = account.cc_number AND
	account.account_status = 'PENDING' AND account.stat_hit = 'N' AND account.sign_up < '".$date_stat."' AND account.sign_up != '00000000000000'";

	$result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

	while (FALSE !== ($row_data = $sql->Fetch_Object_Row ($result)))
	{
		$acct = $row_data->cc_number;
		$statcount->{$acct}=$row_data;
	}

	$rec_count = get_object_vars($statcount);
	$rec_count = count($rec_count);
	$e_counter = 0;
	if($rec_count>0)
	{
		foreach($statcount as $count_hit)
		{
			$e_counter++;
			$promo_id = $count_hit->promo_id;
			$promo_sub_code = $count_hit->promo_sub_code;
			$column = 'approved';

			$promo_status = new stdclass();
			$promo_status->valid = "valid";

			$base = "egc_stat";

			$stat_data = Set_Stat_1::Setup_Stats('1833', '0', '1835', $promo_id, $promo_sub_code, $sql, $base, $promo_status->valid, $batch_id = NULL);
			Set_Stat_1::Set_Stat ($stat_data->block_id, $stat_data->tablename, $sql, $base, $column);
		}
	}

	// If 14+ days old, add to file
	$query = "SELECT account.cc_number, DATE_FORMAT(account.sign_up, '%Y-%m-%d') as sign_up,
	customer.first_name, customer.last_name, customer.address_1, customer.address_2,
	customer.city, customer.state, customer.zip FROM account,customer WHERE customer.cc_number = account.cc_number AND
	account.account_status = 'PENDING' AND account.stat_hit = 'N' AND account.sign_up < '".$date_stat."' AND account.sign_up != '00000000000000'";

	$result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

	while (FALSE !== ($row_data = $sql->Fetch_Object_Row ($result)))
	{
		$acct = $row_data->cc_number;
		$info->{$acct}=$row_data;
	}

	$package_14day = "CC NUMBER, FIRST NAME, LAST NAME, ADDRESS, ADDRESS 2, CITY, STATE, ZIP, SIGN UP\n";

	$rec_count = get_object_vars($info);
	$rec_count = count($rec_count);

	if($rec_count>0)
	{
		foreach($info as $record)
		{
			$package_14day .= $record->cc_number.",".$record->first_name.",".$record->last_name.",".$record->address_1.",".$record->address_2.",".$record->city.",".$record->state.",".$record->zip.",".$record->sign_up."\n";

			$cc_list .= "'".$record->cc_number."',";
		}
	}

	$query = "INSERT INTO batch_file  (file, origination_date, employee_id, batch_type, total_checks, total_amount) VALUES('".base64_encode ($package_21day)."',NOW(),'0','EGC INACTIVE','0','0')";
	$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

	// ** Start Approval Process ** //

	ini_set ("magic_quotes_runtime", 0);
	define ('MIN_BUSINESS_DAYS', 14);

	// Create the xmlrpc_client
	$soap_client = new xmlrpc_client (SSO_SOAP_SERVER_PATH, SSO_SOAP_SERVER_URL, SSO_SOAP_SERVER_PORT);

	// Build the holidays array
	$result = $sql->Query ("d2_management", "select * from holidays", Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);

	$holidays = array ();
	while ($row = $sql->Fetch_Object_Row ($result))
	{
		$holidays[$row->date] = TRUE;
	}

	$select = "transaction_0.transaction_id, transaction_0.cc_number, transaction_0.ach_total, account.ach_routing_number AS routing_number, ";
	$select .= "account.ach_account_number AS acctno, customer.first_name, customer.last_name, customer.email, ";
	$select .= "customer.address_1 AS address1, customer.address_2 AS address2, customer.city, customer.state, ";
	$select .= "customer.zip, customer.ssn, DATE_FORMAT(account.sign_up, '%Y-%m-%d') AS sign_up";

	$from = "transaction_0, customer, account";
	$where = "transaction_0.transaction_status = 'SENT' AND transaction_0.transaction_type = 'ENROLLMENT' AND ";
	$where .= "transaction_0.cc_number = customer.cc_number AND account.cc_number = customer.cc_number AND transaction_0.cc_number IN(".substr($cc_list,0,-1).")";
	$query = "SELECT ".$select." FROM ".$from." WHERE ".$where."";

	$result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);

	while ($row = $sql->Fetch_Object_Row ($result))
	{
		$trans_list .= "'".$row->transaction_id."',";
		$row->first_name = ucwords(strtolower($row->first_name));
		$row->last_name = ucwords(strtolower($row->last_name));
		$row->email = strtolower($row->email);

		//Create the Smart Shopper Account
		$soap_args = array (
			"firstname" => $row->first_name,
			"lastname" => $row->last_name,
			"email" => $row->email,
			"address_line_1" => $row->address1,
			"address_line_2" => $row->address2,
			"address_city" => $row->city,
			"address_state" => $row->state,
			"address_zip" => $row->zip,
			"password_hash" => trim(Crypt_3::Encrypt (substr($row->ssn, -4), 'rodric.nick')),
			"egc_number" => $row->cc_number,
			"ach_routing" => $row->routing_number,
			"ach_account" => $row->acctno,
			"social_security" => preg_replace ("/[^\d]/", "", $row->ssn)
		);

		//$soap_client->setDebug (1);

		$soap_call = new xmlrpcmsg ("Create_Account", array (php_xmlrpc_encode ($soap_args)));
		$soap_result = $soap_client->send ($soap_call);

		if ($soap_result->faultCode ())
		{
			echo "SOAP Fault:".$soap_result->faultCode ().":".$soap_result->faultString ()."\n";
		}

		// Build their email
		$first_name = $row->first_name;
		$last_name = $row->last_name;
		$user_name = $row->email;
		$password = substr($row->ssn, -4);
		$card = chunk_split ($row->cc_number, 4, " ");


		// Build the header
		$header = new stdClass ();
		$header->port = 25;
		$header->url = "expressgoldcard.com";
		$header->subject = "Welcome to Express Gold Card!";
		$header->sender_name = "Express Gold Card";
		$header->sender_email = "info@expressgoldcard.com";

		// Build the recipient
		$recipient1 = new stdClass ();
		$recipient1->type = "to";
		$recipient1->name = $first_name.' '.$last_name;
		$recipient1->address = $row->email;

		$recipient2 = new stdClass ();
		$recipient2->type = "bcc";
		$recipient2->name = 'Beaker';
		$recipient2->address = 'nickw@sellingsource.com';

		// Build the message
		$message = new stdClass ();
		//$message->text = "This is the text message";

		ob_start ();
?>
<html>
<head>
	<title>Get Cash Now</title>
</head>

<body marginheight="0" marginwidth="0" topmargin="0" leftmargin="0">

<table cellpadding="1" cellspacing="0" border="0" align="center" bgcolor="#ffffff">
	<tr>
		<td>
			<table cellpadding="0" cellspacing="0" border="0" align="center">
				<tr>

					<td><img src="http://www.imagedataserver.com/nms/_emails_pops/EGCemails/addons/email_030501-CBS/egc_01.gif" width="500" height="171" alt="" border="0"></td>
				</tr>
				<tr>
					<td background="http://www.imagedataserver.com/nms/_emails_pops/EGCemails/addons/email_030501-CBS/egc_02.gif" width="500" height="58" align="center">
						<table width="80%" cellpadding="0" cellspacing="0" border="0">
							<tr>

                <td style="font-family: arial; font-size: 11px; font-weight: 700;" align="center">
                  Your membership kit is on the way! You&#039;ll receive your $150
                  gift certificate and your bonus 3 day/2 night hotel accommodation
                  voucher within the next 2 weeks! </td>
							</tr>

						</table>
					</td>
				</tr>
				<tr>
					<td><img src="http://www.imagedataserver.com/nms/_emails_pops/EGCemails/addons/email_030501-CBS/egc_03.gif" width="500" height="46" alt="" border="0"></td>
				</tr>
				<tr>
					<td background="http://www.imagedataserver.com/nms/_emails_pops/EGCemails/addons/email_030501-CBS/egc_04.gif" width="500" height="49" align="center">
						<table width="80%" cellpadding="0" cellspacing="0" border="0">

							<tr>
								<td style="font-family: arial; font-size: 11px; font-weight: 500;">
								You are Pre-Qualified for a Cash Advance from 123OnlineCash.com!
								Get cash for tuition, groceries, car repairs or bills! 123OnlineCash
								can loan you up to $500.00! Fast 2-minute application. Get the cash
								you need!
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>

					<td><a href="http://123onlinecash.com/?promo_id=11280" target="_blank"><img src="http://www.imagedataserver.com/nms/_emails_pops/EGCemails/addons/email_030501-CBS/egc_05.gif" width="500" height="84" alt="" border="0"></a></td>
				</tr>
			</table>
		</td>
	</tr>
</table>

</body>
</html>
<?php
		$message->html = ob_get_contents ();
		ob_end_clean ();

		// Build a temp object to hold the deliverable
		$temp = new stdClass ();
		$temp->recipient = array ($recipient1, $recipient2);
		$temp->message = $message;

		// Build the deliverable array
		$deliverable [] = $temp;

		$message = new stdClass ();
		$message->text = "

Dear $first_name $last_name,

Welcome to Express Gold Card! Your bank account has been successfully debited for $".number_format($row->ach_total,2,'.',',')."  You can expect to receive your Express Gold Card with a $7500 credit line in the mail within the next 3 days.  You can however begin shopping right away at www.SmartShopperOnline.com by using the following information when you log on:

Username: $user_name
Password: $password

Express Gold Card Number: $card

We do ask that before you purchase anything from SmartShopperOnline.com you do first go over our terms and conditions, which can be found at http://www.expressgoldcard.com/terms.html.

If you should have any questions regarding this email or your Express Gold Card please email us at info@expressgoldcard.com.
";

		$temp->message = $message;

		$deliverable [] = $temp;

		//mail ($row->email, "Welcome to Express Gold Card!", $email, "From: Express Gold Card <info@expressgoldcard.com>");
		//mail ("nickw@sellingsource.com", "Welcome to Express Gold Card!", $email, "From: Express Gold Card <info@expressgoldcard.com>");

		$mail = new Smtp_Mail_Soap_Client ('soap.maildataserver.com');

//print_r($header); print_r($deliverable);

		$result = $mail->Send_Mail ($deliverable, $header);

//print_r($mail->__get_wire());

/*
echo "\n\nRESULT\n";
print_r($result);
exit;
*/

		$query = "INSERT INTO `sent_documents' SET send_date = NOW(), send_time = NOW(), cc_number = '".$row->cc_number."', document_name ='EGC_Approved', user_id = '0', method = 'EMAIL'";
		$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	}

	//Set their status
	if(strlen($cc_list))
	{
		$query = "UPDATE account SET account_status='INACTIVE', activation = NOW(), stat_hit = 'Y' WHERE cc_number IN(".substr($cc_list,0,-1).")";
		$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	}

	if(strlen($trans_list))
	{
		$query = "UPDATE transaction_0 SET transaction_status = 'APPROVED', transaction_balance = transaction_balance-ach_total WHERE transaction_id IN(".substr($trans_list,0,-1).")";
		$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

		$query = "UPDATE transaction_line_item SET line_item_status = 'APPROVED', line_item_balance = '0.00' WHERE rel_transaction_id IN(".substr($trans_list,0,-1).")";
		$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	}

	mail ("nickw@sellingsource.com", "Approved Count", $e_counter, "From: Express Gold Card <info@expressgoldcard.com>");

	$outer_boundry = md5 ("LlamaLlamaLlama");

	$headers =
		"From: noreply <noreply@expressgoldcard.com>\r\n".
		"MIME-Version: 1.0\r\n".
		"Content-Type: Multipart/Mixed;\r\n boundary=\"".$outer_boundry."\"\r\n".
		"This is a multi-part message in MIME format.\r\n\r\n";

	$csv_email =
		"--".$outer_boundry."\r\n".
		"Content-Type: text/plain;\r\n".
		" charset=\"us-ascii\"\r\n".
		"Content-Transfer-Encoding: 7bit\r\n".
		"Content-Disposition: inline\r\n\r\n".
		"Approved Express Gold Cards for ".date("Y-m-d h:i:s")."\r\n".
		"--".$outer_boundry."\r\n".
		"Content-Type: text/plain;\r\n".
		" charset=\"us-ascii\"\r\n".
		" name=\"InactiveReport_21Day - ".date ("md")."\"\r\n".
		"Content-Transfer-Encoding: 7bit\r\n".
		"Content-Disposition: attachment; filename=\"InactiveReport_14Day - ".date ("md").".txt\"\r\n\r\n".
		$package_14day."\r\n".
		"--".$outer_boundry."--\r\n\r\n";

	mail ("nickw@sellingsource.com", "Express Gold Card Approved List - ".date("Y-m-d h:i:s"), $csv_email, $headers);
	mail ("approval-department@expressgoldcard.com", "Express Gold Card Approved List - ".date("Y-m-d h:i:s"), $csv_email, $headers);
	
	
	// Approvals Are Complete Everything Else Hit As Denied
	$date_stat = date("Y-m-d", strtotime("-13 day")); //-20 is 21 days

	$query = "SELECT customer.promo_id, customer.promo_sub_code, account.cc_number FROM account,customer WHERE customer.cc_number = account.cc_number AND 
	account.stat_hit = 'N' AND account.sign_up < '".$date_stat."' AND account.sign_up != '00000000000000'";

	$result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

	while (FALSE !== ($row_data = $sql->Fetch_Object_Row ($result)))
	{
		$acct = $row_data->cc_number;
		$statcount->{$acct}=$row_data;
	}

	$rec_count = get_object_vars($statcount);
	$rec_count = count($rec_count);
	$e_counter = 0;
	if($rec_count>0)
	{
		foreach($statcount as $count_hit)
		{
			$e_counter++;
			$promo_id = $count_hit->promo_id;
			$promo_sub_code = $count_hit->promo_sub_code;
			$cc_number = $count_hit->cc_number;
			$column = 'denied';

			$promo_status = new stdclass();
			$promo_status->valid = "valid";

			$base = "egc_stat";

			$stat_data = Set_Stat_1::Setup_Stats('1833', '0', '1835', $promo_id, $promo_sub_code, $sql, $base, $promo_status->valid, $batch_id = NULL);
			Set_Stat_1::Set_Stat ($stat_data->block_id, $stat_data->tablename, $sql, $base, $column);
			
			$query = "UPDATE account SET stat_hit = 'Y' WHERE cc_number = '".$cc_number."'";
			$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		}
	}
?>
