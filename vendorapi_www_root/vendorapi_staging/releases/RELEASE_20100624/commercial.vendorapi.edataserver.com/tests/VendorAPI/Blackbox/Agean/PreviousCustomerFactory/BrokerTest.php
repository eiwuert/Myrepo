<?php

/**
 * Tests the factory in a react configuration...
 *
 * This test checks the values of and is very dependent upon protected
 * variables; unfortunately, there's no way around it at this point.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Agean_PreviousCustomerFactory_BrokerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var VendorAPI_Blackbox_Rule_PreviousCustomer
	 */
	protected $_rule;

	protected function setUp()
	{
		$this->markTestIncomplete('We need to move these customer specific overrides OUT of vendor api and into the customer modules');
		$driver = $this->getMock('VendorAPI_IDriver');

		$db = $this->getMock('DB_IConnection_1');
		$driver->expects($this->any())
			->method('getDatabase')
			->will($this->returnValue($db));

		$config = new VendorAPI_Blackbox_Config();
		$config->company = 'test';
		$config->is_enterprise = FALSE;
		$config->is_react = FALSE;
		$config->blackbox_mode = VendorAPI_Blackbox_Config::MODE_BROKER;
		$config->event_log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);

		$factory = new VendorAPI_Blackbox_Agean_PreviousCustomerFactory('test', $driver, $config, NULL);
		$this->_rule = $factory->getPreviousCustomerRule($this->getMock('ECash_CustomerHistory', array(), array(), '', FALSE));
	}

	protected function tearDown()
	{
		$this->_rule = NULL;
	}

	public function testRuleHasAgeanProvider()
	{
		$provider = $this->readAttribute($this->_rule, 'provider');

		$this->assertType('VendorAPI_Blackbox_Agean_ECashProvider', $provider);
	}
}

?>
