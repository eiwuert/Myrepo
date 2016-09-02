<?php
/**
 * Config file for OLPBlackbox PHPUnit tests.
 *
 * NOTE: Do not put any includes for bfw.1.edataserver.com without a conditional!!!
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

date_default_timezone_set('America/Los_Angeles');

//error_reporting(E_WARNING);

// Directory paths
define('BASE_DIR', dirname(__FILE__) . '/../');
define('BFW_CODE_DIR', '/virtualhosts/bfw.1.edataserver.com/include/code/');
define('BFW_OLP_MODULE_DIR', '/virtualhosts/bfw.1.edataserver.com/include/modules/olp/');
define('BLACKBOX_DIR', dirname(__FILE__) . '/../blackbox/');
define('LIB_DIR', '/virtualhosts/lib/');
define('LIB5_DIR', '/virtualhosts/lib5/');
define('BFW_DIR', '/virtualhosts/bfw.1.edataserver.com/');
define('OLP_LIB', '/virtualhosts/olp_lib/');
define('ECASH_COMMON_DIR', '/virtualhosts/ecash_common/');

// things complain if these aren't present
if (!defined('BFW_MODE')) define('BFW_MODE', 'LOCAL');
if (!defined('DEBUG')) define('DEBUG', FALSE);

// Part of the Failover Cron stuff.
define('USE_DATAX_IDV','TRUE');

// AutoLoad (below) requires that the blackbox path be in the root
ini_set('include_path', ini_get('include_path') . ':' . BASE_DIR . ':' . BLACKBOX_DIR . ':' . BFW_DIR . ':' . OLP_LIB);

require_once('libolution/AutoLoad.1.php');
require_once(LIB_DIR . 'suppression_list.1.php');
require_once(LIB_DIR . 'mysql.4.php');
require_once(LIB_DIR . 'mysqli.1.php');
include_once(LIB_DIR . 'aba.bad.php');
require_once(LIB5_DIR . 'datax.2.php');

// used in OLPBlackbox_Enterprise_Agean_Rule_QualifiesForAmount
// TODO: when that is moved to an api call, remove these.
require_once(ECASH_COMMON_DIR . 'ecash_api/loan_amount_calculator.class.php');
require_once(LIB_DIR . 'qualify.2.php');
require_once(LIB_DIR . 'business_rules.class.php');

$config = OLPBlackbox_Config::getInstance();

// set up the DebugConf object for the configuration option.
if (is_null($config->debug))
{
	$config->debug = new OLPBlackbox_DebugConf();
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

if (is_null($config->applog))
{
	$config->applog = new Applog('blackbox', 1000000, 5, '', FALSE);
}

/**
 * Conditional requires.
 *
 * These are conditional because in certain environments (CruiseControl) they may not exist. Tests
 * that require these files will also need to make sure the classes or files exist before running.
 */
if (file_exists(BFW_CODE_DIR . 'Enterprise_Data.php'))
{
	require_once(BFW_CODE_DIR . 'Enterprise_Data.php');
}

if (file_exists(BFW_OLP_MODULE_DIR . 'authentication.php'))
{
	require_once(BFW_OLP_MODULE_DIR . 'authentication.php');
}

if (file_exists(BFW_OLP_MODULE_DIR . 'stats.php'))
{
	require_once(BFW_OLP_MODULE_DIR . 'stats.php');
}

if (file_exists(BFW_OLP_MODULE_DIR . 'stat_limits.php'))
{
	require_once(BFW_OLP_MODULE_DIR . 'stat_limits.php');
}

if (file_exists(BFW_CODE_DIR . 'SiteConfig.php'))
{
	require_once(BFW_CODE_DIR . 'SiteConfig.php');
}

if (file_exists(BFW_CODE_DIR . 'OLP_Qualify_2.php'))
{
	require_once(BFW_CODE_DIR . 'OLP_Qualify_2.php');
}

if (file_exists(BFW_CODE_DIR . 'setup_db.php'))
{
	require_once(BFW_CODE_DIR . 'setup_db.php');
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

/**
 * Returns the test database information.
 *
 * @return object
 */
function TEST_GET_DB_INFO()
{
	$db_info = new stdClass();

	$db_info->host = empty($GLOBALS['TEST_DB_INFO_HOST']) ?
		'localhost' :
		$GLOBALS['TEST_DB_INFO_HOST'];

	$db_info->port = empty($GLOBALS['TEST_DB_INFO_PORT']) ?
		3306 :
		$GLOBALS['TEST_DB_INFO_PORT'];

	$db_info->user = empty($GLOBALS['TEST_DB_INFO_USER']) ?
		'bbxtest' :
		$GLOBALS['TEST_DB_INFO_USER'];

	$db_info->pass = empty($GLOBALS['TEST_DB_INFO_PASS']) ?
		'bbxtest' :
		$GLOBALS['TEST_DB_INFO_PASS'];

	$db_info->name = empty($GLOBALS['TEST_DB_INFO_NAME']) ?
		'bbx_test' :
		$GLOBALS['TEST_DB_INFO_NAME'];

	$db_info->ldb_name = empty($GLOBALS['TEST_DB_INFO_LDB_NAME']) ?
		'ldb_test' :
		$GLOBALS['TEST_DB_INFO_LDB_NAME'];

	return $db_info;
}

/**
 * Returns a PDO connection object to the test database.
 *
 * @return PDO
 */
function TEST_DB_PDO()
{
	$db_info = TEST_GET_DB_INFO();

	return new PDO(
		"mysql:host={$db_info->host};port={$db_info->port};dbname={$db_info->name}",
		$db_info->user,
		$db_info->pass
	);
}

/**
 * Return a PDO connection object for use in PHPUnit.
 *
 * @return PDO Object.
 */
function TEST_DB_PDO_LDB()
{
	$db_info = TEST_GET_DB_INFO();
	
	return new PDO(
		"mysql:host={$db_info->host};port={$db_info->port};dbname={$db_info->ldb_name}",
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

	$db = new MySQL_4(
		"{$db_info->host}:{$db_info->port}",
		$db_info->user,
		$db_info->pass
	);
	$db->Connect();

	return $db;
}

/**
 * Returns a MySQLi_1 test object for unit testing.
 *
 * @return MySQLi_1 object.
 */
function TEST_DB_MYSQLI()
{
	$info = TEST_GET_DB_INFO();
	return new MySQLi_1($info->host, $info->user, $info->pass, $info->ldb_name, $info->port);
}

/**
 * Retrieves the test DataX responses.
 *
 * @param string $dir the directory the file is in
 * @param string $file_name the name of the file to load
 * @return string
 */
function GET_DATAX_XML_FILE($dir, $file_name)
{
	// Snag the XML file contents
	$xml = file_get_contents($dir.'/_datax_xml/'.$file_name);

	return $xml;
}
?>
