<?php

require_once ("db2.1.php");

$db2 = new Db2_1 ("SAMPLE", "db2inst1", "db2");
Error_2::Error_Test ($db2->Connect (), TRUE);


// Define queries
$qry_all = "select * from staff fetch first 2 row only";
$qry_select = "select * from staff where id = ?";
$qry_delete = "delete from staff where id = ?";
$qry_insert = "insert into staff (id, name, dept, job, years, salary, comm) values (?, ?, ?, ?, ?, ?, ?)";


// Prepare queries
$sql_select = $db2->Query ($qry_select);
Error_2::Error_Test ($sql_select, TRUE);

$sql_delete = $db2->Query ($qry_delete);
Error_2::Error_Test ($sql_delete, TRUE);

$sql_insert = $db2->Query ($qry_insert);
Error_2::Error_Test ($sql_insert, TRUE);


// Execute queries
echo "Executing select...";
$res_all = $db2->Execute ($qry_all);
Error_2::Error_Test ($res_all, TRUE);
echo "OK - Num rows is ".$res_all->Num_Rows ()."\n";
while ($row = $res_all->Fetch_Object ())
{
	print_r ($row);
}
echo "\n";

echo "Inserting data....";
Error_2::Error_Test (
	$sql_insert->Execute (24, "Bob", 42, "Farm", 112, 99.9, 1.1), TRUE
);
echo "OK - Num rows is ".$sql_insert->Num_Rows ()."\n\n";

echo "Insert Id....";
echo $db2->Insert_Id (), "\n\n";

echo "Selecting inserted data...";
Error_2::Error_Test (
	$sql_select->Execute (24), TRUE
);
echo "OK - Num rows is ".$sql_select->Num_Rows ()."\n";
while ($row = $sql_select->Fetch_Object ())
{
	print_r ($row);
}
echo "\n";

echo "Deleting inserted data...";
Error_2::Error_Test (
	$sql_delete->Execute (24), TRUE
);
echo "OK - Num rows is ".$sql_delete->Num_Rows ()."\n\n";

?>