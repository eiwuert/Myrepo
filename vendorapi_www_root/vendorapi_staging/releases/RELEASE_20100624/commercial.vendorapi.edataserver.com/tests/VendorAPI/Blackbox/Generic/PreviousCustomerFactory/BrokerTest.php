<?php

/**
 * Tests the factory in a react configuration...
 *
 * This test checks the values of and is very dependent upon protected
 * variables; unfortunately, there's no way around it at this point.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Generic_PreviousCustomerFactory_BrokerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var VendorAPI_Blackbox_Rule_PreviousCustomer
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
	protected function getFactory(VendorAPI_IDriver $driver, VendorAPI_Blackbox_Config $config)
	{
		return VendorAPI_Blackbox_Generic_PreviousCustomerFactory::getInstance('test','test', $driver, $config, NULL);
	}

	public function testDeciderGetsCompany()
	{
		$decider = $this->readAttribute($this->_rule, 'decider');

		$this->assertAttributeEquals('test', 'company', $decider);
	}

	public function testActiveThreshold()
	{
		$decider = $this->readAttribute($this->_rule, 'decider');

		$this->assertAttributeEquals(0, 'active_threshold', $decider);
	}

	public function testDeniedTimeThreshold()
	{
		$decider = $this->readAttribute($this->_rule, 'decider');

		$this->assertAttributeEquals('-30 days', 'denied_time_threshold', $decider);
	}

	public function testDisagreedThreshold()
	{
		$decider = $this->readAttribute($this->_rule, 'decider');

		$this->assertAttributeEquals(0, 'disagreed_threshold', $decider);
	}

	public function testDisagreedTimeThreshold()
	{
		$decider = $this->readAttribute($this->_rule, 'decider');

		$this->assertAttributeEquals('-48 hours', 'disagreed_time_threshold', $decider);
	}

	public function testWithdrawnTimeThreshold()
	{
		$decider = $this->readAttribute($this->_rule, 'decider');

		$this->assertAttributeEquals(NULL, 'withdrawn_threshold', $decider);
	}

	public function testHasSSNRule()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rules = $this->getRuleTypes();
		$this->assertContains('VendorAPI_Blackbox_Rule_PreviousCustomer_SSN', $rules);
	}

	public function testHasEmailDobRule()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rules = $this->getRuleTypes();
		$this->assertContains('VendorAPI_Blackbox_Rule_PreviousCustomer_EmailDob', $rules);
	}

	public function testHasHomePhoneRule()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rules = $this->getRuleTypes();
		$this->assertContains('VendorAPI_Blackbox_Rule_PreviousCustomer_HomePhone', $rules);
	}

	public function testHasBankAccountDobRule()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rules = $this->getRuleTypes();
		$this->assertContains('VendorAPI_Blackbox_Rule_PreviousCustomer_BankAccountDob', $rules);
	}

	public function testHasLicenseRule()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rules = $this->getRuleTypes();
		$this->assertContains('VendorAPI_Blackbox_Rule_PreviousCustomer_License', $rules);
	}

	public function testSSNRuleCannotBeSkipped()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rule = $this->getRule('VendorAPI_Blackbox_Rule_PreviousCustomer_SSN');
		$this->assertAttributeEquals(NULL, 'skippable', $rule);
	}

	public function testEmailDobRuleCannotBeSkipped()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rule = $this->getRule('VendorAPI_Blackbox_Rule_PreviousCustomer_EmailDob');
		$this->assertAttributeEquals(NULL, 'skippable', $rule);
	}

	public function testHomePhoneRuleCannotBeSkipped()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rule = $this->getRule('VendorAPI_Blackbox_Rule_PreviousCustomer_HomePhone');
		$this->assertAttributeEquals(NULL, 'skippable', $rule);
	}

	public function testBankAccountDobRuleCannotBeSkipped()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rule = $this->getRule('VendorAPI_Blackbox_Rule_PreviousCustomer_BankAccountDob');
		$this->assertAttributeEquals(NULL, 'skippable', $rule);
	}

	public function testLicenseRuleCanBeSkipped()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rule = $this->getRule('VendorAPI_Blackbox_Rule_PreviousCustomer_License');
		$this->assertAttributeEquals(TRUE, 'skippable', $rule);
	}
}

?>
