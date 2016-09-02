<?php

	// Set the include path to have the libraries available
	ini_set ("include_path", "/virtualhosts/lib/");
	ini_set ("session.use_cookies", "1");
	ini_set ("magic_quotes_runtime", "0");
	ini_set ("magic_quotes_gpc", "1");

	// We need to include the some libs
	require_once ("library.1.php");
	require_once ("error.2.php");

	$lib_path = Library_1::Get_Library ("debug", 1, 0);
	Error_2::Error_Test ($lib_path);
	require_once ($lib_path);

	$lib_path = Library_1::Get_Library ("mysql", 3, 0);
	Error_2::Error_Test ($lib_path);
	require_once ($lib_path);

///	if (!is_null($debug_email))
///	{
///		// RC Connection information
///		define ("HOST","db101.clkonline.com");
///		define ("USER","sellingsource");
///		define ("PASS","%selling\$_db");
///		define ("SEND_LIMIT", 0); // 0 = no limit
///	}
///	else
///	{
		// Live Connection information
		define ("HOST","db100.clkonline.com");
		define ("USER","sellingsource");
		define ("PASS","%selling\$_db");
		define ("SEND_LIMIT", 0); // 0 = no limit
///	}

	// Build the sql object
	$react_sql = new MySQL_3 ();	
	$database = "react_db";

	$result = $react_sql->Connect ("BOTH", HOST, USER, PASS, Debug_1::Trace_Code (__FILE__, __LINE__));

	$q = "SELECT * FROM send_list WHERE send_status='not_sent' ORDER BY id";
	$r = $react_sql->Query($database, $q, Debug_1::Trace_Code (__FILE__, __LINE__));
	if (!$react_sql->Row_Count($r)>0)
	{
		echo "No records to send out!!!\n";
	}

	$host_names = array();

	while ($row = $react_sql->Fetch_Array_Row($r))
	{
		$parts = explode('@', $row['email_primary']);
		$hostname = strtolower($parts[1]);
		if (!isset($host_names[$hostname]))
		{
			$host_names[$hostname] = 0;
		}
		$host_names[$hostname]++;
	}

	//$list = array();
	//foreach ($host_names as $hostname=>$value)
	//{
	//	if ($value>2)
	//	{
	//		$list[$hostname]=$value;
	//	}
	//}
	asort($host_names);
	print_r($host_names);
	echo "Total: ".$react_sql->Row_Count($r)."\n";

?>
