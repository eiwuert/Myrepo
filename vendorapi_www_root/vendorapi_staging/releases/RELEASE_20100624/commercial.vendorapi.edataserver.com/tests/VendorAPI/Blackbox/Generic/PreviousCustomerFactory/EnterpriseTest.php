<?php

/**
 * Tests the factory in an enterprise configuration...
 *
 * This test checks the values of and is very dependent upon protected
 * variables; unfortunately, there's no way around it at this point.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Generic_PreviousCustomerFactory_EnterpriseTest extends PHPUnit_Framework_TestCase
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
		$config->is_enterprise = TRUE;
		$config->is_react = FALSE;
		$config->event_log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);

		$factory = new VendorAPI_Blackbox_Generic_PreviousCustomerFactory('test', $driver, $config, NULL);
		$this->_rule = $factory->getPreviousCustomerRule($this->getMock('ECash_CustomerHistory', array(), array(), '', FALSE));
	}

	protected function tearDown()
	{
		$this->_rule = NULL;
	}

	public function testProviderChecksAllCompanies()
	{
		$this->markTestIncomplete('Needs to instead test the loader');
		$provider = $this->readAttribute($this->_rule, 'provider');
		$this->assertAttributeEquals(array('test'), 'companies', $provider);
	}

	public function testWillNotExpireApplications()
	{
		$this->markTestIncomplete('Expiring apps is different now, the collection isn\'t used');
		$this->assertFalse($this->_rule->getExpireApplications());
	}

	public function testSSNRuleCanSetSingleCompany()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rule = $this->getRule('VendorAPI_Blackbox_Rule_PreviousCustomer_SSN');
		$this->assertAttributeEquals('test', 'enterprise', $rule);
	}

	public function testEmailDobRuleCanSetSingleCompany()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rule = $this->getRule('VendorAPI_Blackbox_Rule_PreviousCustomer_EmailDob');
		$this->assertAttributeEquals('test', 'enterprise', $rule);
	}

	public function testHomePhoneRuleCannotSetSingleCompany()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rule = $this->getRule('VendorAPI_Blackbox_Rule_PreviousCustomer_HomePhone');
		$this->assertAttributeEquals(NULL, 'enterprise', $rule);
	}

	public function testBankAccountDobRuleCannotSetSingleCompany()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rule = $this->getRule('VendorAPI_Blackbox_Rule_PreviousCustomer_BankAccountDob');
		$this->assertAttributeEquals(NULL, 'enterprise', $rule);
	}

	public function testLicenseRuleCannotSetSingleCompany()
	{
		$this->markTestIncomplete('This has all changed, the criteria now need to be tested');
		$rule = $this->getRule('VendorAPI_Blackbox_Rule_PreviousCustomer_License');
		$this->assertAttributeEquals(NULL, 'enterprise', $rule);
	}
}

?>
