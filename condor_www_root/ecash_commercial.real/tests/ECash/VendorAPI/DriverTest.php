<?php

class ECash_VendorAPI_DriverTest extends PHPUnit_Framework_TestCase
{
	protected $_driver;

	public function setUp()
	{
		$db = $this->getMock('DB_Database_1', array(), array(), '', FALSE);
		$db_config = $this->getMock('DB_IDatabaseConfig_1');
		$factory = ECash_Factory::getFactory('', '', $db_config);
		$log = $this->getMock('Log_ILog_1');

		TestConfig::setFactory($factory);
		$config = ECash_Config::getInstance();

		$company_model = new ECash_Models_Company($db);
		$company_model->company_id = 1;
		$company_model->name_short = 'test';

		$company = $this->getMock('ECash_Company', array('getModel', 'getCompanyId'), array(), '', FALSE);
		$company->expects($this->any())->method('getCompanyId')->will($this->returnValue(1));
		$company->expects($this->any())->method('getModel')->will($this->returnValue($company_model));

		$this->_driver = new ECash_VendorAPI_Driver(
			$config,
			$factory,
			$db,
			$company,
			$log
		);
	}

	public function tearDown()
	{
		$this->_driver = NULL;
	}

	public function testGetActionReturnsOverriddenAction()
	{
		$action = $this->_driver->getAction('Qualify');
		$this->assertType('ECash_VendorAPI_Actions_Qualify', $action);
	}

	public function testGetActionReturnsBaseAction()
	{
		$action = $this->_driver->getAction('Noop');
		$this->assertEquals('VendorAPI_Actions_Noop', get_class($action));
	}

	public function testAuthenticatorImplementsIAuthenticator()
	{
		if (!defined('SESSION_EXPIRATION_HOURS'))
		{
			define('SESSION_EXPIRATION_HOURS', 1);
		}

		$auth = $this->_driver->getAuthenticator();
		$this->assertType('VendorAPI_IAuthenticator', $auth);
	}

	public function testGetDatabaseReturnsConnection()
	{
		$db = $this->_driver->getDatabase();
		$this->assertType('DB_IConnection_1', $db);
	}
}

?>
