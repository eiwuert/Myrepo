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
	$egc_sql = new MySQL ("localhost", "localhost", "root", "", "expressgoldcard", 3306, "\t".__FILE__." -> ".__LINE__."\n");
	//$egc_sql = new MySQL ("read1.iwaynetworks.net", "write1.iwaynetworks.net", "sellingsource", "%selling\$_db", "expressgoldcard", 3306, "\t".__FILE__." -> ".__LINE__."\n");

	// Get the list of existing cc_numbers for faster comparison
	$query = "select cc_number from processed_status";
	$cc_number_info = $egc_sql->Wrapper ($query, "", "\t".__FILE__." -> ".__LINE__."\n");

	foreach ($cc_number_info as $cc_number)
	{
		$list->$cc_number = 1;
	}

	// Pull the unsent processes
	$query = "select * from orders where routing_number != '' and first_name != '' and last_name != '' and processed='FALSE'";
	$batch_info = $egc_sql->Wrapper ($query, "", "\t".__FILE__." -> ".__LINE__."\n");

	foreach ($batch_info as $user_info)
	{
		// validate the routing number
		if (Validate_Routing_Number ($user_info->routing_number))
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
					$this_line .= $value."\t";
				}
			}

			$batch_file .= substr ($this_line, 0, -1)."\n";
			
			// Push the number into the db with the user
			
			// Clean up the mess
			unset ($this_line);
		}
		else
		{
			$fail_list .= "'".$user_info->cid."', ";
		}
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
		"Content-Disposition: attachment; filename=\"ExpressGoldCard - ".date ("md")."\"\r\n\r\n".
		$batch_file."\r\n".
		"--".$outer_boundry."--\r\n\r\n";

	// Send the file to ed for processing
	mail ("sain@sellingsource.com", "EGC batch file for: ".date ("m-d \a\\t H:i:s"), NULL, $batch_headers);
	//mail ("rodricg@sellingsource.com", "EGC batch file for: ".date ("Y-m-d \a\\t H:i:s"), NULL, $batch_headers);



	function Validate_Routing_Number ($routing_number)
	{
		$size = strlen ($routing_number);

		for ($pos = 0; $pos < $size; $pos++)
		{
			$number = (int) substr ($routing_number, $pos, 1);
			switch ($pos)
			{
				case 0:
				case 3:
				case 6:
					$total += ($number *3);
					break;

				case 1:
				case 4:
				case 7:
					$total += ($number *7);
					break;

				case 2:
				case 5:
					$total += ($number *1);
					break;

				case 8:
					$check_sum = $number;
					break;
			}
		}

		if (($check_sum + $total)%10)
		{
			// The number is not valid
			return FALSE;
		}

		return TRUE;
	}


?>
