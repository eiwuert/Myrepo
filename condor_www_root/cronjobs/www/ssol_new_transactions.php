<?php
	// Process SSOL request to batch file for ed.


	// Make sure we keep running even if user aborts
	ignore_user_abort (TRUE);
	
	
	// Database connectivity
	include_once ("/virtualhosts/cronjobs/includes/load_balance.mysql.class.php");

	// Build our db object
	$ssol_sql = new MySQL (NULL, "localhost", "root", "", "expressgoldcard", 3306, "\t".__FILE__." -> ".__LINE__."\n");
	//$ssol_sql = new MySQL ("read1.iwaynetworks.net", "write1.iwaynetworks.net", "sellingsource", "%selling\$_db", "expressgoldcard", 3306, "\t".__FILE__." -> ".__LINE__."\n");


	// Pull the unsent processes
		
	$file_result = "Credit Card Number,Customer Name,Transaction ID,Check Number,Check Date,Check Amount,ABA Routing Number,Account Number\n";
	  
	$query = "select * from transaction_status, processed_status  WHERE transaction_status.status = 'PENDING'  and processed_status.cc_number = transaction_status.cc_number " ;
	
	$batch_info = $ssol_sql->Wrapper ($query, "", "\t".__FILE__." -> ".__LINE__."\n");
 	
	

	foreach ($batch_info as $user)
	{
	  $orders_query = "select * from orders where cid = '".$user->cid."' ";
	  $orders_info = $ssol_sql->First_Row ($orders_query, "", "\t".__FILE__." -> ".__LINE__."\n");
	  
	  $file_result .= "\t".$user->cc_number.",".$orders_info->first_name." ".$orders_info->last_name.",".$user->transaction_id.",606,".date("m-d-Y").",".$user->amount.",".$user->ach_routing.",".$user->ach_account."\n";
	
	
	}
	
	echo "<pre>test";
   echo $file_result;
   echo "</pre>";


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
		"Content-Disposition: attachment; filename=\"SmartShopperOnline-".date ("md").".csv\"\r\n\r\n".
		$file_result."\r\n".
		"--".$outer_boundry."--\r\n\r\n";

	// Send the file to ed for processing
//	mail ("ecross@41cash.com, crystal@fc500.com, scott@fc500.com", "SSOL batch file for: ".date ("m-d \a\\t H:i:s"), NULL, $batch_headers);
	mail ("sain@sellingsource.com", "SSOL New transactions: ".date ("Y-m-d \a\\t H:i:s"), NULL, $batch_headers);
	//mail ("nickw@sellingsource.com", "SSOL batch file for: ".date("Y-md \a\\t H:i:s"), NULL, $batch_headers);
?>
