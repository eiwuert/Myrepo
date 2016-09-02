<?php
/**
 * Configuration File
 *
 * This file defines all of the basic defaults required for eCash
 * to run.  These values should almost never need to be changed.
 */

/**
 * Software Name & Version Info
 */
define('SOFTWARE_NAME', 'ecash');
define('MAJOR_VERSION', 3);
define('MINOR_VERSION', 5);
define('BUILD_NUM', 66);
define('ECASH_VERSION', MAJOR_VERSION);

/**
 * Default Paths
 */
define('BASE_DIR', dirname(__FILE__) . '/../' );
define('CLI_EXE_PATH', '/usr/bin/');
define('CLIENT_CODE_DIR', BASE_DIR . 'client/code/');
define('CLIENT_VIEW_DIR', BASE_DIR . 'client/view/');
define('CLIENT_MODULE_DIR', BASE_DIR . 'client/module/');
define('SERVER_CODE_DIR', BASE_DIR . 'server/code/');
define('SERVER_MODULE_DIR', BASE_DIR . 'server/module/');
define('WWW_DIR', BASE_DIR . 'www/');
define('ECASH_WWW_DIR', BASE_DIR . 'www/');
define('LIB_DIR', BASE_DIR . 'lib/');
define('SQL_LIB_DIR', BASE_DIR . 'sql/lib/');
define('CLI_SCRIPT_PATH', SERVER_CODE_DIR);
define('REQUEST_LOG_PATH', BASE_DIR . 'data/sqlite/request_log.sq3');
define('ECASH_DIR',BASE_DIR.'code/ECash/'); /** @depricated use ECASH_CODE_DIR */
define('ECASH_CODE_DIR',BASE_DIR.'code/ECash/');
/**
 * Library directories
 */

require_once('paths.php');

/**
 * Define the autoloader
 */
require_once(LIBOLUTION_DIR . 'AutoLoad.1.php');

$customer_dir = getenv('ECASH_CUSTOMER_DIR');
$customer = getenv('ECASH_CUSTOMER');
$exec_mode = getenv('ECASH_EXEC_MODE');

if (empty($customer_dir)) { die("ECASH_CUSTOMER_DIR not set in ENV!\n"); }
if (empty($customer)) { die("ECASH_CUSTOMER not set in ENV!\n"); }
if (empty($exec_mode)) { die("ECASH_EXEC_MODE not set in ENV!\n"); }

/**
 * Look for an Enterprise configuration file symlinked
 * in the config/ directory.  If it doesn't exist, die.
 */
require_once(ECASH_COMMON_DIR . '/code/ECash/Config.php');

if(! empty($customer_dir) &&
		! empty($customer) &&
		! empty($exec_mode) &&
		file_exists("{$customer_dir}/code/{$customer}/Config/{$exec_mode}.php"))
{
	require_once("{$customer_dir}/code/{$customer}/Config/{$exec_mode}.php");
}
else
{
	/**
	 * This should now instead die a horrible death rather than loading CLK
	 *
	 * @TODO replace with red screen of death or similar
	 */
	die("No config found in '{$customer_dir}/code/{$customer}/Config/{$exec_mode}.php'");
}

ini_set('include_path',ini_get('include_path') .':'.LIBOLUTION_DIR . ':' . BASE_DIR . ':' . ECASH_COMMON_CODE_DIR . ':'. COMMON_LIB_ALT_DIR.':'.COMMON_LIB_DIR.':'.LIB_DIR);

ECash_Config::useConfig($customer . '_Config_' . $exec_mode);

/**
 * Set the Title Bar
 */
$short_title = 'eCash Commercial - '.MAJOR_VERSION.'.'.MINOR_VERSION;
$hostname = exec('hostname');
$mode = eCash_Config::getInstance()->mode;
$dsn= ECash_Config::getInstance()->DB_MASTER_CONFIG->getDSN();

if (EXECUTION_MODE != 'LIVE')
     define('TITLE', $short_title . ' Build '.BUILD_NUM." ({$mode} - $dsn) (PS: $hostname)");
else define('TITLE', $short_title . " (PS: $hostname)");

/**
 * Our language abbstraction class
 */
require_once(LIB_DIR . '/DisplayMessage.class.php');

/**
 * AppLog Defaults
 */
defined('APPLOG_SIZE_LIMIT') || define('APPLOG_SIZE_LIMIT', 5000000);
defined('APPLOG_FILE_LIMIT') || define('APPLOG_FILE_LIMIT', 80);
defined('APPLOG_SUBDIRECTORY') || define('APPLOG_SUBDIRECTORY', "ecash3.0/{$mode}");

/**
 * Some very basic defaults
 */
define('SESSION_EXPIRATION_HOURS', 12);
define('SCRIPT_TIME_LIMIT_SECONDS', 120);
define('PHP_MEMORY_USE_THRESHOLD', 50000000);

/**
 *Define the precision for BC Math #10338
 */
bcscale(2);

/**
 * Set the run-time environment to use the time zone.
 */
if($tz = eCash_Config::getInstance()->TIME_ZONE)
{
	date_default_timezone_set($tz);
}

/**
 * Set the default locale to en_US
 */
setlocale(LC_ALL, 'en_US');

/**
 * Data fix Tracking flag.
 */
if(! defined('DEFAULT_SOURCE_TYPE')) define('DEFAULT_SOURCE_TYPE', 'ecashinternal');

/**
 * Queue timeout options, used for the Queue Configuration
 */
$GLOBALS["DEFAULT_QUEUE_TIMEOUTS"] = array
	(	"COMPANY"		=> "Company Default",
		"5" 	=> "5 Minutes",
		"10" 	=> "10 Minutes",
		"15" 	=> "15 Minutes",
		"30" 	=> "30 Minutes",
		"60" 	=> "1 Hour",
		"120"	=> "2 Hours",
		"180"	=> "3 Hours",
		"240" 	=> "4 Hours",
		"360" 	=> "6 Hours",
		"480" 	=> "8 Hours",
		"600" 	=> "10 Hours",
		"720" 	=> "12 Hours",
		"1440" 	=> "1 Day",
		"2880" 	=> "2 Days",
		"4320" 	=> "3 Days",
		"10080"	=> "7 Days",
		"none" 	=> "Never",
	);
