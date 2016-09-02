<?php

/**
 * Tests the getInstance static method on the previous customer factory
 * Check the PreviousCustomerFactory/ directory for more tests
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Generic_PreviousCustomerFactoryTest extends PHPUnit_Framework_TestCase
{
	protected $_config;
	protected $_driver;

	public function setUp()
	{
		$log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);
		
		$this->_config = new VendorAPI_Blackbox_Config();
		$this->_config->event_log = $log;

		$this->_driver = $this->getMock('VendorAPI_IDriver');
	}

	public function tearDown()
	{
		$this->_config = NULL;
		$this->_driver = NULL;
	}

	public function testSCNInstance()
	{
		$f = VendorAPI_Blackbox_Generic_PreviousCustomerFactory::getInstance('scn', 'generic', $this->_driver, $this->_config, NULL);
		$this->assertType('VendorAPI_Blackbox_PreviousCustomerFactory', $f);
	}
}

?>
