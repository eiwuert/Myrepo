<?PHP
	

	$table_name = "sitegrowth".date("YmdHis");
	$create_table_query ="CREATE TABLE `".$table_name."` (
	  `ID` int(10) unsigned NOT NULL auto_increment,
	  `date_modified` timestamp(14) NOT NULL,
	  `name` varchar(25) NOT NULL default '',
	  `count` int(16) unsigned NOT NULL default '0',
	  `delta` int(10) unsigned NOT NULL default '0',
	  PRIMARY KEY  (`ID`,`name`)
	) TYPE=MyISAM; ";


	$table_count = array();
	require_once ("/virtualhosts/lib/mysql.3.php");
	$local_sql = new MySQL_3 ();
	// Try the connection
	$result = $local_sql->Connect ("BOTH", "localhost", "root", "", Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);


	$table_count = array();
	$stat_base = "oledirect2";
	require_once ("/virtualhosts/lib/mysql.3.php");
	define ("HOST", "ds001.ibm.tss");
	define ("USER", "sellingsource");
	define ("PASS", 'password');
	$sql = new MySQL_3 ();
	// Try the connection
	$result = $sql->Connect ("BOTH", HOST, USER, PASS, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);
	$tbl_list = $local_sql->Get_Table_List ("ole");
	foreach ($tbl_list as $table => $status)
	{	
		$ctable = $table;
		echo $table."\n";
	}
	$query = "SELECT * FROM ".$ctable;
	echo $query."\n";
	$site_result =  $local_sql->Query ("ole", $query, Debug_1::Trace_Code (__FILE__, __LINE__));	
	Error_2::Error_Test ($result, TRUE);		
	while( $row = $sql->Fetch_Object_Row ($site_result))
	{
		$delta_index[$row->name] = $row->count;		
	}
	//Create new stat table
	$site_result =  $local_sql->Query ("ole", $create_table_query, Debug_1::Trace_Code (__FILE__, __LINE__));
	Error_2::Error_Test ($result, TRUE);		
	$tbl_list = $sql->Get_Table_List ($stat_base);	
	
	foreach ($tbl_list as $table => $status)
	{
		//echo $table;
		if (preg_match ('/^list_(\d+)/', $table, $match))
		{			
			$query = "SELECT count(*) as size FROM ".$match[0];
			$site_result =  $sql->Query ("oledirect2", $query, Debug_1::Trace_Code (__FILE__, __LINE__));
			Error_2::Error_Test ($result, TRUE);
			$person_index = $sql->Fetch_Object_Row ($site_result);
			
			$delta = $person_index->size - $delta_index[$match[0]];
			$query = "INSERT INTO $table_name SET name=\"$match[0]\", count=".$person_index->size.", delta=$delta";
			echo $query."\n";
			$site_result =  $local_sql->Query ("ole", $query, Debug_1::Trace_Code (__FILE__, __LINE__));	
			Error_2::Error_Test ($result, TRUE);	
			
			//if ($delta)
			{
				$query = "UPDATE lists SET last_count = $person_index->size, delta=$delta, visible=1 where ID=".$match[1];
				echo $query."\n";
				//$site_result =  $local_sql->Query ("oledirect2", $query, Debug_1::Trace_Code (__FILE__, __LINE__));	
				$site_result =  $sql->Query ("oledirect2", $query, Debug_1::Trace_Code (__FILE__, __LINE__));	
				Error_2::Error_Test ($result, TRUE);
			}	
		}
	}
	echo "Done..\n";

?>