<?php
	ini_set ('magic_quotes_runtime', 0);
	ini_set ('implicit_flush', 1);
	ini_set ('output_buffering', 0);
	ob_implicit_flush ();
	list ($ss, $sm) = explode (" ", microtime ());
	// Make sure we keep running even if user aborts
	ignore_user_abort (TRUE);
	
	// Let it run forever
	set_time_limit (0);
	
	// Database connectivity
	require_once ("/virtualhosts/lib/mysql.3.php");
	require_once ("/virtualhosts/lib/error.2.php");

	include_once ("/virtualhosts/lib/null_session.1.php");

	// Build the session handling object
	$session_plop = new Null_Session_1 ();
	
	// Set the session name
	session_name ("unique_id");
	
	// Establish the session parameters
	session_set_save_handler
	(
		array (&$session_plop, "Open"),
		array (&$session_plop, "Close"),
		array (&$session_plop, "Read"),
		array (&$session_plop, "Write"),
		array (&$session_plop, "Destroy"),
		array (&$session_plop, "Garbage_Collection")
	);

	// Connection information
//	define ("HOST", "ds03.tss");
//	define ("USER", "root");
//	define ("PASS", "");
	
	define ("HOST", "selsds001");
	define ("USER", "sellingsource");
	define ("PASS", "%selling\$_db");
	
	// Build the sql object
	$sql = new MySQL_3 ();
	
	// Try the connection
	$result = $sql->Connect ("BOTH", HOST, USER, PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	
//########################################################################
	
	// Pull the user information
	$query = "select * from `session` where modifed_date between '".date("YmdHis", strtotime("-2 hours"))."' AND '".date("YmdHis", strtotime("-1 hour"))."'";
	//$query = "select * from `session` where modifed_date between '".date("YmdHis", strtotime("-2 days"))."' AND '".date("YmdHis", strtotime("-1 days"))."'";
	$result = $sql->Query ("olp_ca_visitor", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	
	$total_found = $sql->Row_Count($result);
	$c = 0;
	//foreach ($user_info as $user_data)
	@session_start();
	while ($session = $sql->Fetch_Object_Row ($result))
	{
		$_SESSION = array();
		
		session_decode ($session->session_info);
		$data = $_SESSION;
		
		if($data["already_inserted"])
		{
			echo "poop";
			exit;
		}
		else
		{
			echo $session->session_id."\n";
		}
		
		//if (!$data["personal"]["email"])
		//{
		//	$delete_array[] = $session->session_id;
		//l}
	}
?>
