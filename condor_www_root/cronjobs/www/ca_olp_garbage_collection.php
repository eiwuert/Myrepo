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
	define ("VISITOR_DB", "olp_ca_visitor");
	// Build the sql object
	$sql = new MySQL_3 ();

	// Try the connection
	$result = $sql->Connect ("BOTH", HOST, USER, PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	
	//############################################
	//kill visitor info and fully completed apps
	//############################################
	$query = "select min(modifed_date) as mindate from session_site";
	$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	$session = $sql->Fetch_Object_Row ($result);

	$day = $session->mindate;
	$stamp = mktime(0,0,0,substr($day,4,2),substr($day, 6,2),substr($day, 0, 4));
	while ($stamp < strtotime("-1 day") && $day)
	{
		// Pull the user information
		$query = "select * from `session_site` where modifed_date between '".date("YmdHis", $stamp)."' AND '".date("YmdHis", strtotime("+1 day", $stamp))."'";
		$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
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

			if (!$data["data"]["email_primary"] || $data["app_completed"])
			{
				$delete_array[] = $session->session_id;
			}

		}

		if(is_array ($delete_array))
		{
			foreach ($delete_array as $ses_id)
			{
				$query = "delete from `session_site` where session_id = '".$ses_id."'";
				$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test ($result, TRUE);
				//echo $query."\n";
			}
		}

		unset ($delete_array);
		echo date("m-d-Y", $stamp)."\n";
		$stamp = strtotime("+1 day", $stamp);
	}
	
	//exit;
	//fix below
	//############################################
	//kill visitor info and fully completed apps
	//############################################


	//################ collect partial data into the partial data database ############################
	$query = "select min(modifed_date) as mindate from session_site";
	$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	$session = $sql->Fetch_Object_Row ($result);

	$day = $session->mindate;
	$stamp = mktime(0,0,0,substr($day,4,2),substr($day, 6,2),substr($day, 0, 4));
	while ($stamp < strtotime("-1 month") && $day)
	{
		// Pull the user information
		$query = "select * from `session_site` where modifed_date between '".date("YmdHis", $stamp)."' AND '".date("YmdHis", strtotime("+1 day", $stamp))."'";
		$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		Error_2::Error_Test ($result, TRUE);
		echo $query."\n";
		$total_found = $sql->Row_Count($result);

		$c = 0;
		//foreach ($user_info as $user_data)
		@session_start();
		while ($session = $sql->Fetch_Object_Row ($result))
		{
			$_SESSION = array();
			session_decode ($session->session_info);
			include_once("ca_partial_insert.php");
			Partial_Insert_Database($sql);
			$delete_array[] = $session->session_id;
		}
		//exit;
		if(is_array ($delete_array))
		{
			foreach ($delete_array as $ses_id)
			{
				$query = "delete from `session_site` where session_id = '".$ses_id."'";
				$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
				Error_2::Error_Test ($result, TRUE);
				//echo $query."\n";
			}
		}

		unset ($delete_array);
		echo date("m-d-Y", $stamp)."\n";
		$stamp = strtotime("+1 day", $stamp);
	}

	$query = "OPTIMIZE TABLE `session_site`";
	echo $query."\n";
	$result = $sql->Query (VISITOR_DB, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);

?>
