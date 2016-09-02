<?php

require_once('Base.php');

/**
 * Execution Mode
 */
define('EXECUTION_MODE', 'LIVE');

/**
 * Enterprise - ENVIRONMENT SPECIFIC - Overrides General
 */
class MCC_Config_Live extends MCC_Config_Base
{
	protected function init()
 	{
 		parent::init();

		/**
		 * Mode - Used for the log file directory
		 */
		$this->configVariables['mode'] =  'mcc_live';

		/**
		 * Common Library Directories
		 */
		if (!defined('COMMON_LIB_DIR')) define('COMMON_LIB_DIR', '/virtualhosts/lib/');
		if (!defined('COMMON_LIB_ALT_DIR')) define('COMMON_LIB_ALT_DIR', '/virtualhosts/lib5/');
		if (!defined('LIBOLUTION_DIR')) define('LIBOLUTION_DIR', '/virtualhosts/libolution/');

		/**
		 * Database connections - Live Environment
		 */
		$this->configVariables['DB_HOST'] = 'writer.ecashaalm.ept.tss';
		$this->configVariables['DB_NAME'] = 'ldb_mcc';
		$this->configVariables['DB_USER'] = 'ecashmcc';
		$this->configVariables['DB_PASS'] = 'Gai9ahKa';
		$this->configVariables['DB_PORT'] = 3306;

		$this->configVariables['SLAVE_DB_HOST'] = 'reader.ecashaalm.ept.tss';
		$this->configVariables['SLAVE_DB_NAME'] = 'ldb_mcc';
		$this->configVariables['SLAVE_DB_USER'] = 'ecashmcc';
		$this->configVariables['SLAVE_DB_PASS'] = 'Gai9ahKa';
		$this->configVariables['SLAVE_DB_PORT'] = 3306;

		/**
		 * Statistics database connection constants
		 */
		$this->configVariables['STAT_MYSQL_HOST'] =  'reader.ecashaalm.ept.tss:3306';
		$this->configVariables['STAT_MYSQL_USER'] =  'ecashmcc';
		$this->configVariables['STAT_MYSQL_PASS'] =  'Gai9ahKa';

		/**
		 * QuickChecks Return Files Directory
		 */
		if (!defined('QC_RETURN_FILE_DIR')) define('QC_RETURN_FILE_DIR', '/tmp/');

		/**
		 * Paths to directories and files
		 */
		$this->configVariables['NSF_MAILER_DIR'] =  '/tmp/ecash3.0/ach_mailer';
		$this->configVariables['PDFLIB_LICENSE_FILE'] =  "/etc/pdflib/pdflib_licenses.txt";

		/**
		 * Force Redirection to SSL
		 */
		//$this->configVariables['FORCE_SSL_LOGIN'] = 'ON';

		/**
		 * Master DOMAIN, used for requests that MUST be executed on the same server
		 * ie, ach batches, quick check batches, etc and should be a subdomain of
		 * the load balanced domain.
		 */
		$this->configVariables['MASTER_DOMAIN'] = 'master.live.ecash.mycashcenter.com';
		$this->configVariables['LOAD_BALANCED_DOMAIN'] = 'master.live.ecash.mycashcenter.com';
		$this->configVariables['COOKIE_DOMAIN'] = '.mycashcenter.com';

		/**
		 * ACH Overrides
		 */
		$this->configVariables['ACH_BATCH_SERVER'] = 'mft.moneygram.com';
		$this->configVariables['ACH_BATCH_LOGIN']  = 'mycashcenter';
		$this->configVariables['ACH_BATCH_PASS']   = '8UWef8PR';
		$this->configVariables['ACH_BATCH_URL'] = '/tranfile';

		$this->configVariables['ACH_RECIPIENT'] = "transmissionteam@moneygram.com";


		$this->configVariables['ACH_BATCH_NOTIFY_LIST'] = 'processing@moneygram.com';


		$this->configVariables['NOTIFICATION_ERROR_RECIPIENTS'] = 'brian.ronald@sellingsource.com, brronald@gmail.com, richard.bunce@sellingsource.com, nita.tune@sellingsource.com, ben.burkhart@sellingsource.com';

		/**
		 * Other data FIXME
		 */
		$this->configVariables['REACT_SOAP_KEY'] = 'b4b84688c8055f2896ed5b98843a7bf1 ';
		$this->configVariables['ECASH_APP'] = 'http://ecashapp.com/';
		$this->configVariables['REACT_SOAP_URL'] = 'http://bfw.1.edataserver.com/cm_soap.php?wsdl';
        /**
         * Database connections
         */
        $this->configVariables['DB_MASTER_CONFIG'] = new DB_MySQLConfig_1(
                $this->configVariables['DB_HOST'],
                $this->configVariables['DB_USER'] ,
                $this->configVariables['DB_PASS'],
                $this->configVariables['DB_NAME'],
                $this->configVariables['DB_PORT']
                );
         $this->configVariables['DB_SLAVE_CONFIG'] = new DB_MySQLConfig_1(
                $this->configVariables['SLAVE_DB_HOST'],
                $this->configVariables['SLAVE_DB_USER'] ,
                $this->configVariables['SLAVE_DB_PASS'],
                $this->configVariables['SLAVE_DB_NAME'],
                $this->configVariables['SLAVE_DB_PORT']
                );

		/**
		 * Factory
		 */
       $this->configVariables['FACTORY'] = ECash_Factory::getFactory($this->configVariables['CUSTOMER_CODE_DIR'], $this->configVariables['ENTERPRISE_PREFIX'], $this->configVariables['DB_MASTER_CONFIG']);


 	}
}
