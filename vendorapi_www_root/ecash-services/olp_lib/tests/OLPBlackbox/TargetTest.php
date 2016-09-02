<?php

require_once('OLPBlackboxTestSetup.php');

/**
 * PHPUnit test class for the OLPBlackbox_Target class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPBlackbox_TargetTest extends PHPUnit_Framework_TestCase
{
	/**
	 * The OLPBlackbox_Target object used in tests.
	 *
	 * @var OLPBlackbox_Target
	 */
	protected $target;

	/**
	 * State data to pass around to tests.
	 *
	 * @var Blackbox_StateData
	 */
	protected $state_data;
	
	/**
	 * Shared fixture for the EventBus subpackage.
	 *
	 * @var OLPBlackbox_Test_EventFixtureFactory
	 */
	protected $fixture_factory;

	/**
	 * Sets up the tests OLPBlackbox_Target object.
	 *
	 * @return void
	 */
	public function setUp()
	{
		$this->state_data = new Blackbox_StateData();
		$this->target = new OLPBlackbox_Target('test', 0);
		$this->fixture_factory = new OLPBlackbox_Test_EventFixtureFactory();
	}

	/**
	 * Destroys the target at the end of each test.
	 *
	 * @return void
	 */
	public function tearDown()
	{
		unset($this->target);
	}

	/**
	 * Tests that the isValid function returns TRUE if the rules' isValid returns TRUE.
	 *
	 * @return void
	 */
	public function testIsValidPassOnRules()
	{
		/**
		 * We don't ever expect the Blackbox_Data object to have a value set or unset inside of
		 * Blackbox.
		 */
		$data = $this->getMock('OLPBlackbox_Data', array());
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		$this->target->setRules($rules);
		$valid = $this->target->isValid($data, $this->state_data);

		$this->assertTrue($valid);
	}

	/**
	 * Tests that the isValid function returns FALSE if the rules' isValid returns FALSE.
	 *
	 * @return void
	 */
	public function testIsValidFailOnRules()
	{
		/**
		 * We don't ever expect the Blackbox_Data object to have a value set or unset inside of
		 * Blackbox.
		 */
		$data = $this->getMock('OLPBlackbox_Data', array());
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));

		$this->target->setRules($rules);
		$valid = $this->target->isValid($data, $this->state_data);

		$this->assertFalse($valid);
	}

	/**
	 * Tests to see if the Blackbox_Exception is thrown if we don't setup the rules.
	 *
	 * @expectedException Blackbox_Exception
	 * @return void
	 */
	public function testIsValidException()
	{
		$data = $this->getMock('OLPBlackbox_Data', array());
		$data->expects($this->never())->method('__set');
		$data->expects($this->never())->method('__unset');

		$this->target->isValid($data, $this->state_data);
	}

	/**
	 * Tests that the setInvalid() function works properly.
	 *
	 * @return void
	 */
	public function testSetInvalid()
	{
		$data = new OLPBlackbox_Data();

		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		// Test that we actually were TRUE the first run
		$this->target->setRules($rules);
		$valid = $this->target->isValid($data, $this->state_data);
		$this->assertTrue($valid);

		// Set this target to invalid and test that we get FALSE back now
		$this->target->setInvalid();
		$valid = $this->target->isValid($data, $this->state_data);
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
			array(TRUE, TRUE),
			array(FALSE, FALSE)
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
		$rules->expects($this->any())
			->method('isValid')
			->will($this->returnValue($rule_is_valid));

		$target = new OLPBlackbox_Target('test', 0);
		$target->setPickTargetRules($rules);

		$winner = $target->pickTarget($data);

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
	 * Test the sleep function 
	 *
	 * @return void
	 */
	public function testSleep()
	{
		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));
		$this->target->setRules($rules);

		// Test a target in its default state
		$sleep_data = $this->target->sleep();
		$this->assertType('array', $sleep_data);
		$this->assertArrayHasKey('valid', $sleep_data);
		$this->assertAttributeEquals($sleep_data['valid'], 'valid', $this->target);
		$this->assertArrayHasKey('pick_target_rules_result', $sleep_data);
		$this->assertAttributeEquals($sleep_data['pick_target_rules_result'], 'pick_target_rules_result', $this->target);
		$this->assertArrayHasKey('state_data', $sleep_data);
		$this->assertAttributeEquals($sleep_data['state_data'], 'state_data', $this->target);

		// Test the same target after it isValid/pickTarget
		$this->target->isValid(new OLPBlackbox_Data(), new OLPBlackbox_StateData());
		$sleep_data = $this->target->sleep();
		$this->assertType('array', $sleep_data);
		$this->assertArrayHasKey('valid', $sleep_data);
		$this->assertAttributeEquals($sleep_data['valid'], 'valid', $this->target);
		$this->assertArrayHasKey('pick_target_rules_result', $sleep_data);
		$this->assertAttributeEquals($sleep_data['pick_target_rules_result'], 'pick_target_rules_result', $this->target);
		$this->assertArrayHasKey('state_data', $sleep_data);
		$this->assertAttributeEquals($sleep_data['state_data'], 'state_data', $this->target);
		
	}

	/**
	 * Test the wakeup function on a target in its default state
	 *
	 * @return void
	 */
	public function testWakeupDefaultTarget()
	{
		$bbx_data = new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		
		// This portion will test restoring a default rule that has not been validated or picked

		// IsValid will run and return TRUE to allow for process testing for pickTarget
		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));
		
		// Pick Target will run and return FALSE
		$rules2 = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules2->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));

		$this->target->setRules($rules);
		$this->target->setPickTargetRules($rules2);

		$wakeup_data = array(
			'valid' => NULL,
			'pick_target_rules_result' => NULL,
			'state_data' => $state_data
		);

		$this->target->wakeup($wakeup_data);
		$valid = $this->target->isValid($bbx_data, $state_data);
		$this->assertType('bool', TRUE);
		$this->assertEquals($wakeup_data['valid'], FALSE);
		$winner = $this->target->pickTarget($bbx_data);
		$this->assertType('bool', $winner);
		$this->assertEquals(FALSE, $winner);

	}

	/**
	 * Test the wakeup function on a target that was deemed invalid
	 *
	 * @return void
	 */
	public function testWakeupInvalidTarget()
	{
		$bbx_data = new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		
		// This portion will test restoring a default rule that has not been validated or picked
		
		// IsValid will not run
		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->never())->method('isValid')->will($this->returnValue(FALSE));

		// Pick Target will not run
		$rules2 = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules2->expects($this->never())->method('isValid')->will($this->returnValue(FALSE));

		$this->target->setRules($rules);
		$this->target->setPickTargetRules($rules2);

		$wakeup_data = array(
			'valid' => FALSE,
			'pick_target_rules_result' => NULL,
			'state_data' => $state_data
		);

		$this->target->wakeup($wakeup_data);
		$valid = $this->target->isValid($bbx_data, $state_data);
		$this->assertType('bool', $valid);
		$this->assertEquals($wakeup_data['valid'], $valid);
		$winner = $this->target->pickTarget($bbx_data);
		$this->assertType('bool', $winner);
		$this->assertEquals(FALSE, $winner);

	}
	
	/**
	 * Test the wakeup function on a target that was deemed valid but failed pick target rules
	 *
	 * @return void
	 */
	public function testWakeupFailedPickTargetRules()
	{
		$bbx_data = new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		
		// IsValid will not run since it has been loaded to true
		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->never())->method('isValid');

		// Pick Target will run because valid will be set to TRUE
		$rules2 = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules2->expects($this->once())->method('isValid')->will($this->returnValue(FALSE));

		$this->target->setRules($rules);
		$this->target->setPickTargetRules($rules2);

		$wakeup_data = array(
			'valid' => TRUE,
			'pick_target_rules_result' => NULL,
			'state_data' => $state_data
		);

		$this->target->wakeup($wakeup_data);
		$valid = $this->target->isValid($bbx_data, $state_data);
		$this->assertType('bool', $valid);
		$this->assertEquals($wakeup_data['valid'], $valid);
		$winner = $this->target->pickTarget($bbx_data);
		$this->assertType('bool', $winner);
		$this->assertEquals(FALSE, $winner);

	}
	
	/**
	 * Test the wakeup function on a target that was deemed valid and passed pick target rules
	 *
	 * @return void
	 */
	public function testWakeupValidAndPassed()
	{
		$bbx_data = new OLPBlackbox_Data();
		$state_data = new OLPBlackbox_StateData();
		
		// IsValid will not run since it has been loaded to true
		$rules = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules->expects($this->never())->method('isValid');

		// Pick Target will not run because pick_target_rules_result will be set
		$rules2 = $this->getMock('Blackbox_StandardRule', array('isValid', 'runRule'));
		$rules2->expects($this->never())->method('isValid');

		$this->target->setRules($rules);
		$this->target->setPickTargetRules($rules2);

		$wakeup_data = array(
			'valid' => TRUE,
			'pick_target_rules_result' => TRUE,
			'state_data' => $state_data
		);

		$this->target->wakeup($wakeup_data);
		$valid = $this->target->isValid($bbx_data, $state_data);
		$this->assertType('bool', $valid);
		$this->assertEquals($wakeup_data['valid'], $valid);
		$winner = $this->target->pickTarget($bbx_data);

		// Since the valid and pick_target_rules_result were set to true, the target should
		// wrap itself in a winner object and return that object
		$this->assertType('Blackbox_IWinner', $winner);
		$this->assertEquals($this->target, $winner->getTarget());

	}
	
	/**
	 * Tests that the proper event bus events are sent by isValid().
	 *
	 * @dataProvider validEventsProvider
	 * @param string $expected_events The events we expect to be sent.
	 * @return void
	 */
	public function testIsValidEvents($expected_events)
	{
		$config = $this->getConfigWithEventBus();
		$collector = $this->collectorForAllEventsOn($config->event_bus);
		$target = $this->freshTargetWithConfig($config);
		
		
		// actual test - fire an isValid call
		$target->isValid(new Blackbox_Data(), $this->state_data);
		
		
		// check that all events we expected got sent
		foreach ($expected_events as $expected_event)
		{
			$num_matching_events = count(
				$collector->findEventsByAttributes($expected_event->getAttrs())
			);
			$this->assertEquals($num_matching_events, 1,
				"Expected 1 event matching {$expected_event}, got {$num_matching_events}."
			);
		}
	}
	
	//--------------------------------------------------------------------------
	
	/**
	 * Supplies the events we'd like to see hit during isValid() runs.
	 *
	 * @see OLPBlackbox_TargetTest::testIsValidEvents()
	 * @return array
	 */
	public static function validEventsProvider()
	{
		$validation_start_event = new OLPBlackbox_Event(
			OLPBlackbox_Event::EVENT_VALIDATION_START,
			array(OLPBlackbox_Event::ATTR_SENDER => OLPBlackbox_Event::TYPE_TARGET)
		);
		$validation_end_event = new OLPBlackbox_Event(
			OLPBlackbox_Event::EVENT_VALIDATION_START,
			array(OLPBlackbox_Event::ATTR_SENDER => OLPBlackbox_Event::TYPE_TARGET)
		);
		
		return array(
			array(array($validation_start_event, $validation_end_event),)
		);
	}
	
	/**
	 * Attaches an OLP_Test_CollectingSubscriber to a given event bus, which will
	 * "collect" all events sent on this bus via notify().
	 *
	 * @param OLP_IEventBus $bus
	 * @return OLP_Test_CollectingSubscriber
	 */
	protected function collectorForAllEventsOn(OLP_IEventBus $bus)
	{
		return $this->fixture_factory->freshCollectorForEventsOn($bus);
	}
	
	/**
	 * Sets up the provided config with a mocked up event bus.
	 *
	 * @return OLPBlackbox_Config $config The config created with an event bus.
	 */
	protected function getConfigWithEventBus()
	{
		return $this->fixture_factory->freshConfigWithEventBus();
	}

	/**
	 * Returns a new target which will use $config as it's config and optionally
	 * set some rules on the target.
	 *
	 * @param OLPBlackbox_Config $config The config to be returned when the 
	 * target mock object calls getConfig()
	 * @param array $rules List of rules to load the target with.
	 * @return OLPBlackbox_Target (mock)
	 */
	protected function freshTargetWithConfig(OLPBlackbox_Config $config, $rules = NULL)
	{
		$target = $this->freshTargetWithRules(array('getConfig'), $rules);
		$target->expects($this->any())
			->method('getConfig')
			->will($this->returnValue($config));
		return $target;
	}
	
	/**
	 * Preps a target with some fake rules and fake pick target rules.
	 * 
	 * @param array $mock_methods the methods to mock on the target.
	 * @param array $rules The rules to load in the target using setRules().
	 * @return OLPBlackbox_Target (mock)
	 */
	protected function freshTargetWithRules($mock_methods = array(), $rules = NULL)
	{
		$target = $this->getMock(
			'OLPBlackbox_Target', $mock_methods, array('test', 0)
		);
		
		if ($rules instanceof Blackbox_IRule)
		{
			$target->setRules($rules);
		}
		else
		{
			$target->setRules($this->getMock('OLPBlackbox_DebugRule'));
		}
		
		$target->setPickTargetRules($this->getMock('OLPBlackbox_DebugRule'));
		return $target;
	}
}

?>
