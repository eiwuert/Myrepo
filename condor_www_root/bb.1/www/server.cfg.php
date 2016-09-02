<?php
	/*
	* vim:ts=4
	*/

	// Set the include path to have the libraries available
	//ini_set ('include_path', '.:/virtualhosts/lib:/usr/share/pear');
	ini_set ('magic_quotes_runtime', '0');
	ini_set ('magic_quotes_gpc', '1');

	// We need to include the some libs
	require_once ('library.1.php');
	require_once ('error.2.php');

	$lib_path = Library_1::Get_Library ("debug", 1, 0);
	Error_2::Error_Test ($lib_path);
	require_once ($lib_path);

	$lib_path = Library_1::Get_Library ("mysql", 3, 2);
	Error_2::Error_Test ($lib_path);
	require_once ($lib_path);

	// Connection information
	define ('_CFG_MASTER_HOST', 'db100.clkonline.com');
	define ('_CFG_MASTER_USER', 'sellingsource');
	define ('_CFG_MASTER_PASS', 'password');
	
	define ('_CFG_SLAVE_HOST', 'db100.clkonline.com');
	define ('_CFG_SLAVE_USER', 'sellingsource');
	define ('_CFG_SLAVE_PASS', 'password');
	
	define ('_CFG_BASE', 'config_ibm');
	
	// Build the sql object
	$sql = new MySQL_3 ();

	// Try the connection
	$result = $sql->Connect ('BOTH', _CFG_MASTER_HOST, _CFG_MASTER_USER, _CFG_MASTER_PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);

	$result = $sql->Connect ('READ', _CFG_SLAVE_HOST, _CFG_SLAVE_USER, _CFG_SLAVE_PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result);

	// Get the config info
	$query = "select * from config_map where url='".$_SERVER ['SERVER_NAME'].':'.preg_replace ('/\/[^\/]*($|\?.*)/s', '/', $_SERVER ['REQUEST_URI'])."'";
	//$query = "select * from config_map where url='".$_SERVER ['SERVER_NAME'].':'.preg_replace ('/^[^\?]+\/[^\/]*$/', '/', $_SERVER ['REQUEST_URI'])."'";
	$result = $sql->Query (_CFG_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	$server_data = $sql->Fetch_Object_Row ($result);

	// Pull the server config data from the db
	$query = 'select * from config_server order by priority';
	$server_result = $sql->Query (_CFG_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($server_result, TRUE);

	// Pull the site config data from the db
	if ($server_data->config_table)
	{
		$query = 'select * from '.$server_data->config_table.' order by priority';
		$site_result = $sql->Query (_CFG_BASE, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		Error_2::Error_Test ($site_result, TRUE);

		// Define the site constants. We do them first to override the server constants if needed
		while ($temp = $sql->Fetch_Object_Row ($site_result))
   		{
			eval ('define ("'.$temp->constant.'", '.$temp->value.');');
		}
	}
	
	// Define the server constants
	while ($temp = $sql->Fetch_Object_Row ($server_result))
    {
		if (! defined ($temp->constant))
		{
			eval ('define ("'.$temp->constant.'", '.$temp->value.');');
		}
    }
?>
