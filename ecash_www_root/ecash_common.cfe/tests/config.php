<?php

define('BASE_DIR', realpath('../../ecash').'/');
define('LIB_DIR', BASE_DIR.'lib/');
define('ECASH_COMMON_DIR', realpath('../').'/');
define('COMMON_LIB_DIR', '/virtualhosts/lib/');

//require 'libolution/AutoLoad.1.php';
AutoLoad_1::addLoader(new AutoLoad_1('../code/'));

class_exists('DB_Database_1', true);

class TestingConfig extends ECash_Config
{
	protected function init()
	{	
		/**
		 * Database connections
		 */
		$this->configVariables['DB_MASTER_CONFIG'] = new TestingDBConfig();
		$this->configVariables['DB_SLAVE_CONFIG'] = $this->configVariables['DB_MASTER_CONFIG'];
	//	 	 $this->configVariables['FACTORY'] = new ECash_Factory($this->configVariables['ENTERPRISE_PREFIX'], $this->configVariables['DB_MASTER_CONFIG']);

		
		$this->configVariables['FACTORY'] = new ECash_Factory('ECash', $this->configVariables['DB_MASTER_CONFIG']);
	}
}

?>
