<?php

	//Set the doc root
	$outside_web_space = realpath ("../")."/";
	$inside_web_space = realpath ("./")."/";
	define ("OUTSIDE_WEB_SPACE", $outside_web_space);
	define ("DATABASE", "expressgoldcard");

	// the days required to be approved
	define ('MIN_BUSINESS_DAYS', 6);

	require_once ("/virtualhosts/lib/debug.1.php");
	require_once ("/virtualhosts/lib/error.2.php");
	require_once ("/virtualhosts/lib/mysql.3.php");
	require_once ("/virtualhosts/lib/crypt.3.php");
	require_once ("/virtualhosts/lib/xmlrpc_client.2.php");
	require_once ("/virtualhosts/lib/setstat.1.php");
	require_once ("/virtualhosts/lib/soap_smtp_client.3.php");
	$log = "LINE:".__LINE__." Require Files Added\n\n";

	$server = new stdClass ();
	$server->host = "selsds001";
	$server->user = "sellingsource";
	$server->pass = "%selling\$_db";

	/*
	$server = new stdClass ();
	$server->host = "localhost";
	$server->user = "root";
	$server->pass = "";
	*/
	// Connection Info
	define('SSO_SOAP_SERVER_PATH', '/');
	define('SSO_SOAP_SERVER_URL', 'ibm.smartshopperonline.soapdataserver.com');
	define('SSO_SOAP_SERVER_PORT', 80);

	// Email
	require_once ("prpc/client.php");
	$mail = new Prpc_Client ("prpc://smtp.2.soapdataserver.com/smtp.1.php");

	// Create sql connection(s)
	$sql = new MySQL_3 ();
	$result = $sql->Connect (NULL, $server->host, $server->user, $server->pass, Debug_1::Trace_Code (__FILE__, __LINE__));

	$date_stat = date("Y-m-d", strtotime("-" . MIN_BUSINESS_DAYS . " day")); //-20 is 21 days

	$query = "SELECT customer.promo_id, customer.promo_sub_code, account.cc_number, account.stat_hit FROM account,customer WHERE customer.cc_number = account.cc_number AND
	account.account_status = 'PENDING' AND account.stat_hit IN('N','C') AND account.sign_up < '".$date_stat."' AND account.sign_up != '00000000000000'";

	$result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

	while (FALSE !== ($row_data = $sql->Fetch_Object_Row ($result)))
	{
		$acct = $row_data->cc_number;
		$statcount->{$acct}=$row_data;
		$valid = TRUE;
	}

	$e_counter = 0;
	if($valid)
	{
		foreach($statcount as $count_hit)
		{
			$e_counter++;
			$promo_id = $count_hit->promo_id;
			$promo_sub_code = $count_hit->promo_sub_code;
               
               switch($count_hit->stat_hit)
               {
                    case "N":
                    $column = 'approved';
                    break;
                    
                    case "C":
                    $column = 'denied';
                    break;
                    
                    default:
                    NULL;
                    break;
               }
			
			$promo_status = new stdclass();
			$promo_status->valid = "valid";

			$base = "egc_stat";

			$stat_data = Set_Stat_1::Setup_Stats('1833', '0', '1835', $promo_id, $promo_sub_code, $sql, $base, $promo_status->valid, $batch_id = NULL);
			Set_Stat_1::Set_Stat ($stat_data->block_id, $stat_data->tablename, $sql, $base, $column);
		}
	}

	// If 3+ days old, add to file
	$query = "SELECT account.cc_number, account.stat_hit, DATE_FORMAT(account.sign_up, '%Y-%m-%d') as sign_up,
	customer.first_name, customer.last_name, customer.address_1, customer.address_2,
	customer.city, customer.state, customer.zip FROM account,customer WHERE customer.cc_number = account.cc_number AND
	account.account_status = 'PENDING' AND account.stat_hit = 'N' AND account.sign_up < '".$date_stat."' AND account.sign_up != '00000000000000'";

	$result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

	while (FALSE !== ($row_data = $sql->Fetch_Object_Row ($result)))
	{
		$acct = $row_data->cc_number;
		$info->{$acct}=$row_data;
		
		$valid3 = TRUE;
	}

	$package_14day = "CC NUMBER, FIRST NAME, LAST NAME, ADDRESS, ADDRESS 2, CITY, STATE, ZIP, SIGN UP\n";

	

	if($valid3)
	{
		foreach($info as $record)
		{
		     $find[0] = "/street/i";
		     $find[1] = "/avenue/i";
               $find[2] = "/road/i";
               $find[3] = "/north/i";
               $find[4] = "/south/i";
               $find[5] = "/east/i";
               $find[6] = "/west/i";
               $find[7] = "/(\.)|(,)|(;)/";
               $find[8] = "/apartment/i";
               $find[9] = "/#(\S)/";
               $find[10] = "/suite/i";
               
               $replace[0] = "St";
		     $replace[1] = "Ave";
               $replace[2] = "Rd";
               $replace[3] = "N";
               $replace[4] = "S";
               $replace[5] = "E";
               $replace[6] = "W";
               $replace[7] = "";
               $replace[8] = "Apt";
               $replace[9] = "# $1";
               $replace[10] = "Ste";
		     
		     $record->address_1 = preg_replace($find,$replace,$record->address_1);
		     $record->address_2 = preg_replace($find,$replace,$record->address_2);
			$package_14day .= $record->cc_number.",".ucwords($record->first_name).",".ucwords($record->last_name).",".$record->address_1.",".$record->address_2.",".ucwords($record->city).",".$record->state.",".$record->zip.",".$record->sign_up."\n";

			$cc_list .= "'".$record->cc_number."',";
		}
	}

	$query = "INSERT INTO batch_file  (file, origination_date, employee_id, batch_type, total_checks, total_amount) VALUES('".base64_encode ($package_14day)."',NOW(),'0','EGC INACTIVE','0','0')";
	$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

	// ** Start Approval Process ** //
	ini_set ("magic_quotes_runtime", 0);


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
if(strlen($cc_list))
{
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

	while (FALSE !== ($row = $sql->Fetch_Object_Row ($result)))
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
		$header->sender_address = "info@expressgoldcard.com";

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

$html_email = '
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
';
		$message->html = $html_email;

		$mailing_id = $mail->CreateMailing ("EGC_APPROVE", $header, NULL, NULL);
		
		if(!$mailing_id)
		{
			echo "No Mailing Id Created";
		}

		$package_id = $mail->AddPackage ($mailing_id, array ($recipient1,$recipient2), $message, array ());
		
		$sender = $mail->SendMail ($mailing_id);

		// Build the header
		$header = new stdClass ();
		$header->port = 25;
		$header->url = "expressgoldcard.com";
		$header->subject = "Welcome to Express Gold Card!";
		$header->sender_name = "Express Gold Card";
		$header->sender_address = "info@expressgoldcard.com";

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
		$message->text = "

Dear $first_name $last_name,

Welcome to Express Gold Card! Your bank account has been successfully debited for $".number_format($row->ach_total,2,'.',',')."  You can expect to receive your Express Gold Card with a $7500 credit line in the mail within the next 3 days.  You can however begin shopping right away at www.SmartShopperOnline.com by using the following information when you log on:

Username: $user_name
Password: $password

Express Gold Card Number: $card

We do ask that before you purchase anything from SmartShopperOnline.com you do first go over our terms and conditions, which can be found at http://www.expressgoldcard.com/terms.html.

If you should have any questions regarding this email or your Express Gold Card please email us at info@expressgoldcard.com.
";

		$mailing_id = $mail->CreateMailing ("EGC_APPROVE", $header, NULL, NULL);
		
		if(!$mailing_id)
		{
			echo "No Mailing Id Created";
		}

		$package_id = $mail->AddPackage ($mailing_id, array ($recipient1,$recipient2), $message, array ());

		$sender = $mail->SendMail ($mailing_id);

		$query = "INSERT INTO `sent_documents' SET send_date = NOW(), send_time = NOW(), cc_number = '".$row->cc_number."', document_name ='EGC_Approved', user_id = '0', method = 'EMAIL'";
		$sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));

	}
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

	// Build the header
	$header = new stdClass ();
	$header->port = 25;
	$header->url = "expressgoldcard.com";
	$header->subject = "Express Gold Card Approved List - ".date("Y-m-d h:i:s")."";
	$header->sender_name = "Express Gold Card";
	$header->sender_address = "no-reply@expressgoldcard.com";

	// Build the recipient
	
	$recipient1 = new stdClass ();
	$recipient1->type = "to";
	$recipient1->name = 'EGC';
	$recipient1->address = 'approval-department@expressgoldcard.com';
     
     
	$recipient2 = new stdClass ();
	$recipient2->type = "bcc";
	$recipient2->name = 'Beaker';
	$recipient2->address = 'nickw@sellingsource.com';

	// Build the message
	$message = new stdClass ();
	$message->text = "Attached File";

	// Build the attachment
	$attachment1 = new StdClass ();
	$attachment1->name = MIN_BUSINESS_DAYS . "-Day Approved List";
	$attachment1->content = base64_encode ($package_14day);
	$attachment1->content_type = "text/plain";
	$attachment1->content_length = strlen ($package_14day);
	$attachment1->encoded = "TRUE";

	$mailing_id = $mail->CreateMailing ("APPROVE_LIST", $header, NULL, NULL);

		if(!$mailing_id)
		{
			echo "No Mailing Id Created";
		}

	$package_id = $mail->AddPackage ($mailing_id, array ($recipient1,$recipient2), $message, array ($attachment1));

	$sender = $mail->SendMail ($mailing_id);

	// Build the header
	$header = new stdClass ();
	$header->port = 25;
	$header->url = "expressgoldcard.com";
	$header->subject = "Express Gold Card Tail - ".date("Y-m-d h:i:s")."";
	$header->sender_name = "Express Gold Card";
	$header->sender_address = "no-reply@expressgoldcard.com";

	// Build the recipient
	$recipient2 = new stdClass ();
	$recipient2->type = "bcc";
	$recipient2->name = 'Beaker';
	$recipient2->address = 'nickw@sellingsource.com';

	// Build the message
	$message = new stdClass ();
	$message->text = $log;

	$mailing_id = $mail->CreateMailing ("Tail", $header, NULL, NULL);

		if(!$mailing_id)
		{
			echo "No Mailing Id Created";
			break;
		}

	$package_id = $mail->AddPackage ($mailing_id, array ($recipient2), $message, array ());

	$sender = $mail->SendMail ($mailing_id);

	// Approvals Are Complete Everything Else Hit As Denied
	$date_stat = date("Y-m-d", strtotime("-" . MIN_BUSINESS_DAYS . " day")); //-20 is 21 days

	$query = "SELECT customer.promo_id, customer.promo_sub_code, account.cc_number FROM account,customer WHERE customer.cc_number = account.cc_number AND
	account.stat_hit IN('N','C') AND account.sign_up < '".$date_stat."' AND account.sign_up != '00000000000000'";

	$result = $sql->Query (DATABASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
     
	$statcount = new stdClass ();
	while (FALSE !== ($row_data = $sql->Fetch_Object_Row ($result)))
	{
          echo $row_data->cc_number."\n";
		$acct = $row_data->cc_number;
		$statcount->{"id".$acct}=$row_data;
		$valid_2 = TRUE;
	}

	$e_counter = 0;
	if($valid_2)
	{
		foreach($statcount as $count_hit)
		{
			$e_counter++;
			echo $e_counter."\n";
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
