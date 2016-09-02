<?php

require_once("/virtualhosts/lib/lib_mode.1.php");
require_once("/virtualhosts/lib/debug.1.php");
require_once("/virtualhosts/lib/error.2.php");
require_once("/virtualhosts/lib/mysql.3.php");
require_once("/virtualhosts/lib/diag.1.php");

define('MYSQL_ERR_NONE',			0);
define('MYSQL_ERR_DUP_ENTRY',		1062);
define('MYSQL_ERR_LOST_CONN',		2013);

// be able to handle commandline or web-based environments
$HOST = $_SERVER["HTTP_HOST"] ? $_SERVER["HTTP_HOST"] : $_ENV["HOSTNAME"];

// management configuration for master vendor data
define("DB_MGMT_HOST",	"nightwing.tss");
define("DB_MGMT_USER",	"sellingsource");
define("DB_MGMT_PASS",	"%selling\$_db");
define("DB_MGMT_NAME",	"mgmt");

// set our database vars based on where we are
switch (Lib_Mode::Get_Mode())
{
	case MODE_LIVE:
	case MODE_RC:
	case MODE_LOCAL:
	// on local
		define("DB_HOST",	"riddler.tss");
		define("DB_USER",	"sellingsource");
		define("DB_PASS",	"%selling\$_db");
		define("DB_NAME",	"scrubber");
		break;

	//case (preg_match ("/^rc\..*/", $HOST)): // The rc server
	default: // It must be live
	//FIXME: this doesn't exist yet
		define("DB_HOST",	"");
		define("DB_USER",	"");
		define("DB_PASS",	"");
		define("DB_NAME",	"scrubber");
		break;
}

Diag::Out("DB_HOST:" . DB_HOST . ", DB_USER:" . DB_USER . ", DB_PASS:" . DB_PASS . "\n");

// default connection to scrubber database
$sql = new MySQL_3();
$conn = $sql->Connect(
	NULL
	,DB_HOST
	,DB_USER
	,DB_PASS
	,Debug_1::Trace_Code(__FILE__, __LINE__)
);

Error_2::Error_Test($sql, true);

Diag::Dump($sql, '$sql');

?>
