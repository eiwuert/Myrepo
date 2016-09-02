<?php
/**
 * PHPUnit test class for the OLPBlackbox_TargetCollection class.
 *
 * @todo Add tests for new repick flag.
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_TargetCollectionTest extends PHPUnit_Framework_TestCase
{
	/**
	 * The OLPBlackbox_TargetCollection object used in tests.
	 *
	 * @var OLPBlackbox_TargetCollection
	 */
	protected $target_collection;

	/**
	 * State data to pass around to tests.
	 *
	 * @var Blackbox_StateData
	 */
	protected $state_data;
	
	/**
	 * An object to get common fixtures for the EventBus subpackage.
	 *
	 * Used for testing some events functionality.
	 * 
	 * @var OLPBlackbox_Test_EventFixtureFactory
	 */
	protected $event_fixtures;

	/**
	 * Sets up the tests OLPBlackbox_TargetCollection object.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->state_data = new OLPBlackbox_StateData();
		$this->target_collection = new OLPBlackbox_TargetCollection('test');
		$this->event_fixtures = new OLPBlackbox_Test_EventFixtureFactory();
	}

	/**
	 * Destroys the target collection at the end of each test.
	 *
	 * @return void
	 */
	public function tearDown()
	{
		unset($this->target_collection);
	}

	/**
	 * Tests that the constructor throws an InvalidArgumentException when it receives and invalid
	 * name.
	 *
	 * @expectedException InvalidArgumentException
	 * @return void
	 */
	public function testConstructorException()
	{
		new OLPBlackbox_TargetCollection(12345);
	}

	/**
	 * Test that pickTarget with no picker defined returns FALSE by default.
	 *
	 * @return void
	 */
	public function testPickTargetNoPicker()
	{
		$data = new Blackbox_Data();
		$valid = $this->target_collection->isValid($data, $this->state_data);

		$target = $this->target_collection->pickTarget($data);
		$this->assertFalse($target);
	}

	/**
	 * Test that pickTarget returns the correct target whith a picker.
	 *
	 * @return void
	 */
	public function testPickTargetWithPicker()
	{
		$data = new Blackbox_Data();
		$winning_target = $this->getMock('OLPBlackbox_Campaign', array('isValid'), array('test', 0, 1));
		$winning_target->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));

		$picker = $this->getMock('OLPBlackbox_PriorityPicker', array('pickTarget'));
		$picker->expects($this->any())->method('pickTarget')->will($this->returnValue($winning_target));

		$this->target_collection->addTarget($winning_target);
		$this->target_collection->setPicker($picker);
		$this->assertTrue($this->target_collection->isValid($data, $this->state_data));

		$target = $this->target_collection->pickTarget($data);
		$this->assertEquals($winning_target, $target);
	}

	/**
	 * Simply tests that we can pass a campaign to the target collection and not get an
	 * exception.
	 *
	 * @return void
	 */
	public function testAddTarget()
	{
		$campaign = new OLPBlackbox_Campaign('test', 0, 100);

		$this->target_collection->addTarget($campaign);
	}

	/**
	 * Tests that pickTarget returns just from the valid_list, not from the entire target_list
	 * when a picker is set.
	 *
	 * There was a small bug where we were passing $this->target_list instead of $this->valid_list
	 * to the picker, so it could return an invalid target.
	 *
	 * @return void
	 */
	public function testPickTargetInvalidTargets()
	{
		$data = new Blackbox_Data();

		$campaign_one = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid', 'pickTarget'),
			array('test', 0, 1)
		);
		$campaign_one->expects($this->any())->method('isValid')
			->will($this->returnValue(FALSE));
		$winner_one = new OLPBlackbox_Winner($campaign_one);
		$campaign_one->expects($this->any())->method('pickTarget')
			->will($this->returnValue($winner_one));

		$campaign_two = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid', 'pickTarget'),
			array('test2', 0, 10)
		);
		$winner_two = new OLPBlackbox_Winner($campaign_two);
		$campaign_two->expects($this->any())->method('isValid')
			->will($this->returnValue(TRUE));
		$campaign_two->expects($this->any())->method('pickTarget')
			->will($this->returnValue($winner_two));

		$picker = $this->getMock('OLPBlackbox_PriorityPicker', array('random', 'addPickedTarget'));
		$picker->expects($this->any())->method('random')->will($this->returnValue(array(1, 10)));

		$this->target_collection->addTarget($campaign_one);
		$this->target_collection->addTarget($campaign_two);

		$valid = $this->target_collection->isValid($data, $this->state_data);
		$this->assertTrue($valid);

		$this->target_collection->setPicker($picker);
		$winner = $this->target_collection->pickTarget($data);
		$this->assertTrue($winner->getCampaign()->isValid($data, $this->state_data));
	}

	/**
	 * Tests the target collection's setInvalid function.
	 *
	 * @return void
	 */
	public function testSetInvalid()
	{
		$data = new OLPBlackbox_Data();

		$campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid'),
			array('test', 0, 1)
		);
		$campaign->expects($this->any())->method('isValid')->will($this->returnValue(TRUE));
		$this->target_collection->addTarget($campaign);

		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		// Set the rules to return TRUE and verify that isValid is TRUE
		$this->target_collection->setRules($rules);
		$valid = $this->target_collection->isValid($data, $this->state_data);
		$this->assertTrue($valid);

		// Set the collection to be invalid and see that it now returns FALSE
		$this->target_collection->setInvalid();
		$valid = $this->target_collection->isValid($data, $this->state_data);
		$this->assertFalse($valid);
	}

	/**
	 * Data provider for testPickTargetRules.
	 *
	 * @return array
	 */
	public static function pickTargetRulesDataProvider()
	{
		return array(
			array(TRUE, TRUE), // rules return TRUE, we expect the winner to evalutate to true
			array(FALSE, FALSE) // rules return FALSE, expect pickTarget to be FALSE
		);
	}

	/**
	 * Tests that the pickTargetRules affects the outcome.
	 *
	 * @param bool $rule_is_valid validity of the rule
	 * @param bool $expected what we expect from pickTarget
	 * @dataProvider pickTargetRulesDataProvider
	 * @return void
	 */
	public function testPickTargetRules($rule_is_valid, $expected)
	{
		$data = new Blackbox_Data();

		// Mock rule that we'll add to the target collection's pickTargetRules
		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->once())->method('isValid')->will($this->returnValue($rule_is_valid));

		$campaign_one = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid', 'pickTarget'),
			array('test', 0, 1)
		);
		$campaign_one->expects($this->any())->method('isValid')
			->will($this->returnValue(TRUE));
		$winner_one = new OLPBlackbox_Winner($campaign_one);
		$campaign_one->expects($this->any())->method('pickTarget')
			->will($this->returnValue($winner_one));

		$this->target_collection->addTarget($campaign_one);
		$this->target_collection->setPickTargetRules($rules);

		$valid = $this->target_collection->isValid($data, $this->state_data);
		$this->assertTrue($valid);

		$winner = $this->target_collection->pickTarget($data);

		if ($expected)
		{
			$this->assertType('Blackbox_IWinner', $winner);
		}
		else
		{
			$this->assertFalse($winner);
		}
	}

	/**
	 * Data provider function for testPostTargetRules.
	 *
	 * @return array
	 */
	public static function postTargetRulesDataProvider()
	{
		return array(
			array(TRUE, TRUE),
			array(FALSE, FALSE)
		);
	}

	/**
	 * Tests that the post targets rules run.
	 *
	 * There is no test to check that it works without being set, since this is effectively tested above.
	 *
	 * @param bool $is_valid if the rule returns valid
	 * @param bool $expected what we expect from the collection's isValid call
	 * @dataProvider postTargetRulesDataProvider
	 * @return void
	 */
	public function testPostTargetRules($is_valid, $expected)
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();

		$rule = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rule->expects($this->once())->method('isValid')->will($this->returnValue($is_valid));

		$campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('isValid', 'pickTarget'),
			array('test', 0, 1)
		);
		$campaign->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		$this->target_collection->addTarget($campaign);
		$this->target_collection->setPostTargetRules($rule);

		$valid = $this->target_collection->isValid($data, $state_data);
		$this->assertEquals($expected, $valid);
	}

	/**
	 * Test the return of the sleep method
	 *
	 * @return void
	 */
	public function testSleep()
	{
		$picker_sleep_return = 'I slept with the picker';
		$picker = $this->getMock('OLPBlackbox_IPicker');
		$picker->expects($this->once())
			->method('sleep')
			->will($this->returnValue($picker_sleep_return));
		$this->target_collection->setPicker($picker);

		$campaign_name = 'test';
		$campaign_sleep_return = "I slept with the campaign";
		$campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('sleep'),
			array($campaign_name));
		$campaign->expects($this->once())
			->method('sleep')
			->will($this->returnValue($campaign_sleep_return));
		$this->target_collection->addTarget($campaign);
		
		$sleep_data = $this->target_collection->sleep();
		
		$this->assertType('array', $sleep_data);
		$this->assertArrayHasKey('valid', $sleep_data);
		$this->assertAttributeEquals($sleep_data['valid'], 'valid', $this->target_collection);
		$this->assertArrayHasKey('pick_target_rules_result', $sleep_data);
		$this->assertAttributeEquals($sleep_data['pick_target_rules_result'], 'pick_target_rules_result', $this->target_collection);
		$this->assertArrayHasKey('state_data', $sleep_data);
		$this->assertAttributeEquals($sleep_data['state_data'], 'state_data', $this->target_collection);
		$this->assertArrayHasKey('picker', $sleep_data);
		$this->assertEquals($picker_sleep_return, $sleep_data['picker']);
		$this->assertArrayHasKey('children', $sleep_data);
		$this->assertType('array', $sleep_data['children']);
		$this->assertArrayHasKey($campaign_name, $sleep_data['children']);
		$this->assertEquals($campaign_sleep_return, $sleep_data['children'][$campaign_name]);
	}

	/**
	 * Test the wakeup method for a default sleep 
	 *
	 * @return void
	 */
	public function testWakeupDefault()
	{
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();
		
		$picker_sleep_data = array('I slept with the picker');
		$campaign_sleep_data = array('I slept with the campaign');
		$campaign_name = ' CMPGN';
		$sleep_data = array(
			'valid' => NULL,
			'state_data' => $this->state_data,
			'pick_target_rules_result' => NULL,
			'picker' => $picker_sleep_data,
			'children' => array($campaign_name => $campaign_sleep_data)
		);
		
		$campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('wakeup', 'isValid'),
			array($campaign_name)
		);

		$campaign->expects($this->once())
			->method('isValid')
			->will($this->returnValue(TRUE));

		$campaign->expects($this->once())
			->method('wakeup')
			->with($this->equalTo($sleep_data['children'][$campaign_name]));

		$picker = $this->getMock('OLPBlackbox_IPicker');
		$picker->expects($this->once())
			->method('wakeup')
			->with($this->equalTo($sleep_data['picker']));
		$picker->expects($this->any())
			->method('pickTarget')
			->will($this->returnValue($campaign));

			$coll_rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$coll_rules->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));
		
		$coll_pick_rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$coll_pick_rules->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));
		
		$coll_post_rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$coll_post_rules->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		// Campaign mock just needs to implement OLPBlackbox_IRestorable
		$this->target_collection->addTarget($campaign);
		$this->target_collection->setPicker($picker);
		$this->target_collection->setRules($coll_rules);
		$this->target_collection->setPickTargetRules($coll_pick_rules);
		$this->target_collection->setPostTargetRules($coll_post_rules);

		$this->target_collection->wakeup($sleep_data);
		
		$valid = $this->target_collection->isValid($data, $state_data);
		$this->assertTrue($valid);

		$winner = $this->target_collection->pickTarget($data);
		
		$this->assertEquals($campaign, $winner);

				
	}
	
	/**
	 * Test the wakeup method for sleep data from a collection with a cached valid value of FALSE
	 * The cached value should prevent any rules or pickTarget functionality to be performed after
	 * wakeup as the collection has been branded as invalid
	 *
	 * @return void
	 */
	public function testWakeupCachedValid()
	{
		
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();
		
		$picker_sleep_data = array('I slept with the picker');
		$campaign_sleep_data = array('I slept with the campaign');
		$campaign_name = ' CMPGN';
		$sleep_data = array(
			'valid' => FALSE,
			'state_data' => $this->state_data,
			'pick_target_rules_result' => FALSE,
			'picker' => $picker_sleep_data,
			'children' => array($campaign_name => $campaign_sleep_data)
		);
		
		$picker = $this->getMock('OLPBlackbox_IPicker');
		$picker->expects($this->once())
			->method('wakeup')
			->with($this->equalTo($sleep_data['picker']));

		$coll_rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$coll_rules->expects($this->never())->method('isValid');
		
		$coll_pick_rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$coll_pick_rules->expects($this->never())->method('isValid');
		
		$coll_post_rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$coll_post_rules->expects($this->never())->method('isValid');

		// Campaign mock just needs to implement OLPBlackbox_IRestorable
		$campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('wakeup', 'isValid'),
			array($campaign_name)
		);

		$campaign->expects($this->never())->method('isValid');

		$campaign->expects($this->once())
			->method('wakeup')
			->with($this->equalTo($sleep_data['children'][$campaign_name]));
			
		$picker->expects($this->never())
			->method('pickTarget')
			->will($this->returnValue($campaign));

		$this->target_collection->addTarget($campaign);
		$this->target_collection->setPicker($picker);
		$this->target_collection->setRules($coll_rules);
		$this->target_collection->setPickTargetRules($coll_pick_rules);
		$this->target_collection->setPostTargetRules($coll_post_rules);

		$this->target_collection->wakeup($sleep_data);
		
		$valid = $this->target_collection->isValid($data, $state_data);
		$this->assertFalse($valid);
		
		$winner = $this->target_collection->pickTarget($data);
		$this->assertFalse($winner);
	}
		/**
	 * Test the wakeup method for sleep data from a valid collection with a cached pick target value
	 *
	 * @return void
	 */
	public function testWakeupCachedPickTarget()
	{
		
		$data = new Blackbox_Data();
		$state_data = new Blackbox_StateData();
		
		$picker_sleep_data = array('I slept with the picker');
		$campaign_sleep_data = array('I slept with the campaign');
		$campaign_name = ' CMPGN';
		$sleep_data = array(
			'valid' => NULL,
			'state_data' => $this->state_data,
			'pick_target_rules_result' => FALSE,
			'picker' => $picker_sleep_data,
			'children' => array($campaign_name => $campaign_sleep_data)
		);
		
		$picker = $this->getMock('OLPBlackbox_IPicker');
		$picker->expects($this->once())
			->method('wakeup')
			->with($this->equalTo($sleep_data['picker']));

		$coll_rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$coll_rules->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));
		
		$coll_pick_rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$coll_pick_rules->expects($this->never())->method('isValid')->will($this->returnValue(TRUE));
		
		$coll_post_rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$coll_post_rules->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		// Campaign mock just needs to implement OLPBlackbox_IRestorable
		$campaign = $this->getMock(
			'OLPBlackbox_Campaign',
			array('wakeup', 'isValid'),
			array($campaign_name)
		);

		$campaign->expects($this->once())
			->method('isValid')
			->will($this->returnValue(TRUE));

		$campaign->expects($this->once())
			->method('wakeup')
			->with($this->equalTo($sleep_data['children'][$campaign_name]));
			
		$picker->expects($this->once())
			->method('pickTarget')
			->will($this->returnValue($campaign));

		$this->target_collection->addTarget($campaign);
		$this->target_collection->setPicker($picker);
		$this->target_collection->setRules($coll_rules);
		$this->target_collection->setPickTargetRules($coll_pick_rules);
		$this->target_collection->setPostTargetRules($coll_post_rules);

		$this->target_collection->wakeup($sleep_data);
		
		$valid = $this->target_collection->isValid($data, $state_data);
		$this->assertTrue($valid);
		
		$winner = $this->target_collection->pickTarget($data);
		$this->assertEquals($campaign, $winner);
	}
	
	/**
	 * Tests events which should be fired during the isValid() call to a target
	 * collection.
	 *
	 * @dataProvider isValidEventsProvider
	 * @param array $targets List of targets to populate the collection with 
	 * @param array $expected_events List of events objects that SHOULD be 
	 * fired when isValid is run. (Comparing attribs, not actual object).
	 * @param array $unexpected_events List of event objects which have attributes
	 * like those which should NOT have been sent while isValid is run.
	 * @return void
	 */
	public function testIsValidEvents($targets, $expected_events, $unexpected_events)
	{
		$config = $this->freshConfigWithEventBus();
		$collector = $this->freshCollectorForAllEventsOn($config->event_bus);
		$collection = $this->freshCollectionWithConfig($config);
		foreach ($targets as $target)
		{
			$collection->addTarget($target);
		}
		
		// run the method we're interested in
		$collection->isValid(new OLPBlackbox_Data(), $this->state_data);
		
		// verify results
		foreach ($expected_events as $expected)
		{
			$was_sent = (bool)count($collector->findEventsByAttributes($expected->getAttrs()));
			$this->assertTrue($was_sent, "Expected an event like $expected.");
		}
		foreach ($unexpected_events as $unexpected)
		{
			$was_sent = (bool)count($collector->findEventsByAttributes($unexpected->getAttrs()));
			$this->assertFalse($was_sent, "Did not expect an event like  $unexpected.");
		}
	}
	
	/**
	 * Data provider for {@see OLPBlackbox_TargetCollectionTest::testIsValidEvents()}
	 *
	 * @return array
	 */
	public static function isValidEventsProvider()
	{
		$targets_no_timeout = array(
			new OLPBlackbox_Test_DummyTarget()
		);
		$targets_with_timeouts = array(
			new OLPBlackbox_Test_DummyTarget(array('isvalid_timeout' => TRUE)),
			new OLPBlackbox_Test_DummyTarget(),
		);
		
		$full_events = array(
			new OLPBlackbox_Event(OLPBlackbox_Event::EVENT_VALIDATION_START, 
				array(OLPBlackbox_Event::ATTR_SENDER => OLPBlackbox_Event::TYPE_TARGET_COLLECTION)),
			new OLPBlackbox_Event(OLPBlackbox_Event::EVENT_VALIDATION_END,
				array(OLPBlackbox_Event::ATTR_SENDER => OLPBlackbox_Event::TYPE_TARGET_COLLECTION)),
		);
		return array(
			// with normal targets
			array($targets_no_timeout, $full_events, array()),
			
			// first target times out
			array($targets_with_timeouts, $full_events, array()), 
		);
	}
	
	/**
	 * Test that timeouts during isValid() runs cause target collections to not
	 * run certain targets and not be valid.
	 *
	 * @return void
	 */
	public function testTimeoutDuringIsValid()
	{
		$config = $this->freshConfigWithEventBus();
		$collection = $this->freshCollectionWithConfig($config);
		
		// test that 2 dummy targets will be valid if run normally.
		$this->addDummyTargetsToCollection(2, $collection, $config);
		$valid = $collection->isValid(new OLPBlackbox_Data(), $this->state_data);
		
		$this->assertTrue($valid, "Stock targets did not validate as expected.");
		foreach (array($collection->getTargetAtIndex(0), $collection->getTargetAtIndex(1)) as $target)
		{
			$this->assertEquals(TRUE, $target->isValidWasRun(),
				"Target was not run: " . spl_object_hash($target)
			);
		}
		$config->event_bus->unsubscribe($collection);
		
		
		// test that if the first target times out, the collection is invalid and 
		// the second target is not run
		$collection = $this->freshCollectionWithConfig($config);
		$this->addDummyTargetsToCollection(2, $collection, $config);
		// first target should send time out event.
		$collection->getTargetAtIndex(0)->attrs['isvalid_timeout'] = TRUE;
		
		$valid = $collection->isValid(new OLPBlackbox_Data(), $this->state_data);
		
		$this->assertFalse($valid, "Collection was not invalid when it received a timeout event");
		$this->assertTrue($collection->getTargetAtIndex(0)->isValidWasRun(), "Target One was not run.");
		$this->assertFalse($collection->getTargetAtIndex(1)->isValidWasRun(), "Target Two was run.");
	}
	
	/**
	 * Test that the events we expect to fire 
	 *
	 * @dataProvider pickTargetEventsProvider
	 * @param OLPBlackbox_IPicker $picker_class Optional picker to test all picker 
	 * behavior in target collections.
	 * @param array $expected_events List of OLPBlackbox_Event objects we expect
	 * to be hit once.
	 * @return void
	 */
	public function testPickTargetEvents($picker_class = NULL, $expected_events = array())
	{
		$config = $this->freshConfigWithEventBus();
		$collection = $this->freshCollectionWithConfig($config);
		$collector = $this->freshCollectorForAllEventsOn($config->event_bus);
		if ($picker_class) $this->setPickerFromClassName($collection, $picker_class);
		$this->addDummyTargetsToCollection(2, $collection, $config);

		// actual method we're interested in is pickTarget()
		$is_valid = $collection->isValid(new OLPBlackbox_Data(), $this->state_data);
		$winner = $collection->pickTarget(new OLPBlackbox_Data());
		
		// assertations we're interested in
		$this->assertTrue(
			$is_valid, 
			"Target collection was not valid despite having dummy targets."
		);
		$this->assertTrue(
			$winner instanceof Blackbox_IWinner, 
			"Did not receive winner from pickTarget."
		);
		foreach ($expected_events as $event)
		{
			/* @var $event OLPBlackbox_Event */
			$this->assertEquals(
				1, count($collector->findEventsByAttributes($event->getAttrs())), 
				"Event {$event} was not fired once as expected."
			);
		}
	}
	
	/**
	 * Provide data for {@see OLPBlackbox_TargetCollectionTest::testPickTargetEvents()}
	 *
	 * @return array
	 */
	public static function pickTargetEventsProvider()
	{
		$all_pick_events = array(
			new OLPBlackbox_Event(OLPBlackbox_Event::EVENT_PICK_START, 
				array(OLPBlackbox_Event::ATTR_SENDER => OLPBlackbox_Event::TYPE_TARGET_COLLECTION)),
			new OLPBlackbox_Event(OLPBlackbox_Event::EVENT_PICK_END, 
				array(OLPBlackbox_Event::ATTR_SENDER => OLPBlackbox_Event::TYPE_TARGET_COLLECTION)),
		);
		
		return array(
			array(NULL, $all_pick_events),
			array('OLPBlackbox_PriorityPicker', $all_pick_events),
			array('OLPBlackbox_PercentPicker', $all_pick_events),
			array('OLPBlackbox_OrderedPicker', $all_pick_events),
		);
	}
	
	/**
	 * Tests that when a timeout happens during a pickTarget run that the result
	 * is FALSE indicating the picking was stopped.
	 *
	 * @dataProvider timeoutDuringPickTestProvider
	 * @param string $picker_class Picker class to arm the target collection with.
	 * @return void
	 */
	public function testTimeoutDuringPickTarget($picker_class = NULL)
	{
		$config = $this->freshConfigWithEventBus();
		$collection = $this->freshCollectionWithConfig($config);
		if ($picker_class) $this->setPickerFromClassName($collection, $picker_class);
		$this->addDummyTargetsToCollection(1, $collection, $config);
		$collection->getTargetAtIndex(0)->attrs['picktarget_timeout'] = TRUE;
		$collection->getTargetAtIndex(0)->attrs['pick_fail'] = TRUE;
		
		$is_valid = $collection->isValid(new OLPBlackbox_Data(), $this->state_data);
		$winner = $collection->pickTarget(new OLPBlackbox_Data());
		
		$this->assertTrue(
			$is_valid, "Collection was invalid."
		);
		$this->assertFalse(
			$winner instanceof Blackbox_IWinner,
			"Winner was returned even though timeout was sent."
		);
	}
	
	/**
	 * Provides data for {@see OLPBlackbox_TargetCollectionTest::testTimeoutDuringPickTarget()}
	 *
	 * @return array
	 */
	public static function timeoutDuringPickTestProvider()
	{
		return array(
			array(),
			array('OLPBlackbox_PercentPicker'),
			array('OLPBlackbox_OrderedPicker'),
			array('OLPBlackbox_PriorityPicker'),
		);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * Create a mock picker and mock out the methods which call functions which
	 * would cause the test to crash.
	 *
	 * @param OLPBlackbox_TargetCollection $collection The collection to add the
	 * picker to.
	 * @param string $picker_class The class to create.
	 * @return void
	 */
	protected function setPickerFromClassName(OLPBlackbox_TargetCollection $collection, $picker_class)
	{
		$picker = $this->getMock(
			$picker_class, array('incrementFrequencyScore')
		);
		$collection->setPicker($picker);
	}
	
	/**
	 * Adds a particular number of dummy targets to a collection.
	 *
	 * @param int $number The number of dummy targets to add.
	 * @param OLPBlackbox_TargetCollection $collection The collection to add the
	 * targets to.
	 * @param OLPBlackbox_Config $config The configuration to set the dummy
	 * targets up with.
	 * @return void
	 */
	protected function addDummyTargetsToCollection(
		$number, 
		OLPBlackbox_TargetCollection $collection, 
		OLPBlackbox_Config $config = NULL
	)
	{
		for ($i=0; $i<$number; ++$i)
		{
			$attrs = array();
			if ($config) $attrs['config'] = $config;
			
			// the first targets will have the higher weight and least leads.
			$attrs['weight'] = $number - $i;
			$attrs['current_leads'] = $i;
			
			$target = new OLPBlackbox_Test_DummyTarget($attrs);
			$collection->addTarget($target);
		}
	}
	/**
	 * Make a new mock TargetCollection and change getConfig() to return the $config
	 * passed in.
	 *
	 * @param OLPBlackbox_Config $config The config the TargetCollection will use.
	 * @return OLPBlackbox_TargetCollection
	 */
	protected function freshCollectionWithConfig(OLPBlackbox_Config $config)
	{
		$collection = $this->getMock(
			'OLPBlackbox_TargetCollection', 
			array('getConfig'),
			array('target_collection')
		);
		$collection->expects($this->any())
			->method('getConfig')
			->will($this->returnValue($config));
		if (isset($config->event_bus) && $config->event_bus instanceof OLP_IEventBus)
		{
			$config->event_bus->subscribeTo(OLPBlackbox_Event::EVENT_BLACKBOX_TIMEOUT, $collection);
		}
		return $collection;
	}
	
	/**
	 * Returns a new OLPBlackbox_Config with an event bus in it.
	 *
	 * @return OLPBlackbox_Config
	 */
	protected function freshConfigWithEventBus()
	{
		return $this->event_fixtures->freshConfigWithEventBus();
	}
	
	/**
	 * Returns a subscriber which will be notified of all events sent on the bus
	 * supplied and store these events, in order, for verification later.
	 *
	 * @param OLP_IEventBus $bus
	 * @return OLP_Test_CollectingSubscriber
	 */
	protected function freshCollectorForAllEventsOn(OLP_IEventBus $bus)
	{
		return $this->event_fixtures->freshCollectorForEventsOn($bus);
	}
}
?>
