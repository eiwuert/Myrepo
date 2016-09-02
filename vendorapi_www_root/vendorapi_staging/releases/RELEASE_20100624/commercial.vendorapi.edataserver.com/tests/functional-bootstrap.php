<?php

require './bootstrap.php';

$db = new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection(
	getTestPDODatabase(),
	$GLOBALS['db_name']
);

$dataset = new PHPUnit_Extensions_Database_DataSet_XmlDataSet('./Functional/_fixtures/'.$GLOBALS['enterprise'].'.xml');

// this is about 8 times faster
$op = new PHPUnit_Extensions_Database_Operation_Composite(array(
	new FastTruncate(),
	new LongInsert(),
));
$op->execute($db, $dataset);

unset($db, $dataset, $op);

?>