<?php
	// Process EGC request to batch file for ed.
	/*
		Data Format
		??, Routing#, Acct#, Amount, Id Number, Name
		"XX","XXXXXXXXX","XXXXXXXXXXXX","XX.XX", "XXXXXX", "LLL, FFF"
	*/

	// Benchmarking
	list ($ss, $sm) = explode (" ", microtime ());

	// Seed the random number generator
	$hash = md5 (microtime());
	$sub_length = ((substr ($hash, 0, 1) < 8) ? 8 : 7 );
	$seed = base_convert (substr ($hash, 0, $sub_length), 16, 10);
	mt_srand ($seed);

	// Make sure we keep running even if user aborts
	ignore_user_abort (TRUE);

	// Let it run forever
	set_time_limit (0);

	// Database connectivity
	include_once ("/virtualhosts/cronjobs/includes/load_balance.mysql.class.php");

	// Build our db object
	$egc_sql = new MySQL ("read1.iwaynetworks.net", "write1.iwaynetworks.net", "sellingsource", "%selling\$_db", "expressgoldcard", 3306, "\t".__FILE__." -> ".__LINE__."\n");
//	$egc_sql = new MySQL ("localhost", "localhost", "root", "", "expressgoldcard", 3306, "\t".__FILE__." -> ".__LINE__."\n");
	
	$this_line = ""; 
	
	// Pull the unsent processes
	$query = "select orders.*, transaction_id from transaction , orders where transaction_type = 'ENROLLMENT' and transaction_status = 'PENDING' and orders.cc_number = transaction.cc_number";
	
	$batch_info = $egc_sql->Wrapper ($query, "", "\t".__FILE__." -> ".__LINE__."\n");



	foreach ($batch_info as $user_info)
	{
		
			// Add each column to the data string
			
				foreach ($user_info as $column => $value)
			{
				switch ($column)
				{
					case "routing_number":
					case "acctno":
					case "homephone":
					case "workphone":
					$value = preg_replace ("/[^\d]/", "", $value);
				
						break;
				}
				
				if ($column != "cc_number")
				{
					$this_line .= $value.",";
				}
				
			}
			
	        
	 
            $batch_file .= substr ($this_line, 0, -1)."\n";
			$update_account_list .= "'".$user_info->cc_number."', ";
			$update_transaction_list .= "'".$user_info->transaction_id."', ";
	
			// Push the number into the db with the user
			//(origination_date, cc_number, ach_amount, ach_routing_number, ach_account_number, transaction_type, transaction_source)
	    //	$processed_list .= "(NOW(), '".$cc_number."', 149.00, '".$user_info->routing_number."', '".$user_info->acctno."', 'ENROLLMENT', 'EGC', '".$user_info->ref."'), ";
		//	$acccount_list .= "('".$cc_number."', 7500, 7500, 'HOLD', '".$user_info->routing_number."', '".$user_info->acctno."'), ";
		//	$cc_file = $cc_number.",".strtoupper ($user_info->first_name." ".$user_info->last_name)."\n";

			// Clean up the mess
			unset ($this_line);
		
	}



	if (strlen ($update_transaction_list))
	{
		$query = "update transaction  set transaction_status = 'SENT' , send_batch_date = NOW()  where transaction_id in (".substr ($update_transaction_list, 0, -2).")";
		$egc_sql->Wrapper ($query, "", "\t".__FILE__." -> ".__LINE__."\n");
		//echo $query."<br>";
    }

	if (strlen ($update_account_list))

	{
		$query = "update account  set account_status = 'PENDING' where cc_number in (".substr ($update_account_list, 0, -2).")";
		$egc_sql->Wrapper ($query, "", "\t".__FILE__." -> ".__LINE__."\n");
		//echo $query."<br>";
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
		" name=\"ExpressGoldCard - ".date ("md")."\"\r\n".
		"Content-Transfer-Encoding: 7bit\r\n".
		"Content-Disposition: attachment; filename=\"ExpressGoldCard-".date ("md").".csv\"\r\n\r\n".
		$batch_file."\r\n".
		"--".$outer_boundry."--\r\n\r\n";

	// Send the file to ed for processing
	mail ("ecross@41cash.com, crystal@fc500.com, scott@fc500.com", "EGC batch file for: ".date ("m-d \a\\t H:i:s"), NULL, $batch_headers);
	mail ("rodricg@sellingsource.com", "EGC batch file for: ".date ("Y-m-d \a\\t H:i:s"), NULL, $batch_headers);
	mail ("sain@sellingsource.com", "EGC batch file for: ".date ("Y-m-d \a\\t H:i:s"), NULL, $batch_headers);

	/*On hold per derek
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
		" name=\"ExpressGoldCard - ".date ("md")."\"\r\n".
		"Content-Transfer-Encoding: 7bit\r\n".
		"Content-Disposition: attachment; filename=\"ExpressGoldCard - ".date ("md")."\"\r\n\r\n".
		$cc_file."\r\n".
		"--".$outer_boundry."--\r\n\r\n";

	// Send the file to mandy for cc printing
	mail ("mandyh@sellingsource.com", "New Express Gold Card Numbers", NULL, $batch_headers);
	*/

	
	// Mail the stats
	mail ("pauls@sellingsource.com, johnh@sellingsource.com, rodricg@sellingsource.com", "EGC Batch Pull Stats", count($batch_info));

	
?>
