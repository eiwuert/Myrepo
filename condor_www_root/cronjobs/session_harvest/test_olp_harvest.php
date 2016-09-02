<?php

# this says "test" but it really does exactly what we want, so it's the real deal.
# might want to put some stuff in so testing locally works, but this'll do for now

require_once("diag.1.php");
require_once("mysql.3.php");
require_once("lib.olp_session_harvest.php");

$sql = new MySQL_3();
$sql->Connect(
	"BOTH",
	"selsds001",
	"sellingsource",
	"%selling\$_db",
	Debug_1::Trace_Code(__FILE__, __LINE__)
);

$db_to = "olp_session_harvest";

$o = new OLP_Harvest($sql);

Diag::Dump($o, "o");

#$o->Harvest("olp_d1_visitor", $db_to);
//$o->Harvest("olp_pcl_visitor", $db_to);
//$o->Harvest("olp_ucl_visitor", $db_to);
//$o->Harvest("olp_ca_visitor", $db_to);
//$o->Harvest("olp_bb_visitor", $db_to);
$o->Harvest("olp", $db_to);

?>
