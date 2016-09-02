<?php
require_once '/virtualhosts/bfw.1.edataserver.com/include/code/event_log.singleton.class.php';
require_once 'import_pending.php';

if(isset($argv[1]))
{
	$mode = $argv[1];
}
else
{
	echo "You must pass the mode\n";
	exit(1);
}

// if property shorts are provided, limit the script to those
$props = isset($argv[2]) ? explode(',', $argv[2]) : array();

DEFINE('BFW_MODE',$mode);
//Load Failover Config
Failover_Config::RunConfig();

$import = new Import_Pending(BFW_MODE, $props);
$import->Run();

?>
