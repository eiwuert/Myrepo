<?PHP
	// A file to handle server configuration

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

	// Connection information
	define ("HOST", "localhost");
	define ("USER", "root");
	define ("PASS", "");
	

	// Build the sql object
	$sql = new MySQL_3 ();	
	//exec("mysqldump -u sellingsource -p  --opt -Q -C -h ds001.ibm.tss -B sync_cashline_ucl | mysql");
	$cashline_database = array ("sync_cashline_ca", "sync_cashline_pcl", "sync_cashline_ucl");
/*
	exec('mysqldump -u sellingsource -p%selling\$_db  --opt -Q -C -h ds001.ibm.tss sync_cashline_ca cashline_customer_list| mysql sync_cashline_ca' );
	exec('mysqldump -u sellingsource -p%selling\$_db  --opt -Q -C -h ds001.ibm.tss sync_cashline_pcl cashline_customer_list| mysql sync_cashline_pcl' );
	exec('mysqldump -u sellingsource -p%selling\$_db  --opt -Q -C -h ds001.ibm.tss sync_cashline_ucl cashline_customer_list | mysql sync_cashline_ucl' );
*/		 
	$table_name = "cashline_customer_list".date("md_s", time());
	// Try the connection	
	$result = $sql->Connect ("BOTH", HOST, USER, PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	
	foreach ($cashline_database as $database)
	{
		exec('mysqldump -u sellingsource -p%selling\$_db  --opt -Q -C -h ds001.ibm.tss '.$database.' cashline_customer_list| mysql -uroot '.$database );
		$query = 'DROP TABLE IF EXISTS '.$table_name;	
		$result = $sql->Query ($database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		Error_2::Error_Test ($result, TRUE);	
		$query = "rename table cashline_customer_list to ".$table_name;
		$result = $sql->Query ($database, $query, Debug_1::Trace_Code (__FILE__, __LINE__));
		Error_2::Error_Test ($result, TRUE);
	}
	
?>