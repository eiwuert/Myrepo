<?php

/**
 * Use the Environment Variable get the ECASH_WWW_DIR
 */
define('ECASH_WWW_DIR', getenv('ECASH_WWW_DIR'));
define('ECASH_COMMON_DIR', getenv('ECASH_COMMON_DIR'));

/**
 * Use the Environment Variables to get the CUSTOMER_DIR
 */
define('CUSTOMER', getenv('ECASH_CUSTOMER'));
define('CUSTOMER_DIR', getenv('ECASH_CUSTOMER_DIR'));
define('CUSTOMER_WWW_DIR', CUSTOMER_DIR . 'www' . DIRECTORY_SEPARATOR);
define('CUSTOMER_CODE_DIR', CUSTOMER_DIR . 'code' . DIRECTORY_SEPARATOR);

/**
 * Uses the Environment Variables from the .htaccess file to determine
 * what the execution mode is and who the customer is so the appropriate
 * configuration file can be loaded.
 */
$customer = getenv('ECASH_CUSTOMER');
$exec_mode = getenv('ECASH_EXEC_MODE');
	
if(  defined('CUSTOMER_DIR') &&
   ! empty($customer) &&
   ! empty($exec_mode) &&
   file_exists(CUSTOMER_CODE_DIR . "{$customer}/Config/{$exec_mode}.php"))
{
	require_once(CUSTOMER_CODE_DIR . "{$customer}/Config/{$exec_mode}.php");
}
else
{
	/**
	  * This should now instead die a horrible death.
	  *
	  * @TODO replace with red screen of death or similar
	  */
	die("No config found in '" . CUSTOMER_CODE_DIR . "{$customer}/Config/{$exec_mode}.php'");
}

/**
 * Loads the config file from the main eCash module
 * and include the index.php file.
 */
require_once ECASH_WWW_DIR . 'config.php';
include ECASH_WWW_DIR . 'index.php';

?>
