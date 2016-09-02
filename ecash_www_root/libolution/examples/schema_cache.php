<?php

	require_once 'libolution/AutoLoad.1.php';

	$db = new DB_MySQLConfig_1('localhost', 'root', '');
	$schema = new DB_Util_SchemaCache_1($db->getConnection());

	if ($schema->tableExists('test', 'mytable'))
	{
		echo "test:mytable exists!\n";
	}
	else
	{
		echo "test:mytable does not exist!\n";
	}

	if ($schema->tableExists('information_schema', 'COLUMNS'))
	{
		echo "information_schema:COLUMNS exists!\n";
	}
	else
	{
		echo "information_schema:COLUMNS does not exist!\n";
	}


?>
