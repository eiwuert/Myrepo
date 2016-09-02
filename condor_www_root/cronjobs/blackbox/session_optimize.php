#!/usr/lib/php5/bin/php
<?php
	
	require('mysql.4.php');
	
	define('DEBUG', FALSE);
	define('BFW_CODE_DIR', '/virtualhosts/bfw.1.edataserver.com/include/code/');
	
	require(BFW_CODE_DIR.'setup_db.php');
	
	// get a connection to the slave
	$sql = Setup_DB::Get_Instance('BLACKBOX', 'SLAVE');
	
	// table extensions
	$extensions = array_merge(range('a', 'f'), range(0, 9));
	
	foreach ($extensions as $ext)
	{
		$sql->Query($sql->db_info['db'], 'OPTIMIZE TABLE session_'.$ext);
	}
	
	unset($sql);
	
?>
