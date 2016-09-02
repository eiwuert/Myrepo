<?php

if(!empty($_GET['module']))
{
	require_once(strtolower($_GET['module']) . ".class.php");
}
else
{
	require_once("default.class.php");
}

$server_status = new Server_Status();
if($server_status->Run_Tests())
{
	echo "PASS";
}
else
{
	echo "FAIL";
}

?>
