<?php

/**
 * Tests the factory in a react configuration...
 *
 * This test checks the values of and is very dependent upon protected
 * variables; unfortunately, there's no way around it at this point.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_CLK_PreviousCustomerFactory_BrokerTest extends PHPUnit_Framework_TestCase
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
		$config->company = 'ca';
		$config->is_enterprise = FALSE;
		$config->is_react = FALSE;
		$config->blackbox_mode = VendorAPI_Blackbox_Config::MODE_BROKER;
		$config->event_log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);

		$factory = new VendorAPI_Blackbox_CLK_PreviousCustomerFactory('test', $driver, $config, NULL);
		$this->_rule = $factory->getPreviousCustomerRule($this->getMock('ECash_CustomerHistory', array(), array(), '', FALSE));
	}

	protected function tearDown()
	{
		$this->_rule = NULL;
	}

	public function testDisagreedThresholdIsOne()
	{
		$decider = $this->readAttribute($this->_rule, 'decider');

		$this->assertAttributeEquals(1, 'disagreed_threshold', $decider);
	}

	public function testDisagreedTimeThresholdIs24Hours()
	{
		$decider = $this->readAttribute($this->_rule, 'decider');

		$this->assertAttributeEquals('-24 hours', 'disagreed_time_threshold', $decider);
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

	public function testHomePhoneDobRuleCannotBeSkipped()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rule = $this->getRule('VendorAPI_Blackbox_Rule_PreviousCustomer_HomePhoneDob');
		$this->assertAttributeEquals(NULL, 'skippable', $rule);
	}

	public function testBankAccountDobRuleCannotBeSkipped()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rule = $this->getRule('VendorAPI_Blackbox_Rule_PreviousCustomer_BankAccountDob');
		$this->assertAttributeEquals(NULL, 'skippable', $rule);
	}

	public function testBankAccountRuleCannotBeSkipped()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rule = $this->getRule('VendorAPI_Blackbox_Rule_PreviousCustomer_BankAccount');
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
