<?PHP
/**	
	@version
		1.0.0 7/2005 - Randy Kochis

	@change_log
		1.0.0 
			- Version 1
*/

//Condor Standard Constants

DEFINE('DIR_LIB', '/virtualhosts/lib');
DEFINE('DIR_LIB5', '/virtualhosts/lib5');
DEFINE("DIR_INCLUDE", "../../include/");	// Include Directory
DEFINE("DIR_CODE", DIR_INCLUDE."code/");	// Code Directory
DEFINE("DIR_PRPC", "/virtualhosts/lib5/prpc/");				// prpc 5 directory

switch(TRUE)
{
	// local
	case preg_match('/(ds\d{2}.tss|gambit.tss)$/', $_SERVER['SERVER_NAME'] ):
	DEFINE("MYSQL_HOST","monster.tss:3309");		// MYSQL Host
	DEFINE("MYSQL_USER","condor");            // MYSQL DB User
	DEFINE("MYSQL_PWD","password");         		// MYSQL DB Password
	DEFINE("MYSQL_DB", "condor");
	break;
	
	// RC
	case preg_match('/^rc\./', $_SERVER['VHOST']):

	DEFINE("MYSQL_HOST","db101.clkonline.com:3308");               // MYSQL Host
	DEFINE("MYSQL_USER","condor");            // MYSQL DB User
	DEFINE("MYSQL_PWD","password");                         // MYSQL DB Password
	DEFINE("MYSQL_DB", "condor");

	break;
	
	// LIVE 
	default:
	DEFINE("MYSQL_HOST","writer.condor.ept.tss");               // MYSQL Host
	DEFINE("MYSQL_USER","condor");            // MYSQL DB User
	DEFINE("MYSQL_PWD","password");                         // MYSQL DB Password
	DEFINE("MYSQL_DB", "condor");
	break;
	
}

// Required Files
require_once("debug.1.php");			// Debug Include
require_once("error.2.php");			// Error Include
require_once("mysql.4.php");			// Mysql Include
require_once(DIR_PRPC."server.php");			// Prpc Server Include
require_once(DIR_CODE."condor.class.php");		// Condor Class

// Instantiate the MySQL object and connect

$sql = new MySQL_4(MYSQL_HOST,MYSQL_USER,MYSQL_PWD, DEBUG);

try
{
	$sql->Connect(TRUE);
}
catch ( MySQL_Exception $e )
{
	throw $e;
}

?>
