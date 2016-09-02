<?php
setDefaultValue('db_host', 'localhost');
setDefaultValue('db_user', 'vendortest');
setDefaultValue('db_pass', 'vendortest');
setDefaultValue('db_name', 'ldb_vndrtst_com');
setDefaultValue('db_port', 3306);

setDefaultValue('paths_root', realpath(dirname(__FILE__).'/../'));
define('ROOT', $GLOBALS['paths_root']);

setDefaultValue('paths_commercial_root', realpath(ROOT.'/../ecash_commercial/'));
define('COMMERCIAL_ROOT', $GLOBALS['paths_commercial_root']);

setDefaultValue('paths_ecash_common_root', realpath(ROOT.'/../ecash_common_cfe/'));
define('ECASH_COMMON_ROOT', $GLOBALS['paths_ecash_common_root']);

setDefaultValue('paths_vendor_api_root', realpath(ROOT . '/../vendor_api'));
define('VENDOR_API_ROOT', $GLOBALS['paths_vendor_api_root']);

define('COMMON_LIB_DIR', '/virtualhosts/lib/');
define('ECASH_WWW_DIR', COMMERCIAL_ROOT.'/www/');
define('ECASH_CODE_DIR', COMMERCIAL_ROOT.'/code/');
define('ECASH_COMMON_DIR', ECASH_COMMON_ROOT);
define('ECASH_COMMON_CODE_DIR', ECASH_COMMON_ROOT.'/code/');
define('LIB_DIR', COMMERCIAL_ROOT.'/lib/');

require 'libolution/AutoLoad.1.php';
AutoLoad_1::addSearchPath(
	ROOT.'/code/',
	VENDOR_API_ROOT.'/code/',
	VENDOR_API_ROOT.'/lib/blackbox/',
	COMMERCIAL_ROOT.'/code/',
	ECASH_COMMON_ROOT.'/code/',
	ROOT.'/../ecash_cra/code/'
);

/**
 * @return PDO
 */
function getTestPDODatabase()
{
	return new PDO("mysql:host={$GLOBALS['db_host']};dbname={$GLOBALS['db_name']};port={$GLOBALS['db_port']}", $GLOBALS['db_user'], $GLOBALS['db_pass']);
}

/**
 * @return DB_MySQLConfig_1
 */
function getTestDBConfig()
{
	return new DB_MySQLConfig_1($GLOBALS['db_host'], $GLOBALS['db_user'], $GLOBALS['db_pass'], $GLOBALS['db_name'], $GLOBALS['db_port']);
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
 * A test config class for commercial that allows manually setting the factory.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 * @package Tests
 */
class TestConfig extends ECash_Config
{
	/**
	 * @var ECash_Factory
	 */
	protected static $factory;

	/**
	 * @return NULL
	 */
	protected function init()
	{

	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if ($name == 'FACTORY')
		{
			return self::$factory;
		}
	}

	/**
	 * Sets the factor for all TestConfigs
	 *
	 * @param ECash_Factory $factory
	 * @return NULL
	 */
	public static function setFactory(/*ECash_Factory*/ $factory)
	{
		self::$factory = $factory;
	}
}
ECash_Config::useConfig('TestConfig');


?>
