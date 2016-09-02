<?php

/*


*/

	define ('SITE_ID', 8869);	// 5000freelongdistance.com
	define ('PAGE_ID', 8871);

	//define ('SQL_HOST', 'localhost');
	define ('SQL_HOST', 'write1.iwaynetworks.net');
	define ('SQL_USER', 'sellingsource');
	define ('SQL_PASS', 'password');

	define ('SQL_SITE_BASE', 'freelongdistance');
	define ('SQL_STAT_BASE', 'direct1_stat');

	require_once ('/virtualhosts/lib/mysql.3.php');
	require_once ('/virtualhosts/lib/setstat.1.php');

	ini_set ('display_errors', 1);
	ini_set ('html_errors', 0);
	ini_set ('magic_quotes_runtime', 0);

	set_time_limit (0);
	error_reporting (E_ALL & ~(E_NOTICE));

	$promo_status = new stdClass ();
	$promo_status->valid = 'valid';

	$sql = new MySQL_3 ();
	Error_2::Error_Test (
		$sql->Connect (NULL, SQL_HOST, SQL_USER, SQL_PASS, Debug_1::Trace_Code (__FILE__, __LINE__)), TRUE
	);

	require_once ("/virtualhosts/lib/crypt.3.php");
	require_once ("/virtualhosts/lib/mime_mail.inc");
 	require_once ("/virtualhosts/cronjobs/www/int2Date.php");
	require_once ("/virtualhosts/lib/gpgclass.php");

	// check for day, if monday pull data for sat,sun,mon
	$ts = time (); //strtotime('2003-06-07 00:15:00');

	$check_today = getdate($ts);
	$today = date ("Ymd", $ts);
	$DirectoneGPG = new GPG_Functions();
	$user = "Dean";

	switch ($check_today['wday'])
	{
		case 0:		// Sunday
		case 6:		// Saturday
			exit (0);
			break;

		case 1:		// Monday
			$int2Date = new int2Date();
			$friday = $int2Date->parseDate(($check_today['yday'] - 3),2003,"");
			$sunday = $int2Date->parseDate(($check_today['yday'] - 1),2003,"");
			$file_name = "batch_$friday"."-"."$sunday".".csv";
			$q  = "SELECT A.applicant_id as APPID, A.*, A.first_name as cust_first_name, A.last_name as cust_last_name, B.first_name as billing_first_name, B.last_name as billing_last_name, B.*, C.*, D.* FROM applicant_information A LEFT JOIN payment_information B on (A.applicant_id = B.applicant_id) LEFT JOIN applicant_status C on (A.applicant_id = C.applicant_id) LEFT JOIN vendor_information D on (A.applicant_id = D.applicant_id) WHERE C.pending BETWEEN ".$friday." AND ".$sunday." AND (C.sent IS NULL OR C.sent = '0000-00-00')";
			$file2encrypt = "/virtualhosts/5000freelongdistance.com/data_mgt/$file_name";
			$outputfile = "/virtualhosts/5000freelongdistance.com/data_mgt/$file_name.gpg";
			break;

		default:
			$int2Date = new int2Date();
			$yesterday = $int2Date->parseDate(($check_today['yday'] - 1),2003,"");
			$file_name = "batch_$yesterday".".csv";
			$q  = "SELECT A.applicant_id as APPID, A.*, A.first_name as cust_first_name, A.last_name as cust_last_name, B.first_name as billing_first_name, B.last_name as billing_last_name, B.*, C.*, D.* FROM applicant_information A LEFT JOIN payment_information B on (A.applicant_id = B.applicant_id) LEFT JOIN applicant_status C on (A.applicant_id = C.applicant_id) LEFT JOIN vendor_information D on (A.applicant_id = D.applicant_id) WHERE C.pending = ".$yesterday." AND (C.sent IS NULL OR C.sent = '0000-00-00')";
			$file2encrypt = "/virtualhosts/5000freelongdistance.com/data_mgt/$file_name";
			$outputfile = "/virtualhosts/5000freelongdistance.com/data_mgt/$file_name.gpg";
			break;
	}

//echo $q; exit;

	//	$file_name = "batch_20030501.csv";
	//	$q  = "SELECT A.applicant_id as APPID, A.*, A.first_name as cust_first_name, A.last_name as cust_last_name, B.first_name as billing_first_name, B.last_name as billing_last_name, B.*, C.*, D.* FROM applicant_information A LEFT JOIN payment_information B on (A.applicant_id = B.applicant_id) LEFT JOIN applicant_status C on (A.applicant_id = C.applicant_id) LEFT JOIN vendor_information D on (A.applicant_id = D.applicant_id) WHERE C.pending = '20030501'";
		// $q  = "SELECT A.applicant_id as APPID, A.*, A.first_name as cust_first_name, A.last_name as cust_last_name, B.first_name as billing_first_name, B.last_name as billing_last_name, B.*, C.*, D.* FROM applicant_information A LEFT JOIN payment_information B on (A.applicant_id = B.applicant_id) LEFT JOIN applicant_status C on (A.applicant_id = C.applicant_id) LEFT JOIN vendor_information D on (A.applicant_id = D.applicant_id) WHERE C.pending BETWEEN 20030414 AND 20030417";
// Query DB for data

	$rs = $sql->Query (SQL_SITE_BASE, $q, Debug_1::Trace_Code (__FILE__, __LINE__));
    $rows = $sql->Row_Count($rs);

	if($rs)
	{
		// we have a valid recordset, continue script
		$file_content = "Promotion,Client Identifcation Code,Customer Phone #,Customer First Name,Customer Last Name,Customer Billing Address 1,Customer Billing Address 2,Customer City,Customer State,Customer Zip,Script Code,Plan Code,Rate Code,Premium Code,Sale Code,Date of Sale,Credit Card Number,Credit Card Expiration Date,Email Address,Filler,Primary Product Description,CVV2\n";
		while ($row = $sql->Fetch_Array_Row($rs))
		{
			// populate $file_content with data from $rs
			$dummycard = FALSE;
			$Promotion = "DOW";
			$Client_ID_Code = $row['APPID'];
			$Customer_Phone = $row['home_phone'];
			$Customer_first_name = $row['cust_first_name'];
			$Customer_last_name = $row['cust_last_name'];
			$Customer_address1 = $row['address1'];
			$Customer_address2 = $row['address2'];
			$Customer_city = $row['city'];
			$Customer_state = $row['state'];
			$Customer_zip = $row['zip'];
			$Script_code = 1226;
			$Plan_Code = 63;
			$Rate_Code = "05";
			$Premium_Code = 617;
			$Sale_Code = "SZ";
			$Date_of_Sale = $row['order_date'];
			list ($year, $month, $day) = split ('[-]', $Date_of_Sale);
			$Date_of_Sale = $year.$month.$day;
			// decrypt CC#
			$temp_CCNum = $row['card_number'];
			$Credit_Card_Number = Crypt_3::Decrypt ($temp_CCNum, "freelongdistance03");
			$Email_Address = $row['email_address'];
			$Filler = "";
			$Product_Desc = "";
			$CVV2 = $row['cvv2_number'];

			// check for dummy card #
			if (preg_match ('/11111111/', $Credit_Card_Number))
			{
				$dummycard = TRUE;
			}

			if (!($dummycard))
			{
				// append values to $file_content
				$file_content .= "$Promotion,$Client_ID_Code,$Customer_Phone,$Customer_first_name,$Customer_last_name,$Customer_address1,$Customer_address2,$Customer_city,$Customer_state,$Customer_zip,$Script_code,$Plan_Code,$Rate_Code,$Premium_Code,$Sale_Code,$Date_of_Sale,$Credit_Card_Number,";

				// format exp_month
				if ($row['exp_month'] <= 9)
				{
					$file_content.="0".$row['exp_month'];

				} else
				{
					$file_content.=$row['exp_month'];
				}

				// format exp_month
				if ($row['exp_year'] <= 9)
				{
					$file_content.="0".$row['exp_year'];
				} else
				{
					$file_content.=$row['exp_year'];
				}

				$file_content.=",$Email_Address,$Filler,$Product_Desc,$CVV2\n";

				$stat_pending [$row['APPID']] = array (
					'order_date' => $row['order_date'],
					'applicant_id' => $row['APPID'],
					'promo_id' => $row['promo_id'],
					'promo_sub_code' => $row['sub_code']
				);
			}

		} // end looping over $rs


		// write $file_content to $file_name
		$file_handle = fopen ("/virtualhosts/5000freelongdistance.com/data_mgt/$file_name", "w+");
		fwrite($file_handle, $file_content);
		fclose($file_handle);

		// run shell comand to encrypt batch file
		// $shell_command = "gpg -e -r Dean /virtualhosts/5000freelongdistance.com/data_mgt/$file_name";
		// shell_exec ($shell_command);

		// gpg encrypt
		$DirectoneGPG->keypath = "/home/ssroot/.gnupg";
		$DirectoneGPG->Config ($user, $file2encrypt, $outputfile);
		$DirectoneGPG->Encrypt();

		$gpg_file = "/virtualhosts/5000freelongdistance.com/data_mgt/$file_name".".gpg";

		// check to see if the encryption was successfull
		if (file_exists ($gpg_file))
		{

			$query = 'INSERT INTO `batch_file` (`data`, `num_records`) VALUES (\''.mysql_escape_string ($file_content).'\', '.count($stat_pending).')';
			Error_2::Error_Test (
				$sql->Query (SQL_SITE_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__)), TRUE
			);

			$fd = fopen($gpg_file, "rb");
			$data = fread($fd, filesize($gpg_file));
			fclose($fd);

			$content_type = "application/octet-stream";
			$file_name.=".pgp";

			$mail = new mime_mail;
			$mail->from     = "jeffb@sellingsource.com";
			$mail->to     	= "jeffb@sellingsource.com";
			$mail->subject  = "Batch file $file_name";
			$mail->body     = "Attached is your PGP encrypted nightly batch file.";
			$mail->add_attachment($data, $file_name, $content_type);
			$mail->send();

			$mail->to     = "Dean Vu - x249 <DVU@directoneusa.com>";
			$mail->send();

			if (count ($stat_pending))
			{
				$query = 'update `applicant_status` set `sent` = NOW() where `applicant_id` in ('.implode(',', array_keys($stat_pending)).')';
				Error_2::Error_Test (
					$sql->Query (SQL_SITE_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__)), TRUE
				);
				//echo $query, "\n";

				foreach ($stat_pending as $app_id => $info)
				{
					$stat = Set_Stat_1::_Setup_Stats ($info ['order_date'], SITE_ID, 0, PAGE_ID, $info ['promo_id'], $info ['promo_sub_code'], $sql, SQL_STAT_BASE, $promo_status, 'week');
					Set_Stat_1::Set_Stat ($stat->block_id, $stat->tablename, $sql, SQL_STAT_BASE, 'pending', 1);
				}
			}
			exit;

		}
		else // encryption failed
		{

			$mail1 = new mime_mail;
			$mail1->from     = "jeffb@sellingsource.com";
			$mail1->to     = "jeffb@sellingsource.com";
			//$mail1->to     = "rodricg@sellingsource.com";
			$mail1->subject     = "Batch file encryption failed";
			$mail1->body     = "The PGP encryption failed for $file_name.";
			$mail1->send();
			exit;

		}
	}
	else // no recordset, email & exit
	{

		$mail2 = new mime_mail;
		$mail2->from     = "jeffb@sellingsource.com";
		$mail2->to     = "jeffb@sellingsource.com";
		//$mail2->to     = "rodricg@sellingsource.com";
		$mail2->subject     = "No data for $file_name";
		$mail2->body     = "No data for $file_name";
		$mail2->send();
		exit;

	}

?>
