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
	//$egc_sql = new MySQL ("alpha.tss", "alpha.tss", "root", "", "expressgoldcard", 3306, "\t".__FILE__." -> ".__LINE__."\n");
	$egc_sql = new MySQL ("localhost", "localhost", "root", "", "expressgoldcard", 3306, "\t".__FILE__." -> ".__LINE__."\n");

	// Get the list of existing cc_numbers for faster comparison
	
	// Pull the unsent processes
	$query = "select * from transaction where transaction_source='EGC'  ";
	$egc_info = $egc_sql->Wrapper ($query, "", "\t".__FILE__." -> ".__LINE__."\n");

  

	$egcrows=0;
	$ssorows=0;


	foreach ($egc_info as $transaction)
	{

	//   echo "<br><pre>cc_number:-"; print_r($transaction); echo "</pre>";
	   
	   if ($transaction->transaction_status == 'SENT')
	   {
	   $update_query = "update account set account_status  = 'PENDING'  where  cc_number= '".$transaction->cc_number."' ";
	   }
	   else  if ($transaction->transaction_status == 'APPROVED')
	   {
	   $update_query = "update account set account_status  = 'INACTIVE'  where  cc_number = '".$transaction->cc_number."' ";
	   }
	   else if ($transaction->transaction_status == 'DENIED')
	   {
	   $update_query = "update account set account_status  = 'DENIED'  where  cc_number = '".$transaction->cc_number."' ";
	   }
	   else 
	   {
	   echo " transaction status for EGC got problem for cc_number '".$transaction->cc_number."' ";
	   exit;
	   }
	   $egcrows++;
	   echo "<br> $update_query";
	   $egc_sql->Wrapper ($update_query, "", "\t".__FILE__." -> ".__LINE__."\n");

	}
	

	
	$query = "select * from transaction where transaction_source='SSO'  ";
	$sso_info = $egc_sql->Wrapper ($query, "", "\t".__FILE__." -> ".__LINE__."\n");

  


	foreach ($sso_info as $transaction)
	{

	//   echo "<br><pre>cc_number:-"; print_r($transaction); echo "</pre>";
	   
	   if (($transaction->transaction_status == 'SENT') || ($transaction->transaction_status == 'PENDING') || ($transaction->transaction_status == 'APPROVED'))
	   {
	   $update_query = "update account set account_status  = 'ACTIVE'  where  cc_number= '".$transaction->cc_number."' ";
	   }
	   else if ($transaction->transaction_status == 'DENIED')
	   {
	   $update_query = "update account set account_status  = 'INACTIVE'  where  cc_number = '".$transaction->cc_number."' ";
	   }
	   else 
	   {
	   echo " transaction status for SSO got problem for cc_number '".$transaction->cc_number."' ";
	   exit;
	   }
	   $ssorows++;
	   echo "<br> $update_query";
	   $egc_sql->Wrapper ($update_query, "", "\t".__FILE__." -> ".__LINE__."\n");

	}
	

	$query = "select * from account_status  where account_status='WITHDRAWN'  ";
	$account_info = $egc_sql->Wrapper ($query, "", "\t".__FILE__." -> ".__LINE__."\n");

  


	foreach ($account_info as $account)
	{

	//   echo "<br><pre>cc_number:-"; print_r($transaction); echo "</pre>";
	   
	   if ($account->account_status == 'WITHDRAWN')
	   {
	   $update_query = "update account set account_status  = 'WITHDRAWN'  where  cc_number= '".$account->cc_number."' ";
	   $egc_sql->Wrapper ($update_query, "", "\t".__FILE__." -> ".__LINE__."\n");
	   }
	  

	}
	
	
	echo "<br>EGC rows $egcrows | SSO rows $ssorows";
	
	
	
	
	
	
	 






?>