<?php
/**
 * Tests the changes to the OPM factory
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_Blackbox_OPM_PreviousCustomerFactory_BrokerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var VendorAPI_Blackbox_PreviousCustomerCollection
	 */
	protected $_rule;

	protected function setUp()
	{
		$driver = $this->getMock('VendorAPI_IDriver');

		$db = $this->getMock('DB_IConnection_1');
		$driver->expects($this->any())
			->method('getDatabase')
			->will($this->returnValue($db));

		$driver->expects($this->any())
			->method('getAppClient')
			->will($this->returnValue($this->getMock('WebServices_Client_AppClient', array(), array(), '', FALSE)));

		$config = new VendorAPI_Blackbox_Config();
		$config->company = 'test';
		$config->is_enterprise = FALSE;
		$config->is_react = FALSE;
		$config->blackbox_mode = VendorAPI_Blackbox_Config::MODE_BROKER;
		$config->event_log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);

		$factory = $this->getFactory($driver, $config);
		$this->_rule = $factory->getPreviousCustomerRule($this->getMock('ECash_CustomerHistory', array(), array(), '', FALSE));
	}

	protected function tearDown()
	{
		$this->_rule = NULL;
	}
	
	/**
	 * Get the previous customer factory for this test
	 *
	 * @param VendorAPI_IDriver $driver
	 * @param VendorAPI_Blackbox_Config $config
	 * @return VendorAPI_Blackbox_Generic_PreviousCustomerFactory
	 */
	protected function getFactory($driver, $config)
	{
		return VendorAPI_Blackbox_Generic_PreviousCustomerFactory::getInstance('OPM','test', $driver, $config, NULL);
	}

	/**
	 * @see VendorAPI_Blackbox_Generic_PreviousCustomerFactory_BrokerTest#testDeniedTimeThreshold
	 * @return void
	 */
	public function testDeniedTimeThreshold()
	{
		$decider = $this->readAttribute($this->_rule, 'decider');

		$this->assertAttributeEquals('-60 days', 'denied_time_threshold', $decider);
	}

	/**
	 * @see VendorAPI_Blackbox_Generic_PreviousCustomerFactory_BrokerTest#testDisagreedTimeThreshold
	 * @return void
	 */
	public function testDisagreedTimeThreshold()
	{
		$decider = $this->readAttribute($this->_rule, 'decider');

		$this->assertAttributeEquals('-60 days', 'disagreed_time_threshold', $decider);
	}

	/**
	 * @see VendorAPI_Blackbox_Generic_PreviousCustomerFactory_BrokerTest#testWithdrawnTimeThreshold
	 * @return void
	 */
	public function testWithdrawnTimeThreshold()
	{
		$decider = $this->readAttribute($this->_rule, 'decider');

		$this->assertAttributeEquals('-60 days', 'withdrawn_threshold', $decider);
	}
}

?>
