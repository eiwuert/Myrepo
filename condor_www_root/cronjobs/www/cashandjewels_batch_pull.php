<?php

	// Make sure we keep running even if user aborts
	ignore_user_abort (TRUE);

	// Let it run forever
	set_time_limit (0);

	// Database connectivity
	include_once ("/virtualhosts/cronjobs/includes/load_balance.mysql.class.php");

	// Build our db object
	$cashnjewels_sql = new MySQL ("read1.iwaynetworks.net", "write1.iwaynetworks.net", "sellingsource", "%selling\$_db", "cashandjewels_com", 3306, "\t".__FILE__." -> ".__LINE__."\n");

	$this_line .= "Customer ID \t Order Date \t Order Time \t  IP Address \t Offer Name \t First Name \t Last Name \t Email \t Phone \t Quanitity \t Form ID \t Status \t Billing Address \t Billing Address 2 \t City \t State \t Zip \t Account Number \t Routing Number \t Check Number \t Bank Name \t Ship To Name \t Shipping Address \t Shipping Address 2 \t Shipping City \t Shipping State \t Shipping Zip \n";
	// Pull the unsent processes
	$query = "SELECT * FROM personal_information, checking_information, shipping_information
			WHERE personal_information.status = 'PENDING' AND
			personal_information.customer_id=shipping_information.customer_id AND
			personal_information.customer_id=checking_information.customer_id";

	$batch_info = $cashnjewels_sql->Wrapper ($query, "", "\t".__FILE__." -> ".__LINE__."\n");
	foreach ($batch_info as $user)
	{
	$this_line .="\n";
		foreach ($user as $user_info => $value)
		{

		$this_line .= $value."\t";
		}
	
	$update_list .= "'".$user->customer_id."', ";
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
		" name=\"Cash And Jewels - ".date ("md")."\"\r\n".
		"Content-Transfer-Encoding: 7bit\r\n".
		"Content-Disposition: attachment; filename=\"Cash And Jewels - ".date ("md")."\"\r\n\r\n".
		$this_line."\r\n".
		"--".$outer_boundry."--\r\n\r\n";

	// Send the file to ed for processing
	//mail ("ecross@41cash.com, crystal@fc500.com, scott@fc500.com", "Cash And Jewels batch file for: ".date ("m-d \a\\t H:i:s"), NULL, $batch_headers);
	//mail ("pauls@sellingsource.com", "SSOL batch file for: ".date ("Y-m-d \a\\t H:i:s"), NULL, $batch_headers);
	mail ("ecross@41cash.com, crystal@fc500.com, scott@fc500.com", "Cash And Jewels batch file for: ".date ("Y-m-d \a\\t H:i:s"), NULL, $batch_headers);


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
	$query = "update personal_information set status='SENT' where customer_id in (".substr ($update_list, 0, -2).")";
	$cashnjewels_sql->Wrapper ($query, "", "\t".__FILE__." -> ".__LINE__."\n");

	// Mail the stats
	mail ("pauls@sellingsource.com, johnh@sellingsource.com", "Cash And Jewels Stats", count($batch_info));
?>
