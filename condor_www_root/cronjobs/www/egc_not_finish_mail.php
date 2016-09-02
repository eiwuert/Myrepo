<?php
	// Process EGC mail for un finished applications 


	// Benchmarking
	list ($ss, $sm) = explode (" ", microtime ());

	// Make sure we keep running even if user aborts
	ignore_user_abort (TRUE);

	// Let it run forever
	set_time_limit (0);

	// Database connectivity
	include_once ("/virtualhosts/cronjobs/includes/load_balance.mysql.class.php");

	// Build our db object
//	$egc_sql = new MySQL ("read1.iwaynetworks.net", "write1.iwaynetworks.net", "sellingsource", "%selling\$_db", "expressgoldcard", 3306, "\t".__FILE__." -> ".__LINE__."\n");
	$egc_sql = new MySQL ("localhost", "localhost", "root", "", "expressgoldcard", 3306, "\t".__FILE__." -> ".__LINE__."\n");
	// Get the list of existing cc_numbers for faster comparison
	
	// Pull the unsent processes
	$query = "select * from orders where routing_number = '' and unique_id != '' and processed='FALSE'";
	$users_info = $egc_sql->Wrapper ($query, "", "\t".__FILE__." -> ".__LINE__."\n");

    echo "$query <br><pre>userinfo:-"; print_r($users_info); echo "</pre>";
	

	$outer_boundry = md5 ("Outer Boundry");
	$inner_boundry = md5 ("Inner Boundry");
	

	foreach ($users_info as $user_info)
	{

     $mail_body = "Dear $user_info->last_name    <a href=http://egc.expressgoldcard.com.ds06.tss/goto_step.php?id=$user_info->unique_id> Click Here </a> ";

	 echo $mail_body;
	 
	 unset($mail_headers);
	$mail_headers =
		"MIME-Version: 1.0\r\n".
		"Content-Type: Multipart/Mixed;\r\n boundary=\"".$outer_boundry."\"\r\n\r\n\r\n".
		"--".$outer_boundry."\r\n".
		"Content-Type: text/html;\r\n".
		" charset=\"us-ascii\"\r\n".
		"Content-Transfer-Encoding: 7bit\r\n".
		"Content-Disposition: inline\r\n\r\n".
		"$mail_body\r\n".
		"--".$outer_boundry."--\r\n\r\n";
		
     mail ("sain@sellingsource.com", "EGC batch file for: ".date ("m-d \a\\t H:i:s"), "Null", $mail_headers);
	}






?>