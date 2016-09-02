<?php

require_once('Base.php');

/**
 * Execution Mode
 */
define('EXECUTION_MODE', 'LOCAL');

/**
 * Enterprise - ENVIRONMENT SPECIFIC - Overrides General
 */
class AALM_Config_Local extends AALM_Config_Base
{
	protected function init()
 	{
 		parent::init();

		/**
		 * Mode - Used for the log file directory
		 */
		$this->configVariables['mode'] =  'aalm_local';

		/**
		 * Common Library Directories
		 */
		if (!defined('COMMON_LIB_DIR')) define('COMMON_LIB_DIR', '/virtualhosts/lib/');
		if (!defined('COMMON_LIB_ALT_DIR')) define('COMMON_LIB_ALT_DIR', '/virtualhosts/lib5/');
		if (!defined('LIBOLUTION_DIR')) define('LIBOLUTION_DIR', '/virtualhosts/libolution/');

		/**
		 * Database connections
		 */
		$this->configVariables['DB_HOST'] = 'localhost';
		$this->configVariables['DB_NAME'] = 'ldb_generic';
		$this->configVariables['DB_USER'] = 'root';
		$this->configVariables['DB_PASS'] = '';
		$this->configVariables['DB_PORT'] = 3306;

		$this->configVariables['SLAVE_DB_HOST'] = 'localhost';
		$this->configVariables['SLAVE_DB_NAME'] = 'ldb_generic';
		$this->configVariables['SLAVE_DB_USER'] = 'root';
		$this->configVariables['SLAVE_DB_PASS'] = '';
		$this->configVariables['SLAVE_DB_PORT'] = 3306;

		$this->configVariables['API_DB_HOST'] = 'localhost';
		$this->configVariables['API_DB_NAME'] = 'ldb_generic';
		$this->configVariables['API_DB_USER'] = 'root';
		$this->configVariables['API_DB_PASS'] = '';
		$this->configVariables['API_DB_PORT'] = 3306;

		$this->configVariables['APPSERVICE_DB_HOST'] = 'devdb.chasm.ept.tss';
		$this->configVariables['APPSERVICE_DB_NAME'] = 'aalm';
		$this->configVariables['APPSERVICE_DB_USER'] = 'aalm_readonly';
		$this->configVariables['APPSERVICE_DB_PASS'] = 'Pej42tAp';
		$this->configVariables['APPSERVICE_DB_PORT'] = 1433;

		$this->configVariables['STATEOBJECT_DB_HOST'] = 'devdb.chasm.ept.tss';
		$this->configVariables['STATEOBJECT_DB_NAME'] = 'aalm';
		$this->configVariables['STATEOBJECT_DB_USER'] = 'dev';
		$this->configVariables['STATEOBJECT_DB_PASS'] = 'quCh83HE';
		$this->configVariables['STATEOBJECT_DB_PORT'] = 1433;
		
		$this->configVariables['STATUNIQUE_DB_HOST'] = 'devdb.chasm.ept.tss';
		$this->configVariables['STATUNIQUE_DB_NAME'] = 'aalm';
		$this->configVariables['STATUNIQUE_DB_USER'] = 'stats_writer';
		$this->configVariables['STATUNIQUE_DB_PASS'] = 'da39swUbrut4';
		$this->configVariables['STATUNIQUE_DB_PORT'] = 1433;
		
        /**
         * Database connections
         */
        $this->configVariables['DB_MASTER_CONFIG'] = new ECash_DB_CachedConfig(new DB_MySQLConfig_1(
                $this->configVariables['DB_HOST'],
                $this->configVariables['DB_USER'] ,
                $this->configVariables['DB_PASS'],
                $this->configVariables['DB_NAME'],
                $this->configVariables['DB_PORT']
                ));
         $this->configVariables['DB_SLAVE_CONFIG'] = new ECash_DB_CachedConfig(new DB_MySQLConfig_1(
                $this->configVariables['SLAVE_DB_HOST'],
                $this->configVariables['SLAVE_DB_USER'] ,
                $this->configVariables['SLAVE_DB_PASS'],
                $this->configVariables['SLAVE_DB_NAME'],
                $this->configVariables['SLAVE_DB_PORT']
                ));
         $this->configVariables['DB_API_CONFIG'] = new DB_MySQLConfig_1(
                $this->configVariables['API_DB_HOST'],
                $this->configVariables['API_DB_USER'] ,
                $this->configVariables['API_DB_PASS'],
                $this->configVariables['API_DB_NAME'],
                $this->configVariables['API_DB_PORT']
                );
         $this->configVariables['DB_APPSERVICE_CONFIG'] = new DB_MSSQLConfig_2(
                $this->configVariables['APPSERVICE_DB_HOST'],
                $this->configVariables['APPSERVICE_DB_USER'] ,
                $this->configVariables['APPSERVICE_DB_PASS'],
                $this->configVariables['APPSERVICE_DB_NAME'],
                $this->configVariables['APPSERVICE_DB_PORT']
                );
        $this->configVariables['DB_STATEOBJECT_CONFIG'] = new DB_MSSQLConfig_2(
                $this->configVariables['STATEOBJECT_DB_HOST'],
                $this->configVariables['STATEOBJECT_DB_USER'] ,
                $this->configVariables['STATEOBJECT_DB_PASS'],
                $this->configVariables['STATEOBJECT_DB_NAME'],
                $this->configVariables['STATEOBJECT_DB_PORT']
                );

		$this->configVariables['DB_STATUNIQUE_CONFIG'] = new DB_MSSQLConfig_2(
			$this->configVariables['STATUNIQUE_DB_HOST'],
			$this->configVariables['STATUNIQUE_DB_USER'] ,
			$this->configVariables['STATUNIQUE_DB_PASS'],
			$this->configVariables['STATUNIQUE_DB_NAME'],
			$this->configVariables['STATUNIQUE_DB_PORT']
		);		
 	
        /**
		 * Application Site Redirection
		 */
		$this->configVariables['SITE_PREFIX'] = "rc.";
		
        /**
         * QuickChecks Return Files Directory
         */
        if (!defined('QC_RETURN_FILE_DIR')) define('QC_RETURN_FILE_DIR', '/tmp/');

		$this->configVariables['FACTORY'] = new ECash_Factory(
			$this->configVariables['ENTERPRISE_PREFIX'], 
			$this->configVariables['DB_MASTER_CONFIG']
		);


 	}
}
