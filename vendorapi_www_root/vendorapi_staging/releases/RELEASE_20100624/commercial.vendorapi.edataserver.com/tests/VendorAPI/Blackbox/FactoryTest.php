<?php

/**
 * Tests VendorAPI Blackbox Factory.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class VendorAPI_Blackbox_FactoryTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that we get a Blackbox_RuleCollection back from getRuleCollection.
	 *
	 * @return void
	 */
	public function testGetRuleCollectionBroker()
	{
		$config = new VendorAPI_Blackbox_Config();
		$config->blackbox_mode = VendorAPI_Blackbox_Config::MODE_BROKER;
		$config->event_log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);

		$db = $this->getMock('DB_IConnection_1');
		$driver = $this->getMock('VendorAPI_IDriver');
		$driver->expects($this->any())
			->method('getDatabase')
			->will($this->returnValue($db));

		$rule_factory = $this->getMock(
			'VendorAPI_Blackbox_Rule_Factory',
			array('getPreviousCustomerRule', 'getDataX', 'getUsedInfoRule', 'dataxRecur','getSuppressionRule'),
			array($driver, $config, NULL)
		);
		$rule_factory->expects($this->once())
			->method('getPreviousCustomerRule')
			->will($this->returnValue($this->getMock('Blackbox_Rule', array('canRun', 'runRule'))));
		$rule_factory->expects($this->once())
			->method('getDataX')
			->will($this->returnValue($this->getMock('Blackbox_Rule', array('canRun', 'runRule'))));
		$rule_factory->expects($this->once())
			->method('getUsedInfoRule')->will($this->returnValue($this->getMock('Blackbox_Rule', array('canRun', 'runRule'))));
		$rule_factory->expects($this->once())
			->method('dataxRecur')->will($this->returnValue($this->getMock('Blackbox_Rule', array('canRun', 'runRule'))));
		$rule_factory->expects($this->once())
			->method('getSuppressionRule')
			->will($this->returnValue($this->getMock('Blackbox_Rule', array('canRun', 'runRule'))));
			
		$blackbox_factory = new VendorAPI_Blackbox_Factory($driver, $config, $rule_factory);
		
		$state = new VendorAPI_Blackbox_StateData(array('customer_history' => new Ecash_CustomerHistory()));
		$state->customer_history = new Ecash_CustomerHistory();

		$rule_collection = $blackbox_factory->getRuleCollection(FALSE, $state);

		$this->assertType('Blackbox_IRuleCollection', $rule_collection);
	}

	public function testGetRuleCollectionReact()
	{
		$config = new VendorAPI_Blackbox_Config();
		$config->blackbox_mode = VendorAPI_Blackbox_Config::MODE_ECASH_REACT;
		$config->event_log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);

		$db = $this->getMock('DB_IConnection_1');
		$driver = $this->getMock('VendorAPI_IDriver');
		$driver->expects($this->any())
			->method('getDatabase')
			->will($this->returnValue($db));


		$rule_factory = $this->getMock(
			'VendorAPI_Blackbox_Rule_Factory',
			array('getPreviousCustomerRule', 'getDataX', 'getUsedInfoRule', 'dataxRecur','getSuppressionRule'),
			array($driver, $config, NULL)
		);
		$rule_factory->expects($this->once())
			->method('getPreviousCustomerRule')
			->will($this->returnValue($this->getMock('Blackbox_Rule', array('canRun', 'runRule'))));
		$rule_factory->expects($this->once())
			->method('getDataX')
			->will($this->returnValue(NULL));
		$rule_factory->expects($this->once())
			->method('getUsedInfoRule')->will($this->returnValue(NULL));
		$rule_factory->expects($this->once())
			->method('dataxRecur')->will($this->returnValue($this->getMock('Blackbox_Rule', array('canRun', 'runRule'))));
		$rule_factory->expects($this->once())
			->method('getSuppressionRule')
			->will($this->returnValue($this->getMock('Blackbox_Rule', array('canRun', 'runRule'))));
			
		$blackbox_factory = new VendorAPI_Blackbox_Factory($driver, $config, $rule_factory);

		$state = new VendorAPI_Blackbox_StateData(array('customer_history' => new Ecash_CustomerHistory()));
		$state->customer_history = new Ecash_CustomerHistory();

		$rule_collection = $blackbox_factory->getRuleCollection(FALSE, $state);
		
		$this->assertType('Blackbox_IRuleCollection', $rule_collection);
	}
	/**
	 * Tests that we get a Blackbox object back from getBlackbox.
	 *
	 * @return void
	 */
	public function testGetBlackbox()
	{
		$config = new VendorAPI_Blackbox_Config();

		$db = $this->getMock('DB_IConnection_1');
		$driver = $this->getMock('VendorAPI_IDriver');
		$driver->expects($this->any())
			->method('getDatabase')
			->will($this->returnValue($db));

		$rule_factory = $this->getMock(
			'VendorAPI_Blackbox_Rule_Factory',
			array(),
			array($driver, $config, NULL)
		);

		$blackbox_factory = $this->getMock('VendorAPI_Blackbox_Factory', array('getRuleCollection'), array($driver, $config, $rule_factory));
		$blackbox_factory->expects($this->once())
			->method('getRuleCollection')
			->will($this->returnValue($this->getMock('Blackbox_Rule', array('canRun', 'runRule'))));

		$blackbox = $blackbox_factory->getBlackbox(
			$this->getMock('VendorAPI_Blackbox_DataX_CallHistory')
		);

		$this->assertType('Blackbox_Root', $blackbox);
	}

}

?>
