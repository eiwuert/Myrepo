<?php



 // 21days_autoapproved.php
 //
 // this file will query applicant_status of the "mbcash_com" DB for pending dates
 // <= 21 days before today then mark those records as approved with todays date.
 // after marking record as approved, stats will be updated

 // includes
	// include ('int2Date.php');
 	require_once ("/virtualhosts/site_config/server.cfg.php");
	// require_once ("/virtualhosts/lib/setstat.1.php");
	require_once ("/virtualhosts/lib/crypt.2.php");
	require_once ("/virtualhosts/lib/mime_mail.inc");
 	require_once ("/virtualhosts/cronjobs/www/int2Date.php");
	require_once ("/virtualhosts/lib/gpgclass.php");
	// define ("DB_NAME", 'rc_freelongdistance');
	define ("DB_NAME", 'freelongdistance');


// check for day, if monday pull data for sat,sun,mon
	$check_today = getdate();
	$today = date ("Ymd");
	$DirectoneGPG = new GPG_Functions();
	$user = "Dean";


	if ($check_today['wday'] == 1)
	{

		$int2Date = new int2Date();
		$friday = $int2Date->parseDate(($check_today['yday'] - 3),2003,"");
		$sunday = $int2Date->parseDate(($check_today['yday'] - 1),2003,"");
		$file_name = "batch_$friday"."-"."$sunday".".csv";
		$q  = "SELECT A.applicant_id as APPID, A.*, A.first_name as cust_first_name, A.last_name as cust_last_name, B.first_name as billing_first_name, B.last_name as billing_last_name, B.*, C.*, D.* FROM applicant_information A LEFT JOIN payment_information B on (A.applicant_id = B.applicant_id) LEFT JOIN applicant_status C on (A.applicant_id = C.applicant_id) LEFT JOIN vendor_information D on (A.applicant_id = D.applicant_id) WHERE C.pending BETWEEN ".$friday." AND ".$sunday;
		$file2encrypt = "/virtualhosts/5000freelongdistance.com/data_mgt/$file_name";
		$outputfile = "/virtualhosts/5000freelongdistance.com/data_mgt/$file_name.gpg";
	} else if ($check_today['wday'] >= 2 && $check_today['wday'] <= 5)
	{
		$int2Date = new int2Date();
		$yesterday = $int2Date->parseDate(($check_today['yday'] - 1),2003,"");
		$file_name = "batch_$yesterday".".csv";
		$q  = "SELECT A.applicant_id as APPID, A.*, A.first_name as cust_first_name, A.last_name as cust_last_name, B.first_name as billing_first_name, B.last_name as billing_last_name, B.*, C.*, D.* FROM applicant_information A LEFT JOIN payment_information B on (A.applicant_id = B.applicant_id) LEFT JOIN applicant_status C on (A.applicant_id = C.applicant_id) LEFT JOIN vendor_information D on (A.applicant_id = D.applicant_id) WHERE C.pending = ".$yesterday;
		$file2encrypt = "/virtualhosts/5000freelongdistance.com/data_mgt/$file_name";
		$outputfile = "/virtualhosts/5000freelongdistance.com/data_mgt/$file_name.gpg";
	}


	//	$file_name = "batch_20030501.csv";
	//	$q  = "SELECT A.applicant_id as APPID, A.*, A.first_name as cust_first_name, A.last_name as cust_last_name, B.first_name as billing_first_name, B.last_name as billing_last_name, B.*, C.*, D.* FROM applicant_information A LEFT JOIN payment_information B on (A.applicant_id = B.applicant_id) LEFT JOIN applicant_status C on (A.applicant_id = C.applicant_id) LEFT JOIN vendor_information D on (A.applicant_id = D.applicant_id) WHERE C.pending = '20030501'";
		// $q  = "SELECT A.applicant_id as APPID, A.*, A.first_name as cust_first_name, A.last_name as cust_last_name, B.first_name as billing_first_name, B.last_name as billing_last_name, B.*, C.*, D.* FROM applicant_information A LEFT JOIN payment_information B on (A.applicant_id = B.applicant_id) LEFT JOIN applicant_status C on (A.applicant_id = C.applicant_id) LEFT JOIN vendor_information D on (A.applicant_id = D.applicant_id) WHERE C.pending BETWEEN 20030414 AND 20030417";
// Query DB for data

	$rs = $sql->Query (DB_NAME, $q);
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
			$Client_ID_Code = $row['APPID']; //Promo_id
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
			$Credit_Card_Number = Crypt_2::Decrypt ($temp_CCNum, "freelongdistance03");
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
					$file_content.="$Promotion,$Client_ID_Code,$Customer_Phone,$Customer_first_name,$Customer_last_name,$Customer_address1,$Customer_address2,$Customer_city,$Customer_state,$Customer_zip,$Script_code,$Plan_Code,$Rate_Code,$Premium_Code,$Sale_Code,$Date_of_Sale,$Credit_Card_Number,";

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


			$fd = fopen($gpg_file, "rb");
			$data = fread($fd, filesize($gpg_file));
			fclose($fd);

			$content_type = "application/octet-stream";
			$file_name.=".pgp";

			$mail = new mime_mail;
			$mail->from     = "jeffb@sellingsource.com";
			$mail->to     = "jeffb@sellingsource.com";
			$mail->subject     = "Batch file $file_name";
			$mail->body     = "Attached is your PGP encrypted nightly batch file.";
			$mail->add_attachment($data, $file_name, $content_type);
			$mail->send();

			$mail->to     = "Dean Vu - x249 <DVU@directoneusa.com>";
			$mail->send();
			exit;
		} else
		{
			// encryption failed
			$mail1 = new mime_mail;
			$mail1->from     = "jeffb@sellingsource.com";
			$mail1->to     = "jeffb@sellingsource.com";
			$mail1->subject     = "Batch file encryption failed";
			$mail1->body     = "The PGP encryption failed for $file_name.";
			$mail1->send();
			exit;
		}



	}else
	{
		// no recordset, email & exit
			// encryption failed
			$mail2 = new mime_mail;
			$mail2->from     = "jeffb@sellingsource.com";
			$mail2->to     = "jeffb@sellingsource.com";
			$mail2->subject     = "No data for $file_name";
			$mail2->body     = "No data for $file_name";
			$mail2->send();
		exit;
	}




?>
