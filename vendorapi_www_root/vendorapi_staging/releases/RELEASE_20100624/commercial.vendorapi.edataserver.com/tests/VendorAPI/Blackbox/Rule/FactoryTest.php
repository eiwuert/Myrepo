<?php
/**
 * Tests the Blackbox rule factory.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_FactoryTest extends PHPUnit_Framework_TestCase
{
	protected $_config;
	protected $_debug;
	protected $_factory;
	protected $_driver;
	protected $_db;
	protected $_app_client;

	public function setUp()
	{
		$this->_driver = $this->getMock('VendorAPI_IDriver');

		$this->_debug = new VendorAPI_Blackbox_DebugConfig();

		$this->_config = new VendorAPI_Blackbox_Config($this->_debug);
		$this->_config->blackbox_mode = VendorAPI_Blackbox_Config::MODE_BROKER;

		$this->_config->event_log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);

		$call = $this->getMock('TSS_DataX_Call', array('execute'), array(), '', FALSE);

		$this->_db = $this->getMock('DB_IConnection_1');
		
		$this->_app_client = $this->getMock('WebServices_Client_AppClient', array(), array(), '', FALSE);

		$this->_driver->expects($this->any())
			->method('getDatabase')
			->will($this->returnValue($this->_db));

		$this->_driver->expects($this->any())
			->method('getDatabase')
			->will($this->returnValue($this->_db));

		$this->_driver->expects($this->any())
			->method('getDataXCall')
			->will($this->returnValue($call));
		
		$this->_driver->expects($this->any())
			->method('getAppClient')
			->will($this->returnValue($this->_app_client));
		
		$this->_factory = $this->getFactory();
	}

	public function tearDown()
	{
		$this->_config = NULL;
		$this->_debug = NULL;
		$this->_factory = NULL;
		$this->_driver = NULL;
		$this->_db = NULL;
		$this->_app_client = NULL;
	}
	
	/**
	 * Get the factory class for these tests
	 *
	 * @return VendorAPI_Blackbox_Rule_Factory
	 */
	protected function getFactory()
	{
		return new VendorAPI_Blackbox_Rule_FactoryTest_RuleFactory(
			$this->_driver,
			$this->_config,
			0
		);
	}
	
	public function testUsedInfoRuleIsNullInNonBrokerMode()
	{
		unset($this->_config->blackbox_mode);
		$this->_config->blackbox_mode = VendorAPI_Blackbox_Config::MODE_AGREE;

		$this->assertNull($this->_factory->getUsedInfoRule());
	}

	public function testPreviousCustomerRuleIsSkippedWithDebugFlag()
	{
		$this->_debug->setFlag(VendorAPI_Blackbox_DebugConfig::PREV_CUSTOMER, FALSE);
		$customer_history = $this->getMock('ECash_CustomerHistory');
		$rule = $this->_factory->getPreviousCustomerRule($customer_history);
		$this->assertType('VendorAPI_Blackbox_Rule_Skip', $rule);
	}


	public function testUsedInfoRuleIsSkippedWithDebugFlag()
	{
		$this->_debug->setFlag(VendorAPI_Blackbox_DebugConfig::USED_INFO, FALSE);

		$rule = $this->_factory->getUsedInfoRule();
		$this->assertType('VendorAPI_Blackbox_Rule_Skip', $rule);
	}

	public function testDataXRuleIsSkippedWithDebugFlag()
	{
		$this->_debug->setFlag(VendorAPI_Blackbox_DebugConfig::DATAX, FALSE);

		$rule = $this->_factory->getDataX(FALSE);
		$this->assertType('VendorAPI_Blackbox_Rule_Skip', $rule);
	}

	public function testDataXRuleIsNotSkippedWithDebugFlagAndSkipCheckOff()
	{
		$this->_debug->setFlag(VendorAPI_Blackbox_DebugConfig::DATAX, FALSE);

		
		$rule = $this->_factory->getDataX(FALSE, TRUE);
		$this->assertType('VendorAPI_Blackbox_Rule_DataX', $rule);
	}

	public function testSuppresionListRulesAreSkippedWithDebugFlag()
	{
		$this->_debug->setFlag(VendorAPI_Blackbox_DebugConfig::SUPPRESSION_LISTS, FALSE);

		$rule = $this->_factory->getSuppressionListRule();
		$this->assertType('VendorAPI_Blackbox_Rule_Skip', $rule);
	}

	/**
	 * Tests that we can get a previous customer rule.
	 *
	 * @return void
	 */
	public function testgetPreviousCustomerRule()
	{
		$customer_history = $this->getMock('ECash_CustomerHistory');
		$this->assertType('Blackbox_IRule', $this->_factory->getPreviousCustomerRule($customer_history));
	}

	/**
	 * Test that we get the DataX rule returned.
	 *
	 * @return void
	 */
	public function testgetDataXRule()
	{
		$rule = $this->_factory->getDataX(FALSE);
		$this->assertType('VendorAPI_Blackbox_Rule_DataX', $rule);
	}
	
	public function testGetSuppressionListRuleForNonReact()
	{
		$rules = $this->_factory->getSuppressionListRule();
		$this->assertType('Blackbox_RuleCollection', $rules,
			'Unexpected return type');
		$this->assertEquals(4, $rules->count());
		
		$verfy_rules = 0;
		$exclude_rules = 0;
		$restrict_rules = 0;
		
		foreach ($rules->getIterator() as $rule)
		{
			if ($rule instanceof VendorAPI_Blackbox_Rule_Suppression_Verify)
			{
				$this->assertEquals('LIST_VERIFY_NON-REACT VERIFY LIST', $rule->getEventName(),
					'Unexpected verify rule name');
				$verfy_rules++;
			}
			elseif ($rule instanceof VendorAPI_Blackbox_Rule_Suppression_Exclude)
			{
				$this->assertTrue(
					in_array($rule->getEventName(),
						array('LIST_EXCLUDE_NON-REACT EXCLUDE LIST 1', 'LIST_EXCLUDE_NON-REACT EXCLUDE LIST 2')),
					'Unexpected event name for rule: ' . $rule->getEventName());	
				$exclude_rules++;			
			}
			elseif ($rule instanceof VendorAPI_Blackbox_Rule_Suppression_Restrict)
			{
				$this->assertEquals('LIST_RESTRICT_NON-REACT RESTRICT LIST', $rule->getEventName(),
					'Unexpected verify rule name');
				$restrict_rules++;
			}
			else
			{
				$this->fail("Not expecting rule of type " . get_class($rule));
			}
			
		}
		$this->assertEquals(1, $verfy_rules, 'Unexpected number of verify rules returned');
		$this->assertEquals(2, $exclude_rules, 'Unexpected number of exclude rules returned');
		$this->assertEquals(1, $restrict_rules, 'Unexpected number of restrict rules returned');
	}
		
	public function testGetSuppressionListRuleForReact()
	{
		$this->_config = new VendorAPI_Blackbox_Config($this->_debug);
		$this->_config->blackbox_mode = VendorAPI_Blackbox_Config::MODE_ECASH_REACT;
		$this->_config->event_log = $this->getMock('VendorAPI_Blackbox_EventLog', array(), array(), '', FALSE);
		
		$this->_factory = $this->getFactory();
		
		$rules = $this->_factory->getSuppressionListRule();
		$this->assertType('Blackbox_RuleCollection', $rules,
			'Unexpected return type');
		$this->assertEquals(4, $rules->count());
		
		$verfy_rules = 0;
		$exclude_rules = 0;
		$restrict_rules = 0;
		
		foreach ($rules->getIterator() as $rule)
		{
			if ($rule instanceof VendorAPI_Blackbox_Rule_Suppression_Verify)
			{
				$this->assertEquals('LIST_VERIFY_REACT VERIFY LIST', $rule->getEventName(),
					'Unexpected verify rule name');
				$verfy_rules++;
			}
			elseif ($rule instanceof VendorAPI_Blackbox_Rule_Suppression_Exclude)
			{
				$this->assertTrue(
					in_array($rule->getEventName(),
						array('LIST_EXCLUDE_REACT EXCLUDE LIST 1', 'LIST_EXCLUDE_REACT EXCLUDE LIST 2')),
					'Unexpected event name for rule: ' . $rule->getEventName());	
				$exclude_rules++;			
			}
			elseif ($rule instanceof VendorAPI_Blackbox_Rule_Suppression_Restrict)
			{
				$this->assertEquals('LIST_RESTRICT_REACT RESTRICT LIST', $rule->getEventName(),
					'Unexpected verify rule name');
				$restrict_rules++;
			}
			else
			{
				$this->fail("Not expecting rule of type " . get_class($rule));
			}
			
		}
		$this->assertEquals(1, $verfy_rules, 'Unexpected number of verify rules returned');
		$this->assertEquals(2, $exclude_rules, 'Unexpected number of exclude rules returned');
		$this->assertEquals(1, $restrict_rules, 'Unexpected number of restrict rules returned');
	}
}

class VendorAPI_Blackbox_Rule_FactoryTest_RuleFactory extends VendorAPI_Blackbox_Rule_Factory
{
	protected function getBrokerSuppressionLists()
	{
		return array(
			'VERIFY' => array(
				'non-react verify list',
			),
			'EXCLUDE' => array(
				'non-react exclude list 1',
				'non-react exclude list 2',
			),
			'RESTRICT' => array(
				'non-react restrict list',
			),
		);
	}
	
	protected function getReactSuppressionLists()
	{
		return array(
			'VERIFY' => array(
				'react verify list',
			),
			'EXCLUDE' => array(
				'react exclude list 1',
				'react exclude list 2',
			),
			'RESTRICT' => array(
				'react restrict list',
			),
		);
	}
}

?>
