<?php

/**
 * Test the rule collection class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 */
class OLPBlackbox_RuleCollectionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * A factory which produces common fixtures for the EventBus subpackage.
	 *
	 * @var OLPBlackbox_Test_EventFixtureFactory
	 */
	protected $fixture_factory;
	
	/**
	 * Set up RuleCollectionTest.
	 * @return void
	 */
	public function setUp()
	{
		$this->fixture_factory = new OLPBlackbox_Test_EventFixtureFactory();
	}
	
	// --------------------------test methods-----------------------------------
	
	/**
	 * Test event bus events being sent properly and collection timing out.
	 *
	 * @dataProvider isValidEventsProvider
	 * @param array $expected_events List of events we expect to be sent.
	 * @param array $unexpected_events List of events we do not expect to see.
	 * @return void
	 */
	public function testIsValidEvents($expected_events, $unexpected_events)
	{
		$config = $this->freshConfigWithEventBus();
		$collector = $this->freshCollectorForAllEventsOn($config->event_bus);
		$collection = $this->freshCollectionWithConfig($config);
		$this->addDummyRulesToCollection(2, $collection, $config);
		
		// actual method we're interested in running, isValid()
		$collection->isValid(new OLPBlackbox_Data(), new OLPBlackbox_StateData());
		
		foreach ($expected_events as $expected_event)
		{
			$found = (bool)count($collector->findEventsByAttributes($expected_event->getAttrs()));
			$this->assertTrue($found, "Expected the event {$expected_event} at least once.");
		}
		foreach ($unexpected_events as $unexpected_event)
		{
			$found = (bool)count($collector->findEventsByAttributes($unexpected_event->getAttrs()));
			$this->assertFalse($found, "Did not expect to receive {$unexpected_event}.");
		}
	}
	
	/**
	 * Data provider for {@see OLPBlackbox_RuleCollectionTest::testIsValidEvents()}
	 *
	 * @return void
	 */
	public static function isValidEventsProvider()
	{
		$events = array(
			new OLPBlackbox_Event(OLPBlackbox_Event::EVENT_VALIDATION_START, 
				array(OLPBlackbox_Event::ATTR_SENDER => OLPBlackbox_Event::TYPE_RULE_COLLECTION)),
			new OLPBlackbox_Event(OLPBlackbox_Event::EVENT_VALIDATION_END, 
				array(OLPBlackbox_Event::ATTR_SENDER => OLPBlackbox_Event::TYPE_RULE_COLLECTION)),
			new OLPBlackbox_Event(OLPBlackbox_Event::EVENT_NEXT_RULE,
				array(OLPBlackbox_Event::ATTR_SENDER => OLPBlackbox_Event::TYPE_RULE_COLLECTION)),
		);
		
		return array(
			array($events, array()),
		);
	}
	
	/**
	 * Test that if a timeout is sent to a rule collection which has subscribed
	 * to that event bus, that subsequent rules do not get run and the collection
	 * returns invalid.
	 *
	 * @return void
	 */
	public function testTimeoutDuringRun()
	{
		$config = $this->freshConfigWithEventBus();
		$collection = $this->freshCollectionWithConfig($config);
		
		// first test that we complete an isValid run with just 2 dummy rules
		$this->addDummyRulesToCollection(2, $collection, $config);
		
		$is_valid = $collection->isValid(new OLPBlackbox_Data(), new OLPBlackbox_StateData());
		
		$this->assertTrue($is_valid, "Rule collection with 2 dummy rules failed!");
		foreach ($collection as $rule)
		{
			$this->assertTrue($rule->wasRun(), "Rule ($rule) wasn't run!");
		}
		$config->event_bus->unsubscribe($collection);
		
		
		// NOW test that if a timeout happens during the first rule, the second 
		// one isn't run and the collection fails.
		$collection = $this->freshCollectionWithConfig($config);
		$this->addDummyRulesToCollection(2, $collection, $config);
		$collection->getRuleAtIndex(0)->send_timeout = TRUE;
		$config->event_bus->subscribeTo(OLPBlackbox_Event::EVENT_BLACKBOX_TIMEOUT, $collection);
		
		// actual method we're testing
		$is_valid = $collection->isValid(new OLPBlackbox_Data(), new OLPBlackbox_StateData());
		
		// assertations
		$this->assertFalse(
			$is_valid,
			"Rule collection timed out but still returned valid."
		);
		$this->assertTrue(
			$collection->getRuleAtIndex(0)->wasRun(), 
			"First rule wasn't run, but was expected to be."
		);
		$this->assertFalse(
			$collection->getRuleAtIndex(1)->wasRun(), 
			"Second rule was run but shouldn't have been."
		);
	}
	
	/**
	 * Test that even if invalidated BEFORE a call to isValid() that a rule collection
	 * will return invalid.
	 *
	 * @return void
	 */
	public function testInvalidatedBeforeRun()
	{
		$config = $this->freshConfigWithEventBus();
		$collection = $this->freshCollectionWithConfig($config);
		$this->addDummyRulesToCollection(1, $collection, $config);
		$collection->notify(new OLPBlackbox_Event(OLPBlackbox_Event::EVENT_BLACKBOX_TIMEOUT));
		
		$is_valid = $collection->isValid(new OLPBlackbox_Data(), new OLPBlackbox_StateData());
		
		$this->assertFalse(
			$is_valid, "Collection was notified of timeout, but remained valid."
		);
	}
	
	// -------------------------non test methods--------------------------------
	
	/**
	 * Adds a number of dummy rules to a collection.
	 *
	 * @param int $number The number of rules to add.
	 * @param OLPBlackbox_RuleCollection $collection The rule collection to add
	 * the dummy rules to.
	 * @param OLPBlackbox_Config $config An optional config to assign to the 
	 * dummy rules.
	 * @return void
	 */
	protected function addDummyRulesToCollection(
		$number, 
		OLPBlackbox_RuleCollection $collection, 
		OLPBlackbox_Config $config = NULL
	)
	{
		for ($i=0; $i<$number; ++$i)
		{
			$attrs = array();
			if ($config instanceof OLPBlackbox_Config)
			{
				$attrs['blackbox_config'] = $config;
			}
			$rule = new OLPBlackbox_Test_DummyRule($attrs);
			$collection->addRule($rule);
		}		
	}
	/**
	 * Sets up the provided config with a mocked up event bus.
	 *
	 * @return OLPBlackbox_Config $config The config created with an event bus.
	 */
	protected function freshConfigWithEventBus()
	{
		return $this->fixture_factory->freshConfigWithEventBus();
	}
	
	/**
	 * Create a new Subscriber that will collect all events sent to bus $bus.
	 *
	 * @param OLP_IEventBus $bus The bus to have the subscriber subscribe to.
	 * @return OLP_Test_CollectingSubscriber
	 */
	protected function freshCollectorForAllEventsOn(OLP_IEventBus $bus)
	{
		return $this->fixture_factory->freshCollectorForEventsOn($bus);
	}
	
	/**
	 * Make a new rule collection mock object and make it return the $config 
	 * provided.
	 *
	 * @param OLPBlackbox_Config $config The config to have the collection use.
	 * @return OLPBlackbox_RuleCollection
	 */
	protected function freshCollectionWithConfig(OLPBlackbox_Config $config)
	{
		$collection = $this->getMock('OLPBlackbox_RuleCollection', array('getConfig'));
		$collection->expects($this->any())
			->method('getConfig')
			->will($this->returnValue($config));
		return $collection;
	}
}

?>