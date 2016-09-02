<?php

/**
  * This is only required if you're using eCash.  It adds paths to the
  * AutoLoader.
  */


if(defined('ECASH_WWW_DIR')) require_once ECASH_WWW_DIR . 'paths.php';

require_once(ECASH_COMMON_CODE_DIR . 'ECash/Config.php');

/**
 * ENTERPRISE - GENERAL
 */
class MCC_Config_Base extends ECash_Config
{
	protected function init()
 	{
		/**
		 * Enterprise Prefix
		 * 
		 * Set to a string because OLP couldn't loop configs due to the constant CUSTOMER
		 * assigned to ENTERPRISE_PREFIX. GF #16842
		 */
		$this->configVariables['ENTERPRISE_PREFIX'] =  'MCC';
		$this->configVariables['CUSTOMER_BASE_DIR'] =  dirname(__FILE__) . "/../../../";
		$this->configVariables['CUSTOMER_CODE_DIR'] =  $this->configVariables['CUSTOMER_BASE_DIR'] . 'code/';
		
		/**
		 * @TODO These need to be removed from the code and the code set up to dynamically pull the values
		 */
		if(!defined('CUSTOMER')) define('CUSTOMER', $this->configVariables['ENTERPRISE_PREFIX']);
		if(!defined('CUST_DIR')) define('CUST_DIR', dirname(__FILE__) . "/../../../");
		if(!defined('CUSTOMER_LIB')) define('CUSTOMER_LIB', $this->configVariables['CUSTOMER_BASE_DIR'] . "legacy_customer_lib/");

 		/**
		 * Paths to directories and files
		 */
//		$this->configVariables['PDFLIB_LICENSE_FILE'] =  "/etc/pdflib/pdflib_licenses.txt";

		/**
		 * Database connections
		 */
		$this->configVariables['DB_HOST'] = 'db101.ept.tss';
		$this->configVariables['DB_NAME'] = 'ldb_mcc';
		$this->configVariables['DB_USER'] = 'ecash';
		$this->configVariables['DB_PASS'] = 'lacosanostra';
		$this->configVariables['DB_PORT'] = 3308;
		$this->configVariables['DB_CACHE'] = "/tmp/mysqli_cache/{$this->configVariables['mode']}";

		$this->configVariables['SLAVE_DB_HOST'] = 'db101.ept.tss';
		$this->configVariables['SLAVE_DB_NAME'] = 'ldb_mcc';
		$this->configVariables['SLAVE_DB_USER'] = 'ecash';
		$this->configVariables['SLAVE_DB_PASS'] = 'lacosanostra';
		$this->configVariables['SLAVE_DB_PORT'] = 3308;
		$this->configVariables['SLAVE_DB_CACHE'] = "/tmp/mysqli_cache/{$this->configVariables['mode']}";

		/**
		 * OLP slave for denied report - Fraud Only
		 */
		// Is this used? Needed?
		//$this->configVariables['OLP_DB_HOST'] = 'db101.ept.tss';
		//$this->configVariables['OLP_DB_NAME'] = 'rc_olp';
		//$this->configVariables['OLP_DB_USER'] = 'sellingsource';
		//$this->configVariables['OLP_DB_PASS'] = 'password';
		//$this->configVariables['OLP_DB_PORT'] = 3306;
		$this->configVariables['DOCUMENT_TEST_EMAIL'] = 'william.parker@sellingsource.com';

		/**
		 * Default eCash user - From agent table
		 */
		$this->configVariables['DEFAULT_AGENT'] = 'ecash';
		$this->configVariables['DEFAULT_AGENT_ID'] =  1;

		/**
		 * Statistics database connection constants
		 */
		$this->configVariables['STAT_MYSQL_HOST'] =  'db101.ept.tss:3317';
		$this->configVariables['STAT_MYSQL_USER'] =  'sellingsource';
		$this->configVariables['STAT_MYSQL_PASS'] =  'password';

		/**
		 * Force Redirection to SSL
		 */
		$this->configVariables['FORCE_SSL_LOGIN'] = 'OFF';

		/**
		 * Master DOMAIN, used for requests that MUST be executed on the same server
		 * ie, ach batches, quick check batches, etc and should be a subdomain of
		 * the load balanced domain.
		 */
		$this->configVariables['MASTER_DOMAIN'] = $_SERVER['HTTP_HOST'];
		$this->configVariables['LOAD_BALANCED_DOMAIN'] = $_SERVER['HTTP_HOST'];
		$this->configVariables['COOKIE_DOMAIN'] = $_SERVER['SERVER_NAME'];

		/**
	 	* Notification Recipients
		 */
		$this->configVariables['NOTIFICATION_ERROR_RECIPIENTS'] = 'brian.ronald@sellingsource.com, nita.tune@sellingsource.com, ben.burkhart@sellingsource.com';
		$this->configVariables['ECASH_NOTIFICATION_ERROR_RECIPIENTS'] = 'brian.ronald@sellingsource.com, nita.tune@sellingsource.com, ben.burkhart@sellingsource.com';
//		$this->configVariables['MANAGER_EMAIL_ADDRESS'] = 'william.parker@cubisfinancial.com';
		$this->configVariables['DOCUMENT_TEST_FAX'] = '';

		/**
		 * ACH Defaults
		 */
		// Used by ACH_Deem_Successful-- This should be a business rule!
		$this->configVariables['ACH_BATCH_SERVER'] = 'ds68.tss';
		$this->configVariables['ACH_BATCH_LOGIN']  = 'achtest';
		$this->configVariables['ACH_BATCH_PASS']   = 'achtest';
		$this->configVariables['ACH_BATCH_URL']    = '/home/achtest/tranfile';

		// This is not where it's sent, but rather a way for GPG to decide which public key to encrypt it with
		// Warning: You can not decrypt the message unless you have a private key for the recipient!
		$this->configVariables['ACH_RECIPIENT']   = "ben.burkhart@sellingsource.com";

		$this->configVariables['ACH_BATCH_NOTIFY_LIST']   = 'ben.burkhart@sellingsource.com';

		$this->configVariables['ACH_HOLD_DAYS'] = 2;
		$this->configVariables['CHECK_HOLD_DAYS'] = 10;
		$this->configVariables['ACH_TRANSPORT_TYPE'] = 'SFTP_AGEAN';
		$this->configVariables['ACH_PROCESSOR'] = 'ACHCOMMERCE';

		$this->configVariables['ACH_BATCH_SERVER_PORT'] = null;
		$this->configVariables['ACH_BATCH_KEY'] = 'RC';

		// USE_ACH_ENTRY_DETAIL is used to toggle the creation of an offsetting type 6 entry
		$this->configVariables['USE_ACH_ENTRY_DETAIL'] = FALSE;
		
		$this->configVariables['ACH_BATCH_FORMAT'] = 'AdvantageACH';
		$this->configVariables['ACH_RETURN_FORMAT'] = 'AdvantageACH';

		/**
		 * New & React Apps
		 */
		$this->configVariables['NEW_APP_SITE'] = 'http://www.mycashcenter.com';
		$this->configVariables['ECASH_APP'] = 'http://rc.ecashapp.com/';
		$this->configVariables['REACT_SOAP_URL'] = 'http://rc.bfw.1.edataserver.com/cm_soap.php?wsdl';
		$this->configVariables['REACT_SOAP_KEY'] = '13eb55c3098ad6a6e18a3aadd90d1304';
		$this->configVariables['ECASH_APP_REACT_PROMOID'] = 27713;

		/**
		 * Queue realted defaults
		 *
		 * (8am -> 8pm)
		 */
		$this->configVariables['LOCAL_EARLIEST_CALL_TIME'] = '8';
		$this->configVariables['LOCAL_LATEST_CALL_TIME'] =  '20';
		$this->configVariables['QUEUE_RECYCLE_EXPIRED_WINDOW'] = '24 hour';

		/**
		 * Features - Enable / Disable Switches
		 */
		$this->configVariables['MULTI_COMPANY_ENABLED'] = FALSE;
		$this->configVariables['MULTI_COMPANY_INCLUDE_ARCHIVE'] = FALSE;
		$this->configVariables['INVESTOR_GROUP_TAGGING_ENABLED'] = FALSE;
		$this->configVariables['DAILY_CASH_REPORT_ENABLED'] = FALSE;
		$this->configVariables['PBX_ENABLED'] = FALSE;
		$this->configVariables['USE_PRINT_MANAGER'] = FALSE; // Not Implemented
		$this->configVariables['USE_SOAP_PREACT'] = FALSE;

		/**
		 * Payment / Transactions Options
		 */
		$this->configVariables['USE_ADHOC_PAYMENTS'] = FALSE;
		$this->configVariables['USE_DEBT_CONSOLIDATION_PAYMENTS'] = TRUE;

		/**
		 * UI Changes that aren't easily overriden in customer_lib
		 */
		$this->configVariables['SHOW_SUMMARY_BAR'] = TRUE;
		$this->configVariables['USE_LOGIN'] = TRUE;
		$this->configVariables['USE_CUSTOMER_CENTRIC'] = FALSE;
		$this->configVariables['USE_HOTKEYS'] = FALSE;
		$this->configVariables['EXTRA_PERSONAL_FIELDS'] = array('name_middle', 'name_suffix');
		$this->configVariables['EXTRA_GENERAL_FIELDS'] = array('name_middle', 'name_suffix');

		/**
		 * Other Defaults
		 */
		$this->configVariables['SYSTEM_NAME'] = 'ecash3_0';
		$this->configVariables['TIME_ZONE'] = 'US/Central';
		$this->configVariables['TICKET_PRIORITY'] = 4;
		$this->configVariables['CONVERSION_MODE'] = 'OTHER';
		$this->configVariables['MAX_SEARCH_DISPLAY_ROWS'] = 500;
		$this->configVariables['MAX_REPORT_DISPLAY_ROWS'] = 10000;
		$this->configVariables['URL_PAYDATE_WIDGET'] = "http://paydates.widgets.edataserver.com";
		$this->configVariables['DOCUMENT_TEST_EMAIL'] = 'ecash3mcc@gmail.com';
		$this->configVariables['PRODUCT_TYPE'] = 'Loan';
		$this->configVariables['ALLOW_UNSIGNED_APPS'] = TRUE;
		$this->configVariables['LOAN_NOTICE_DAYS'] = '2';
		$this->configVariables['LOAN_NOTICE_TIME'] = '1700';

		/*
		$this->configVariables['SEARCH_DISPLAY_COLUMNS'] = array(
														'application_id' => true,
														'name_first' => true,
														'name_last' => true,
														'ssn' => true,
														'street' => true,
														'city' => true,
														'state' 	=> true,
														'application_status' => true,
														'application_balance' => true);
		*/
		
	}
}
