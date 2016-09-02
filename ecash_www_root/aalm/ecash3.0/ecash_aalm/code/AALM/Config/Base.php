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
class AALM_Config_Base extends ECash_Config
{
	protected function init()
 	{
		/**
		 * Enterprise Prefix
		 *
		 * Set to a string because OLP couldn't loop configs due to the constant CUSTOMER
		 * assigned to ENTERPRISE_PREFIX. GF #16842
		 */
        $this->configVariables['ENTERPRISE_PREFIX'] =  'AALM';
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
		$this->configVariables['PDFLIB_LICENSE_FILE'] =  "/etc/pdflib/pdflib_licenses.txt";


		/**
		 * Default eCash user - From agent table
		 */
		$this->configVariables['DEFAULT_AGENT'] = 'ecash';
		$this->configVariables['DEFAULT_AGENT_ID'] =  1;

		/**
		 * Statistics database connection constants
		 */
		$this->configVariables['STAT_MYSQL_HOST'] =  'db101.ept.tss:3317';
		$this->configVariables['STAT_MYSQL_USER'] =  'site_type_stats';
		$this->configVariables['STAT_MYSQL_PASS'] =  '$h1tchUnK%';

		/**
		 * Force Redirection to SSL
		 */
		$this->configVariables['FORCE_SSL_LOGIN'] = 'OFF';

		/**
		 * Master DOMAIN, used for requests that MUST be executed on the same server
		 * ie, ach batches, quick check batches, etc and should be a subdomain of
		 * the load balanced domain.
		 */
		//[#40090] not set for CLI
		$HTTP_HOST = empty($_SERVER['HTTP_HOST']) ? '' : $_SERVER['HTTP_HOST'];
		$this->configVariables['MASTER_DOMAIN'] = $HTTP_HOST;
		$this->configVariables['LOAD_BALANCED_DOMAIN'] = $HTTP_HOST;
		$this->configVariables['COOKIE_DOMAIN'] = empty($_SERVER['SERVER_NAME']) ? '' : $_SERVER['SERVER_NAME'];

		/**
	 	* Notification Recipients
		 */
		$this->configVariables['NOTIFICATION_ERROR_RECIPIENTS'] = 'rebel75cell@gmail.com, randy.klepetko@sbcglobal.net'; //'brian.ronald@sellingsource.com, william.parker@cubisfinancial.com';
		$this->configVariables['ECASH_NOTIFICATION_ERROR_RECIPIENTS'] = 'rebel75cell@gmail.com, randy.klepetko@sbcglobal.net'; //'brian.ronald@sellingsource.com, william.parker@cubisfinancial.com';
		$this->configVariables['MANAGER_EMAIL_ADDRESS'] = '';
		$this->configVariables['DOCUMENT_TEST_FAX'] = '';


		/**
		 * ACH Defaults
		 */
		// Used by ACH_Deem_Successful-- This should be a business rule!
		$this->configVariables['ACH_HOLD_DAYS'] = 2;
		
		$this->configVariables['COMBINED_ACH'] = TRUE;
		$this->configVariables['ACH_TRANSPORT_TYPE'] = 'SFTP';
		$this->configVariables['ACH_BATCH_SERVER'] = 'ds68.tss';
		$this->configVariables['ACH_BATCH_URL'] = '/home/achtest/deposits/';
		
		$this->configVariables['ACH_BATCH_KEY'] = 'RC';
		// USE_ACH_ENTRY_DETAIL is used to toggle the creation of an offsetting type 6 entry
		$this->configVariables['USE_ACH_ENTRY_DETAIL'] = FALSE;
		
		$this->configVariables['ACH_BATCH_FORMAT'] = 'Regal';
		$this->configVariables['ACH_RETURN_FORMAT'] = 'Regal';
		$this->configVariables['MERCHANT_ID'] = '410133';
		$this->configVariables['CLIENT_ID'] = 174;
		$this->configVariables['MIN_ACH_ID'] = 9999;

		// Card processing stuff
		$this->configVariables['COMBINED_CARD'] = FALSE;
		$this->configVariables['CARD_PROCESS_FORMAT'] = 'Anet';

		/**
		 * New & React Apps
		 */
		$this->configVariables['NEW_APP_SITE'] = '';
		$this->configVariables['ECASH_APP'] = 'http://rc.ecashapp.com/';
		$this->configVariables['REACT_SOAP_URL'] = 'http://rc.bfw.1.edataserver.com/cm_soap.php?wsdl';
		$this->configVariables['REACT_SOAP_KEY'] = '13eb55c3098ad6a6e18a3aadd90d1304';
		$this->configVariables['ECASH_APP_REACT_PROMOID'] = 27713;
		$this->configVariables['VAPI_PRPC_URL'] = 'https://rc.vendorapi-commercial.edataserver.com/index.php';
		$this->configVariables['VAPI_PRPC_USER'] = 'api_user';
		$this->configVariables['VAPI_PRPC_PASSWORD'] = 'password';
		
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
		$this->configVariables['DAILY_CASH_REPORT_ENABLED'] = TRUE;
		$this->configVariables['PBX_ENABLED'] = FALSE;
		$this->configVariables['USE_PRINT_MANAGER'] = FALSE; // Not Implemented
		$this->configVariables['USE_SOAP_PREACT'] = FALSE;

		/**
		  * Enables / Disables the loading of the 
		  * Fraud Rules in the Loan_Data class when
		  * the application is loaded.
		  */
		$this->configVariables['USE_FRAUD_RULES'] = TRUE; 

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
		$this->configVariables['TIME_ZONE'] = 'US/Pacific';
		$this->configVariables['CONVERSION_MODE'] = 'OTHER';
		$this->configVariables['MAX_SEARCH_DISPLAY_ROWS'] = 500;
		$this->configVariables['MAX_REPORT_DISPLAY_ROWS'] = 10000;
		$this->configVariables['URL_PAYDATE_WIDGET'] = "https://live.ecash.loanservicingcompany.com/widget/index.php";
		$this->configVariables['DOCUMENT_TEST_EMAIL'] = 'ecash3mls@gmail.com';
		$this->configVariables['PRODUCT_TYPE'] = 'Loan';
		$this->configVariables['ALLOW_UNSIGNED_APPS'] = TRUE;

		/**
		 * Online Help Root URL (Dev & RC)
		 */
		$this->configVariables['ONLINE_HELP_ADMIN_URL_ROOT'] = 'http://help.aalm.ecash.ept.tss/admin/';
		$this->configVariables['ONLINE_HELP_FUNDING_URL_ROOT'] = 'http://help.aalm.ecash.ept.tss/funding/';
		$this->configVariables['ONLINE_HELP_SERVICING_URL_ROOT'] = 'http://help.aalm.ecash.ept.tss/servicing/';
		$this->configVariables['ONLINE_HELP_COLLECTIONS_URL_ROOT'] = 'http://help.aalm.ecash.ept.tss/collections/';
		$this->configVariables['ONLINE_HELP_REPORTING_URL_ROOT'] = 'http://help.aalm.ecash.ept.tss/reporting/';
		$this->configVariables['ONLINE_HELP_DEFAULT_INDEX'] = 'ecash_online_help.htm';

		/*
		 * StatPro 2 Username and Password
		 */
		$this->configVariables["STATPRO_USERNAME"] = "generic";
		$this->configVariables["STATPRO_PASSWORD"] = "password"; 
		
		/**
		 * Path To File System Encryption Key
		 */
		$this->configVariables['FILE_SYSTEM_KEY_PATH'] = $this->configVariables['CUSTOMER_BASE_DIR'] . 
														 '../../encryption/' . strtolower($this->configVariables['ENTERPRISE_PREFIX']) .
														 '/fs_key.dat';

		/**
		 * Application Service URLs
		 */
		$this->configVariables['BASE_SERVICE_URL'] = 'http://localhost:8080/';
		//$this->configVariables['BASE_SERVICE_URL'] = 'http://localhost:8080/';
		$this->configVariables['REFERENCES_SERVICE_URL'] = $this->configVariables['BASE_SERVICE_URL'].'soap-1.0/references?wsdl';
		$this->configVariables['APP_SERVICE_URL'] = $this->configVariables['BASE_SERVICE_URL'].'soap-1.0/service/app?wsdl';
		$this->configVariables['INQUIRY_SERVICE_URL'] = $this->configVariables['BASE_SERVICE_URL'].'soap-1.0/service/inquiry?wsdl';
		$this->configVariables['DOCUMENT_HASH_SERVICE_URL'] = $this->configVariables['BASE_SERVICE_URL'].'soap-1.0/service/documenthash?wsdl';
		$this->configVariables['DOCUMENT_SERVICE_URL'] = $this->configVariables['BASE_SERVICE_URL'].'soap-1.0/service/document?wsdl';
		$this->configVariables['AGGREGATE_SERVICE_URL'] = $this->configVariables['BASE_SERVICE_URL'].'soap-1.0/service/aggregate?wsdl';
		$this->configVariables['LOAN_ACTION_SERVICE_URL'] = $this->configVariables['BASE_SERVICE_URL'].'soap-1.0/service/loanaction?wsdl';
		$this->configVariables['QUERY_SERVICE_URL'] = $this->configVariables['BASE_SERVICE_URL'].'soap-1.0/service/query?wsdl';
		$this->configVariables['QUERY_SERVICE_URL'] = $this->configVariables['BASE_SERVICE_URL'].'soap-1.0/query?wsdl';

		/**
		 * Application Site Redirection
		 */
		
		$this->configVariables['IS_HTTPS'] = FALSE;
		$this->configVariables['SITE_PREFIX'] = "";
		$this->configVariables['ENTERPRISE_SITE_URL'] = "";
		
		/**
		 * Application Service Authentication
		 */
		$this->configVariables['USE_WEB_SERVICES'] = TRUE;
		$this->configVariables['INSERT_WEB_SERVICES'] = FALSE;
		$this->configVariables['USE_WEB_SERVICES_READS'] = TRUE;
		$this->configVariables['LOG_SERVICE_RESPONSE'] = FALSE;
		$this->configVariables['LOG_SERVICE_REQUEST'] = FALSE;
		defineIf('LOG_SERVICE_RESPONSE', FALSE);
		defineIf('LOG_SERVICE_REQUEST', FALSE);
		$this->configVariables['APP_SERVICE_USER'] = 'aalm';
		$this->configVariables['APP_SERVICE_PASS'] = 'aalm';
		
		/**
		 * Application Service Config File Locations
		 */
		$this->configVariables['APP_SVC_IMPL_CFG'] = BASE_DIR . "/config/ApplicationService/implementations.xml";
		$this->configVariables['APP_SVC_OBS_CFG'] = BASE_DIR . "/config/ApplicationService/observers.xml";
		$this->configVariables['APP_SVC_VAL_CFG'] = BASE_DIR . "/config/ApplicationService/validators.xml";
		
		/**
		 * Application Service Settings
		 */
		$this->configVariables['APP_SVC_ENABLED'] = $this->configVariables['USE_WEB_SERVICES'];
		$this->configVariables['APP_SVC_THROW_NON_AUTH_EXCEPT'] = FALSE;
		$this->configVariables['VAPI_MEMCACHE_SERVERS'] = array(
			array('host' => 'localhost', 'port' => 11211),
		);

		/**
		 * Site config service URL
		 */
		$this->configVariables['SITE_CONFIG_SERVICE_URL'] = 'https://ecash-services.loanservicingcompany.com/siteconfig_service/Config.php?wsdl';

		/**
		 * MRS SMS service settings
		 */
		$this->configVariables['SMS_SERVICE_GOOD'] = false;
		$this->configVariables['SMS_SERVICE_URL'] = 'http://ws.mrsdirect.com/webservices/?wsdl&version=1.3.0';
		$this->configVariables['SMS_API_KEY'] = 'some_api_key';
		$this->configVariables['SMS_USERNAME'] = 'username';
		$this->configVariables['SMS_PASSWORD'] = 'password';
		$this->configVariables['SMS_SHORTCODE'] = '00000';
		$this->configVariables['SMS_KEYWORD'] = '000000';
		
		/**
		 * Direct email accounts
		 */
		$this->configVariables['EMAIL_HOST'] = 'someloancompany.smtp.com';
		$this->configVariables['EMAIL_PORT'] = 25;
		$this->configVariables['EMAIL_USER'] = '';
		$this->configVariables['EMAIL_PSWD'] = '';
	}
}
