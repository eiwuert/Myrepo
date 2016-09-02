<?php
	// Process SSOL request to batch file for ed.


	// Make sure we keep running even if user aborts
	ignore_user_abort (TRUE);

	// Let it run forever
	set_time_limit (0);

	// Database connectivity
	include_once ("/virtualhosts/cronjobs/includes/load_balance.mysql.class.php");

	// Build our db object
	$ssol_sql = new MySQL ("read1.iwaynetworks.net", "write1.iwaynetworks.net", "sellingsource", "%selling\$_db", "expressgoldcard", 3306, "\t".__FILE__." -> ".__LINE__."\n");


	// Pull the unsent processes
	
	$this_line .= "Customer ID \t Transaction ID \t Modified Date \t  Send Batch Date \t Receive Batch Date \t Status \t Credit Card Number \t Reference ID \t Amount \t ACH Routing \t ACH Account \t Account Status \n";
	$query = "select cid, transaction.transaction_id, transaction.modified_date, transaction.send_batch_date, transaction.recieve_batch_date, transaction.transaction_status, transaction.cc_number, transaction.cross_reference_id, transaction.ach_amount, transaction.ach_routing_number, transaction.ach_account_number, account_status from transaction, orders, account_status WHERE transaction.transaction_status = 'PENDING' AND transaction_source = 'SSO' AND transaction_type = 'DOWN PAYMENT' and transaction.cc_number=orders.cc_number AND account_status.cc_number=orders.cc_number";
	$batch_info = $ssol_sql->Wrapper ($query, "", "\t".__FILE__." -> ".__LINE__."\n");
	foreach ($batch_info as $user)
	{
	$this_line .="\n";
		foreach ($user as $user_info => $value)
		{
			$this_line .= $value."\t";
		}
	$update_list .= "'".$user->transaction_id."', ";
	}

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
		"Leads for ".date ("Y-m-d")."\r\n".
		"--".$outer_boundry."\r\n".
		"Content-Type: text/plain;\r\n".
		" charset=\"us-ascii\";\r\n".
		" name=\"SmartShopperOnline - ".date ("md")."\"\r\n".
		"Content-Transfer-Encoding: 7bit\r\n".
		"Content-Disposition: attachment; filename=\"SmartShopperOnline - ".date ("md")."\"\r\n\r\n".
		$this_line."\r\n".
		"--".$outer_boundry."--\r\n\r\n";

	// Send the file to ed for processing
	mail ("ecross@41cash.com, crystal@fc500.com, scott@fc500.com", "SSOL batch file for: ".date ("m-d \a\\t H:i:s"), NULL, $batch_headers);
	mail ("rodricg@sellingsource.com", "SSOL batch file for: ".date ("Y-m-d \a\\t H:i:s"), NULL, $batch_headers);
	//mail ("nickw@sellingsource.com", "SSOL batch file for: ".date("Y-md \a\\t H:i:s"), NULL, $batch_headers);

	/* On hold per derek
	$batch_headers =
		"MIME-Version: 1.0\r\n".
		"Content-Type: Multipart/Mixed;\r\n boundary=\"".$outer_boundry."\"\r\n\r\n\r\n".
		"--".$outer_boundry."\r\n".
		"Content-Type: text/plain;\r\n".
		" charset=\"us-ascii\"\r\n".
		"Content-Transfer-Encoding: 7bit\r\n".
		"Content-Disposition: inline\r\n\r\n".
		"CC #'s for ".date ("Y-m-d")."\r\n".
		"--".$outer_boundry."\r\n".
		"Content-Type: text/plain;\r\n".
		" charset=\"us-ascii\";\r\n".
		" name=\"SmartShopperOnline - ".date ("md")."\"\r\n".
		"Content-Transfer-Encoding: 7bit\r\n".
		"Content-Disposition: attachment; filename=\"SmartShopperOnline - ".date ("md")."\"\r\n\r\n".
		$cc_file."\r\n".
		"--".$outer_boundry."--\r\n\r\n";

	// Send the file to mandy for cc printing
	mail ("mandyh@sellingsource.com", "New SmartShopperOnline Numbers", NULL, $batch_headers);
	*/

	// Update the db to tag as processed
	if (strlen ($update_list))
	{
		$query = "update transaction set transaction_status='SENT', send_batch_date = NOW() WHERE transaction_id in (".substr ($update_list, 0, -2).")";
		$ssol_sql->Wrapper ($query, "", "\t".__FILE__." -> ".__LINE__."\n");
	}

	// Mail the stats
	mail ("pauls@sellingsource.com, johnh@sellingsource.com, rodricg@sellingsource.com", "SSOL Stats", count($batch_info));
	//mail ("nickw@sellingsource.com", "SSOL Stats", count($batch_info));
?>
