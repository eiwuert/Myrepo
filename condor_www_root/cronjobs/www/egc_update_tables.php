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
	$egc_sql = new MySQL ("alpha.tss", "alpha.tss", "root", "", "expressgoldcard", 3306, "\t".__FILE__." -> ".__LINE__."\n");
	// Get the list of existing cc_numbers for faster comparison
	
	// Pull the unsent processes
	$query = "select processed_status.cc_number,processed_status.cid from  processed_status , orders  where orders.cc_number = 0 and  processed_status.cid = orders.cid ";
	$cc_info = $egc_sql->Wrapper ($query, "", "\t".__FILE__." -> ".__LINE__."\n");

  
    echo "$query <br><pre>userinfo:-"; print_r($cc_info); echo "</pre>";
	

	$outer_boundry = md5 ("Outer Boundry");
	$inner_boundry = md5 ("Inner Boundry");
	

	foreach ($cc_info as $cc_number)
	{

	   echo "<br><pre>cc_number:-"; print_r($cc_number); echo "</pre>";
	   $update_query = "update orders set cc_number = '".$cc_number->cc_number."'  where  cid = '".$cc_number->cid."' ";
	   echo "<br> $update_query";
	   $egc_sql->Wrapper ($update_query, "", "\t".__FILE__." -> ".__LINE__."\n");

	}
	
	/*!  Updated Orders tahble */
	
	
	
	$query = "select processed_status.cc_number, '7500' as credit_limit ,'7500'as available_balance, 'UNKNOWN' as status   from processed_status left join account_status ON  processed_status.cc_number = account_status.cc_number where account_status IS  NULL";
	
	$account_status_info = $egc_sql->Wrapper ($query, "", "\t".__FILE__." -> ".__LINE__."\n");

  
    echo "$query <br><pre>userinfo:-"; print_r($account_status_info); echo "</pre>";
	
	foreach ($account_status_info as $account_status)
	{

	   echo "<br><pre>cc_number:-"; print_r($account_status); echo "</pre>";
	   $insert_query = "insert into account_status(cc_number, credit_limit, available_balance, account_status) values('".$account_status->cc_number."','".$account_status->credit_limit."', '".$account_status->available_balance."', '".$account_status->status."')";
	   echo "<br> $insert_query";
	   $egc_sql->Wrapper ($insert_query, "", "\t".__FILE__." -> ".__LINE__."\n");
       
	}
	
	
	
	
	 






?>