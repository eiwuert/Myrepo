<?php

//require_once ("server.cfg.php");
require_once('mysql.5.php');

ini_set ('session.use_cookies', 0);

$db_pre = preg_match('/^rc\./', $_SERVER['SERVER_NAME']) ? "rc_" : "";

if ($_REQUEST['id'])
{
	$sql = new MySQL_5('reader.olp.ept.tss', 'sellingsource', 'password', "${db_pre}olp");
	$tbl = 'session_'.strtolower(substr($_REQUEST['id'], 0, 1));
	$qry = "select * from $tbl where session_id = '".mysql_escape_string($_REQUEST['id'])."'";
	$res = $sql->Query($qry);
	$row = $res->Fetch_Object_Row();
	@session_start();
	if ($row->compression == 'gz')
	{
		$row->session_info = gzuncompress ($row->session_info);
	}
	session_decode ($row->session_info);
	echo "<pre>";
	echo htmlentities(print_r($_SESSION, TRUE));
}

?>
