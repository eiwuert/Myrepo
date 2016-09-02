<?php
/**
 * Setup file for the olp_lib tests.
 *
 * @author Brian Feaver <brian.feaver@sellingosurce.com>
 */

date_default_timezone_set('America/Los_Angeles');

// Directory paths
define('BASE_DIR', realpath(dirname(__FILE__) . '/../') . '/');
if (isset($GLOBALS['CRUISE_CONTROL']) && $GLOBALS['CRUISE_CONTROL'])
{
	set_include_path(implode(PATH_SEPARATOR, array(
		'/virtualhosts/lib5',
		'/virtualhosts/lib',
		realpath(BASE_DIR . '/../'),
		realpath(BASE_DIR . '/../libolution'),
		realpath(BASE_DIR . '/code'),
		realpath(BASE_DIR . '/lib'),
		realpath(BASE_DIR . '/tests'),
		get_include_path()
	)));	
}
else
{
	set_include_path(implode(PATH_SEPARATOR, array(
		'/virtualhosts',
		'/virtualhosts/lib5',
		'/virtualhosts/lib',
		'/virtualhosts/libolution',
		realpath(BASE_DIR . '/code'),
		realpath(BASE_DIR . '/lib'),
		realpath(BASE_DIR . '/tests'),
		get_include_path(),
	)));
}

require_once 'libolution/AutoLoad.1.php';
require_once 'OLPECash/Config.php';

require_once('suppression_list.1.php');
require_once('applog.1.php');
include_once('aba.bad.php');

define('TEST_BLACKBOX', 'TEST_BLACKBOX');
define('TEST_OLP', 'TEST_OLP');

// Set up memcache servers to stop warnings.
Cache_Servers_OLP::setupMemcacheServers('LOCAL');

$config = OLPBlackbox_Config::getInstance();

// set up the DebugConf object for the configuration option.
if (is_null($config->debug))
{
	$config->debug = new OLPBlackbox_DebugConf();
}

if (is_null($config->applog))
{
	$config->applog = new Applog('blackbox', 1000000, 5, '', FALSE);
}

if (is_null($config->memcache))
{
	$config->memcache = Cache_Memcache::getInstance();
}

/**
 * Mock event log class for testing purposes.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class MockEventLog
{
	/**
	 * Mocks the interface for logging from legacy blackbox.
	 *
	 * This is declared with no parameters because it doesn't matter
	 * how this is called, for the moment.
	 *
	 * @return void
	 */
	public function Log_Event()
	{
		// pass
	}
}
if (is_null($config->event_log))
{
	$config->event_log = new MockEventLog();
}

/**
 * Returns the test database information.
 *
 * @return object
 */
function TEST_GET_DB_INFO($type = TEST_OLP)
{
	static $settings = array(
		TEST_OLP => array(
			'HOST' => 'localhost',
			'PORT' => 3306,
			'USER' => 'bbxtest',
			'PASS' => 'bbxtest',
			'NAME' => 'bbx_test',
			'LDB_NAME' => 'ldb_test',
		),
		TEST_BLACKBOX => array(
			'HOST' => 'localhost',
			'PORT' => 3306,
			'USER' => 'bbxtest',
			'PASS' => 'bbxtest',
			'NAME' => 'bbxadmin_test',
			'LDB_NAME' => 'ldb_test',
		),
	);
	static $valid_types = array(TEST_BLACKBOX, TEST_OLP);

	if (!in_array($type, $valid_types))
	{
		throw new InvalidArgumentException(sprintf(
			'database type requested must be in %s', print_r($valid_types, TRUE))
		);
	}

	$db_info = new stdClass();

	$db_info->host = empty($GLOBALS[$type . '_DB_INFO_HOST']) ?
		'localhost' :
		$GLOBALS[$type . '_DB_INFO_HOST'];

	$db_info->port = empty($GLOBALS[$type . '_DB_INFO_PORT']) ?
		3306 :
		$GLOBALS[$type . '_DB_INFO_PORT'];

	$db_info->user = empty($GLOBALS[$type . '_DB_INFO_USER']) ?
		'bbxtest' :
		$GLOBALS[$type . '_DB_INFO_USER'];

	$db_info->pass = empty($GLOBALS[$type . '_DB_INFO_PASS']) ?
		'bbxtest' :
		$GLOBALS[$type . '_DB_INFO_PASS'];

	$db_info->name = empty($GLOBALS[$type . '_DB_INFO_NAME']) ?
		'bbx_test' :
		$GLOBALS[$type . '_DB_INFO_NAME'];

	$db_info->ldb_name = empty($GLOBALS[$type . '_DB_INFO_LDB_NAME']) 
		? 'ldb_test' 
		: $GLOBALS[$type . '_DB_INFO_LDB_NAME'];

	return $db_info;
}

/**
 * Returns a PDO connection object to the test database.
 *
 * @return PDO
 */
function TEST_DB_PDO($type = TEST_OLP)
{
	$db_info = TEST_GET_DB_INFO($type);

	return new PDO(
		"mysql:host={$db_info->host};port={$db_info->port};dbname={$db_info->name}",
		$db_info->user,
		$db_info->pass
	);
}

/**
 * Returns a new MySQL_4 connection for the test db.
 *
 * @return MySQL_4
 */
function TEST_DB_MYSQL4()
{
	$db_info = TEST_GET_DB_INFO();
	
	$db = OLP_DB_MySQL4Adapter::fromConnection(TEST_DB_DATABASE());

	return $db;
}

/**
 * Returns a DB_Database_1 connection to the test database.
 *
 * @return DB_Database_1
 */
function TEST_DB_DATABASE()
{
	$db_info = TEST_GET_DB_INFO();
	
	return new DB_Database_1(
		"mysql:host={$db_info->host};port={$db_info->port};dbname={$db_info->name}",
		$db_info->user,
		$db_info->pass
	);
}

/**
 * Returns a new DB_Database_1 connection for the test db.
 *
 * @return DB_Database_1
 */
function TEST_DB_CONNECTOR($type)
{
	$db_info = TEST_GET_DB_INFO($type);

	$db = new DB_Database_1(
		"mysql:host={$db_info->host};port={$db_info->port};dbname={$db_info->name}",
		$db_info->user,
		$db_info->pass
	);

	return $db;
}

/**
 * Get the connection to the test bbxadmin database.
 */
function TEST_DB_PDO_BBXADMIN()
{
	$db_info = TEST_GET_DB_INFO(TEST_BLACKBOX);
	
	return new PDO(
		"mysql:host={$db_info->host};port={$db_info->port};dbname={$db_info->name}",
		$db_info->user,
		$db_info->pass
	);
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

