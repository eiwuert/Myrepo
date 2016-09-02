<?php

require_once('base.php');

/**
 * Execution Mode
 */
define('EXECUTION_MODE', 'LIVE');

/**
 * Enterprise - ENVIRONMENT SPECIFIC - Overrides General
 */
class IMPACT_Config_Live extends IMPACT_Config_Base
{
	protected function init()
 	{
 		parent::init();

 		/**
		 * Mode - Used for the log file directory
		 */	
		$this->configVariables['mode'] =  'impact_live';

		/**
		 * Common Library Directories
		 */
		if (!defined('COMMON_LIB_DIR')) define('COMMON_LIB_DIR', '/virtualhosts/lib/');
		if (!defined('COMMON_LIB_ALT_DIR')) define('COMMON_LIB_ALT_DIR', '/virtualhosts/lib5/');
		if (!defined('LIBOLUTION_DIR')) define('LIBOLUTION_DIR', '/virtualhosts/libolution/');

		/**
		 * Database connections - Live Environment
		 */
		$this->configVariables['DB_HOST'] = 'writer.ecashimpact.ept.tss';
		$this->configVariables['DB_NAME'] = 'ldb_impact';
		$this->configVariables['DB_USER'] = 'ecash';
		$this->configVariables['DB_PASS'] = 'showmethemoney';
		$this->configVariables['DB_PORT'] = 3307;

		$this->configVariables['SLAVE_DB_HOST'] = 'reader.ecashimpact.ept.tss';
		$this->configVariables['SLAVE_DB_NAME'] = 'ldb_impact';
		$this->configVariables['SLAVE_DB_USER'] = 'ecash';
		$this->configVariables['SLAVE_DB_PASS'] = 'showmethemoney';
		$this->configVariables['SLAVE_DB_PORT'] = 3310;
		
		$this->configVariables['API_DB_HOST'] = 'reader.ecashimpactolp.ept.tss';
		$this->configVariables['API_DB_NAME'] = 'ldb_impact';
		$this->configVariables['API_DB_USER'] = 'ecash';
		$this->configVariables['API_DB_PASS'] = 'showmethemoney';
		$this->configVariables['API_DB_PORT'] = 3306;		

		/**
		 * Statistics database connection constants
		 */
		$this->configVariables['STAT_MYSQL_HOST'] =  'writer.ecashimpact.ept.tss:3307';
		$this->configVariables['STAT_MYSQL_USER'] =  'ecash';
		$this->configVariables['STAT_MYSQL_PASS'] =  'showmethemoney';

		/**
		 * Force Redirection to SSL
		 */
		$this->configVariables['FORCE_SSL_LOGIN'] = 'ON';
 		
 		$this->configVariables['MASTER_DOMAIN'] = 'master.funding.impactcashusa.com';
		$this->configVariables['LOAD_BALANCED_DOMAIN'] = 'master.funding.impactcashusa.com';
		$this->configVariables['COOKIE_DOMAIN'] = '.funding.impactcashusa.com';

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
		 * ACH Overrides
		 */
 		$this->configVariables['ACH_BATCH_SERVER'] = 'sftp.impactpayments.com';
 		$this->configVariables['ACH_BATCH_SERVER_PORT'] = 2020;
 		$this->configVariables['ACH_BATCH_URL'] = '/home/sellsource/deposits';
		
		/**
		 * Other data
		 */
		$this->configVariables['REACT_SOAP_KEY'] = 'b4b84688c8055f2896ed5b98843a7bf1';
		$this->configVariables['ECASH_APP'] = 'http://ecashapp.com/';
		$this->configVariables['REACT_SOAP_URL'] = 'http://internal.bfw.1.edataserver.com/cm_soap.php?wsdl';		

		/**
		 * Notification Recipients
		 * 
		 * NOTIFICATION_ERROR_RECIPIENTS is more for administrative use.  ACH processing errors, Alerts, etc.
		 * ECASH_NOTIFICATION_ERROR_RECIPIENTS is for Exceptions.
		 */
		$this->configVariables['NOTIFICATION_ERROR_RECIPIENTS'] = 'brian.ronald@sellingsource.com, brronald@gmail.com, ben.burkhart@sellingsource.com';
		$this->configVariables['ECASH_NOTIFICATION_ERROR_RECIPIENTS'] = 'brian.ronald@sellingsource.com, brronald@gmail.com, ben.burkhart@sellingsource.com';

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
         $this->configVariables['DB_API_CONFIG'] = new DB_MySQLConfig_1(
                $this->configVariables['API_DB_HOST'],
                $this->configVariables['API_DB_USER'] ,
                $this->configVariables['API_DB_PASS'],
                $this->configVariables['API_DB_NAME'],
                $this->configVariables['API_DB_PORT']
                ); 
                                
		$this->configVariables['FACTORY'] = ECash_Factory::getFactory($this->configVariables['CUSTOMER_CODE_DIR'], $this->configVariables['ENTERPRISE_PREFIX'], $this->configVariables['DB_MASTER_CONFIG']);

 	}
}
