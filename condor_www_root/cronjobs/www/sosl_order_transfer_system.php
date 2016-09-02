<?php
	$hostname = "selsds001";
	$username = "sellingsource";
	$dbpassword = 'password';
	$db="steaksofstlouis_com";
	mysql_connect ($hostname, $username, $dbpassword) or die ('could not connect');
	mysql_select_db( $db ) or die ( 'Unable to select database.' );

	Define_Site_Constants();

	// Determine which file to run
	$date1 = date(YmdH);
	$date2 = date(YmdH);
	$file_name = date("m-d-y_H");

	// create the query date and the file
	switch (TRUE)
	{
		case (date(i)>=0 && date(i) <= 15):
			$date1 .= '0000';
			$date2 .= '1499';
			$file_name .= '-15-00.CSV';
		break;

		case (date(i) >=16 && date(i) <= 30):
			$date1 .= '1500';
			$date2 .= '2999';
			$file_name .= '-30-00.CSV';
		break;

		case (date(i) >31 &&  date(i) <= 45):
			$date1 .= '3000';
			$date2 .= '4499';
			$file_name .= '-45-00.CSV';
		break;

		case (date(i) >=46 && date(i)<= 60):
			$date1 .= '4500';
			$date2 .= '5999';
			$file_name .= '-00-00.CSV';
		break;
	}
	
//	$date1 = date(Ymd, time()-(30 * 86400)).'000000';
//	$date2 = date(Ymd).'000000';

	$q = "SELECT * FROM order_info WHERE ordered BETWEEN ".$date1." AND ".$date2." AND status='Pending'";
//	echo $q.'<br>';
	$r = mysql_query($q) or die(mysql_error());
	$total_number_orders = mysql_num_rows($r);

	// if there are entries, a file will be created
	if ($total_number_orders > 0)
	{
//		echo 'Total Orders NOT 0';
		$file_handle = fopen('/virtualhosts/cronjobs/www/data/'.$file_name, "w");

		if (!$file_handle)
		{
			$email_body = 'Data file named :'.$file_name.'from '.$date1.' and '.$date2.' is not working!!!  Could not establish the file on the server';
			$to = 'Errors';
			$headers	=	"From: Steaks of St. Louis <info@steaksofstlouis.com>\r\n";
			$headers	.=	"Content-type: text/plain\r\n";
			$headers	.=	"X-mailer: PHP/";
			Mail('johnh@sellingsource.com', 'Data file BROKEN!!!', $email_body, $headers);
			// send email error
		}
		else
		{
			unset($customer);
			unset($order_info);

			// Order Information
			while ($order_info = mysql_fetch_object($r))
			{
				unset($billing_first_name);
				unset($billing_last_name);
				unset($billing_address);
				unset($billing_address1);
				unset($billing_address2);
				unset($billing_city);
				unset($billing_state);
				unset($billing_zip);
				unset($shipping_first_name);
				unset($shipping_last_name);
				unset($shipping_address2);
				unset($shipping_city);
				unset($shipping_state);
				unset($shipping_zip);
				unset($counter);
//					unset($order_product);

				// customer information
				$q2 = "SELECT * FROM customer WHERE customer_id=".$order_info->customer_id;
				$r2 = mysql_query($q2) or die('<br>Here is the query'.$q2.'<br>');
				$customer = mysql_fetch_object($r2);

				// order product table
				$order_product = new StdClass();


				// construct the order information fields
				$q3 = "SELECT * FROM order_product WHERE order_id=".$order_info->order_id;
				$r3 = mysql_query($q3) or die('<br>Here is the query'.$q3.'<br>');
				while ($data = mysql_fetch_object($r3))
				{
					if ($counter++ > 0)
					{
						$order_product->product_id .= '|';
						$order_product->product_name .= '|';
						$order_product->product_quantity .= '|';
						$order_product->product_price .= '|';
					}
					$order_product->product_id .= Get_Product_Code($data->product_id);
					$order_product->product_name .= $data->product_name;
					$order_product->product_quantity .= $data->product_quantity;
					$order_product->product_price .= $data->product_price;
				}


				// set up delivery information (also known as 'Shipping Info)
				if (strstr($order_info->delivery_line_1, ","))
				{
					$temp = explode(",", $order_info->delivery_line_1);
					$shipping_address = $temp[0];
					$shipping_addres2 = trim(str_replace(",", "", strstr($order_info->delivery_line_1, ",")));
				}
				else
				{
					$shipping_address = $order_info->delivery_line_1;
				}

				$shipping_city = $order_info->delivery_city;
				$shipping_state = $order_info->delivery_state;
				$shipping_zip = $order_info->delivery_zip;

				// set up shipping name
				$temp = explode(" ", $order_info->delivery_name);
				$shipping_first_name = $temp[0];
				unset($temp[0]);
				$shipping_last_name = implode(" ", $temp);

				if (strstr($order_info->delivery_line_1, ","))
				{
					$temp = explode(",", $order_info->delivery_line_1);
					$shipping_address = $temp[0];
					$shipping_address2 = trim(str_replace(",", "",strstr($order_info->delivery_line_1, ",")));
				}
				else
				{
					$shipping_address = $order_info->delivery_line_1;
				}

				if (strstr($customer->address_line_1, ","))
				{
					$temp = explode(",", $customer->address_line_1);
					$billing_address = $temp[0];
					$billing_address3 = $customer->address_line_2;
					$billing_addres2 = trim(str_replace(",", "", strstr($customer->address_line_1, ",")));
				}
				else
				{
					$billing_address = $customer->address_line_1;
				}

				$billing_first_name = $customer->firstname;
				$billing_last_name = $customer->lastname;
				$billing_city = $customer->address_city;
				$billing_state = $customer->address_state;
				$billing_zip = $customer->address_zip;

				$order_info = Clean_Object($order_info);
				$customer = Clean_Object($customer);

				// check to see if there is an alt delivery date.  If not it is today
				$delivery_date = $order_info->delivery_date  > '0000-00-00' ? date('m/d/y', strtotime($order_info->delivery_date)) : $delivery_date = date('m/d/y');

				if ($order_info->total_discount) $discount = $order_info->total_discount;
				$total_order = $order_info->
				$order_info->shipping_method == 'standard' ? $shipping_method = 'ST' : $shipping_method = 'EXP';
				$line .= '01,'.$order_info->order_id.','.$customer->customer_id.','.$shipping_first_name.','.$shipping_last_name.','.$customer->phone.','.$shipping_address.','.$shipping_address2.','.$shipping_address3.','.$shipping_city.','.$shipping_state.','.$shipping_zip.','.$billing_first_name.','.$billing_last_name.','.$billing_address.','.$billing_address2.','.$billing_address3.','.$billing_city.','.$billing_state.','.$billing_zip.','.$order_product->product_id.','.$order_product->product_name.',,'.$order_product->product_quantity.','.$order_product->product_price.',No PO,'.$customer->email.','.$shipping_method.',,'.$order_info->shipping_cost.','.$order_info->total_cost.','.number_format($order_info->total_discount, 2).','.number_format(round( $order_info->total_discount / ($order_info->total_cost + $order_info->total_discount), 2 ), 2).',,Steaks of St. Louis,'.ORDER_EMAIL.','.ERROR_EMAIL.','.WEBSITE_NAME.',,'.$delivery_date."\r\n";
			}

			fwrite($file_handle, $line);
			fclose($file_handle);
			$result  = FTP_Stuff($file_name);
//			unlink('data/'.$file_name);

			$outer_boundry = md5 ("Outer Boundry");
			$inner_boundry = md5 ("Inner Boundry");

		$batch_headers =
			"MIME-Version: 1.0\r\n".
			"Content-Type: Multipart/Mixed;\r\n boundary=\"".$outer_boundry."\"\r\n\r\n\r\n".
			"--".$outer_boundry."\r\n".
			"Content-Type: text/plain;\r\n".
			" charset=\"us-ascii\"\r\n".
			"Content-Transfer-Encoding: 7bit\r\n".
			"Content-Disposition: inline\r\n\r\n".
			"File for ".date(Ymd).".txt\r\n".
			"--".$outer_boundry."\r\n".
			"Content-Type: text/plain;\r\n".
			" charset=\"us-ascii\";\r\n".
			" name=\"sosl".date(Ymd)."\"\r\n".
			"Content-Transfer-Encoding: 7bit\r\n".
			"Content-Disposition: attachment; filename=\"sosl".date(Ymd).".txt\"\r\n\r\n".
			$line."\r\n".
			"--".$outer_boundry."--\r\n\r\n";

		// Send the file to ed for processing
		mail ("johnh@sellingsource.com", "sosl".date(Ymd, $yesterday).'.txt', NULL, $batch_headers);
		}

		$email_body = "Data file named :".$file_name."from ".$date1." and ".$date2." was sent successfully\n\nThe result of the file transfer is as follows:".$result."\nThe query to the DB was as follows:".$q;
		$to = 'Errors';
		$headers	=	"From: Steaks of St. Louis <info@steaksofstlouis.com>\r\n";
		$headers	.=	"Content-type: text/plain\r\n";
		$headers	.=	"X-mailer: PHP/";
		Mail('johnh@sellingsource.com', 'Data file sent', $email_body, $headers);
		// there were no orders
	}
	else
	{
		$email_body = 'there was no data file for the time'.$date1.' and '.$date2."\nThe query to the DB was as follows:".$q."\nTotal Number of Orders:".$total_number_orders;
		$to = $formdata['first_name'].' '.$formdata['last_name'].' <'.$formdata['email_address'].'>';
		$headers = "From: Steaks of St. Louis <info@steaksofstlouis.com>\r\n";
		$headers .= "Content-type: text/plain\r\n";
		$headers .= "X-mailer: PHP/";
		mail('johnh@sellingsource.com', 'no data', $email_body, $headers);
		// there were no orders
	}

	function FTP_Stuff($file_name)
	{
		$ftp_user_name = 'sellingsource';
		$ftp_user_pass = 'zzgreen02';
		$ftp_server = 'crownfoods.com';
		$destination_file = $file_name;
		$source_file = '/virtualhosts/cronjobs/www/data/'.$file_name;

		// set up basic connection
		$conn_id = ftp_connect($ftp_server);

		// login with username and password
		$login_result = ftp_login($conn_id, 'sellingsource', 'zzgreen02');

		// check connection
		if ((!$conn_id) || (!$login_result))
		{
			return 'Attempted to connect to '.$ftp_server.' for user '.$ftp_user_name;
		        die;
		}
		else
		{
		   //     return 'FTP upload has failed!';
		}

		// upload the file
		$upload = ftp_put($conn_id, $destination_file, $source_file, FTP_ASCII);

		// check upload status
		if (!$upload)
		{
			return 'FTP upload has failed!';
		}
		else
		{
			//return 'Uploaded '.$source_file.' to '.$ftp_server.' as '.$destination_file;
		}

		// close the FTP stream
		ftp_close($conn_id);
	}

	function Define_Site_Constants()
	{
		$q = "SELECT * FROM configure";
		$r = mysql_query($q);
		while ($data = mysql_fetch_object($r))
		{
			define(strtoupper($data->key), $data->value);
		}
	}

	function Get_Product_Code($product_id)
	{
		$q = "SELECT code FROM product WHERE product_id=".$product_id;
		$r = mysql_query($q) or die(mysql_error());
		$data = mysql_fetch_object($r);
		return $data->code;
	}

	function Clean_Object($object)
	{
		if (is_object($object))
		{
			foreach ($object as $field => $value)
			{
				$object->$field = str_replace(",", "_", $value);
			}
		}
		return $object;
	}
?>
