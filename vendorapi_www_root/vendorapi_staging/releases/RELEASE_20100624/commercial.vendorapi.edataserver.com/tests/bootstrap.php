<?php

setDefaultValue('db_host', 'localhost');
setDefaultValue('db_user', 'vendortest');
setDefaultValue('db_pass', 'vendortest');
setDefaultValue('db_name', 'ldb_vndrtst_clk');
setDefaultValue('db_port', 3306);
setDefaultValue('api_url', 'http://vendor_api.'.trim(`hostname -f`).'/index.php');
setDefaultValue('api_user', 'api_user');
setDefaultValue('api_pass', 'api_pass');

define('ROOT', realpath(dirname(__FILE__).'/../'));

// We have to setup the lib path for libolution before we can include the AutoLoad class.
set_include_path(
	'.'
	. PATH_SEPARATOR . '/usr/share/php'
	. PATH_SEPARATOR . realpath(ROOT.'/../')
	. PATH_SEPARATOR . realpath(ROOT.'/../lib5')
	. PATH_SEPARATOR . realpath(ROOT.'/../lib')
	. PATH_SEPARATOR . get_include_path()
);

date_default_timezone_set("America/Los_Angeles");

require 'libolution/AutoLoad.1.php';
require_once('PHPUnit/Extensions/Database/TestCase.php');

AutoLoad_1::addSearchPath(
	ROOT.'/lib/',
	ROOT.'/code/',
	ROOT.'/../libolution/',
	ROOT.'/../lib/',
	ROOT.'/../lib5',
	ROOT.'/../ecash_common_cfe/code/',
	ROOT.'/../olp_lib/code/',
	ROOT.'/../ecash_shared/code/',
	ROOT.'/../web_services/code/',
	ROOT.'/../ecash_clk/code/',
	'./_code/'
);
// Make Vendor_API_Fail harmless for unittests
function Vendor_API_Fail()
{
	return;
}

/**
 * @return PDO
 */
function getTestPDODatabase()
{
	return new PDO("mysql:host={$GLOBALS['db_host']};dbname={$GLOBALS['db_name']};port={$GLOBALS['db_port']}", $GLOBALS['db_user'], $GLOBALS['db_pass']);
}

/**
 * @return PDO
 */
function getTestDatabase()
{
	return new DB_Database_1("mysql:host={$GLOBALS['db_host']};dbname={$GLOBALS['db_name']};port={$GLOBALS['db_port']}", $GLOBALS['db_user'], $GLOBALS['db_pass']);
}

/**
 * @return DB_MySQLConfig_1
 */
function getTestDatabaseConfig()
{
	return new DB_MySQLConfig_1(
		$GLOBALS['db_host'],
		$GLOBALS['db_user'],
		$GLOBALS['db_pass'],
		$GLOBALS['db_name'],
		$GLOBALS['db_port']
	);
}

/**
 * @return Rpc_Client_1
 */
function getTestClient($enterprise, $company, $user = NULL, $password = NULL)
{
	if ($user === NULL) $user = $GLOBALS['api_user'];
	if ($password === NULL) $password = $GLOBALS['api_pass'];

	$url = $GLOBALS['api_url']
		.'?enterprise='.urlencode($enterprise)
		.'&company='.urlencode($company)
		.'&username='.urlencode($user)
		.'&password='.urlencode($password);
	return new Rpc_Client_1($url);
}


/**
 * Sets a global variable with a default value if that variable doesn't
 * already exist.
 *
 * @param string $var
 * @param string $val
 * @return NULL
 */
function setDefaultValue($var, $val)
{
	if (!array_key_exists($var, $GLOBALS))
	{
		$GLOBALS[$var] = $val;
	}
}

/**
 * COPIED over from blackbox_test_setup.php so you can unit test rules inside
 * of OLPBlackbox. Should be moved and cleaned up at some point. [MP]
 *
 * Temporary (I assume) object that lets you provide a data array to Blackbox_Data
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class Blackbox_DataTestObj extends Blackbox_Data
{
	/**
	 * Blackbox_DataTestObj constructor
	 *
	 * @param array $data the data for the object
	 */
	public function __construct(array $data)
	{
		$this->data = $data;
	}
}


?>
