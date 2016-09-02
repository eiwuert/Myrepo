<?php
require_once dirname(__FILE__) . '/../../libolution/AutoLoad.1.php';

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

AutoLoad_1::addSearchPath(realpath(dirname(__FILE__) . "/../code"));
AutoLoad_1::addSearchPath(realpath(dirname(__FILE__) . "/../../lib"));
setDefaultValue('db_host', 'localhost');
setDefaultValue('db_user', 'vendortest');
setDefaultValue('db_pass', 'vendortest');
setDefaultValue('db_name', 'ldb_vndrtst_clk');
setDefaultValue('db_port', 3306);
setDefaultValue('api_url', 'http://vendor_api.'.trim(`hostname -f`).'/index.php');
setDefaultValue('api_user', 'api_user');
setDefaultValue('api_pass', 'api_pass');
/**
 * @return PDO
 */
function getTestPDODatabase()
{
	return new PDO("mysql:host={$GLOBALS['db_host']};dbname={$GLOBALS['db_name']};port={$GLOBALS['db_port']}", $GLOBALS['db_user'], $GLOBALS['db_pass']);
}
?>