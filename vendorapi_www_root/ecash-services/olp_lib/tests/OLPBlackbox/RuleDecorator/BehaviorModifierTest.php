<?php

/**
 * Tests the rule decorator which alters runtime behavior for rule conditions.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 */
class OLPBlackbox_RuleDecorator_BehaviorModifierTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Some random constants used for raw strings in the tests.
	 */
	const TARGET = 'ABC';
	const TRACK_HASH = 'sas9d7as71201212812asdak1o2';
	const PROMO_ID = 3221;
	
	/**
	 * Blackbox data used during tests.
	 * @see setUp()
	 * @var Blackbox_Data 
	 */
	protected $blackbox_data;
	
	/**
	 * @see setUp()
	 * @var Blackbox_IStateData
	 */
	protected $state_data;
	
	/**
	 * @see setUp()
	 * @var OLPBlackbox_Config
	 */
	protected $config;
	
	/**
	 * Set up items used during tests.
	 * @return void
	 */
	protected function setUp()
	{
		$this->blackbox_data = new Blackbox_Data();
		$this->blackbox_data->target = self::TARGET;
		
		$this->state_data = new OLPBlackbox_StateData();
		$this->state_data->track_hash = self::TRACK_HASH;
		
		$this->config = new OLPBlackbox_Config();
		$this->config->promo_id = self::PROMO_ID;
	}
	
	/**
	 * Test that when callback rules are added to the runtime conditional
	 * rule, the rules are executed.
	 * 
	 * The way the callbacks behave is their own business, as far as this class
	 * is concerned.
	 * 
	 * @return void
	 */
	public function testRunsCallbackRules()
	{
		$conditional = $this->freshDecorator($this->freshRuleMock());
		
		$callback = $this->freshMockCallbackRule();
		$callback->expects($this->once())
			->method('isValid')
			->with(
				$this->equalTo($this->blackbox_data),
				$this->equalTo($this->state_data)
			);
		
		$conditional->addCallbackRule($callback);
		
		$conditional->isValid($this->blackbox_data, $this->state_data);
	}
	
	/**
	 * Callback setSkippable() is a public method the wrapper must pass on to
	 * the underlying rule. (Callback rules will call it.)
	 * 
	 * @return void
	 */
	public function testSetSkippable()
	{
		$rule = $this->freshRuleMock();
		$rule->expects($this->once())
			->method('setSkippable')
			->with($this->equalTo(TRUE));
		
		$conditional = $this->freshDecorator($rule);
		$conditional->setSkippable(TRUE);
	}
	
	/**
	 * A public method that callback rules will invoke, must prevent the underlying
	 * rule from running.
	 * 
	 * @return void
	 */
	public function testSkip()
	{
		$rule = $this->freshRuleMock();
		$rule->expects($this->never())
			->method('isValid');
		
		$conditional = $this->freshDecorator(
			$rule, $this->freshMockEventLog('CONDITIONAL_SKIP')
		);
		$conditional->skip();
		$conditional->isValid($this->blackbox_data, $this->state_data);
	}
	
	/**
	 * Test that even if a decorator is set up without a rule, once a rule is 
	 * added, it runs properly.
	 * @return void
	 */
	public function testSetRule()
	{
		$rule = $this->freshRuleMock();
		$rule->expects($this->once())->method('isValid');
		$conditional = $this->freshDecorator();

		// meat of the test, set a rule in the conditional and make sure it works.
		$conditional->setRule($rule);
		
		$conditional->isValid($this->blackbox_data, $this->state_data);
	}
	
	/**
	 * Test that if a decorator is run without a rule, a runtime exception occurs.
	 * @return void
	 */
	public function testEmptyRule()
	{
		$this->setExpectedException('RuntimeException');
		$conditional = $this->freshDecorator(NULL);
		$conditional->isValid($this->blackbox_data, $this->state_data);
	}
	
	/**
	 * A public method the callback rules will invoke, must change the value the
	 * underlying rule uses.
	 * 
	 * @dataProvider valueChangeProvider
	 * @param string $source The place this RuntimeConditional rule should get
	 * the new value to set.
	 * @param string $flag the property of the $source to set as the new value.
	 * @param mixed $new_value The value we expect to get set on the underlying
	 * rule, based on $source, $flag and the test case's data sources from 
	 * {@see setUp()}.
	 * @return void
	 */
	public function testValueChange($source, $flag, $new_value)
	{
		$rule = $this->freshRuleMock();
		$rule->expects($this->once())
			->method('setRuleValue')
			->with($this->equalTo($new_value));
		
		$conditional = $this->freshDecorator($rule);
		$conditional->setRuleValueFromFlag($source, $flag);
		$conditional->isValid($this->blackbox_data, $this->state_data);
	}
	
	/**
	 * @see testValueChange()
	 * @return array
	 */
	public static function valueChangeProvider()
	{
		return array(
			array(OLPBlackbox_Config::DATA_SOURCE_CONFIG, 'promo_id', self::PROMO_ID),
			array(OLPBlackbox_Config::DATA_SOURCE_STATE, 'track_hash', self::TRACK_HASH),
			array(OLPBlackbox_Config::DATA_SOURCE_BLACKBOX, 'target', self::TARGET),
		);
	}
	
	// -------------------------------------------------------------------------
	
	/**
	 * Mock an Event_Log expecting a number of calls with an event name.
	 * @param string $event_name The name of the event to log.
	 * @param int $number_of_events
	 * @return Event_Log
	 */
	protected function freshMockEventLog($event_name, $number_of_events = 1)
	{
		$mock = $this->getMock('Event_Log', array('Log_Event'));
		$mock->expects($this->exactly($number_of_events))
			->method('Log_Event')
			->with(
				$this->anything(),
				$event_name,
				$this->anything(),
				$this->anything(),
				$this->anything()
			);
		return $mock;
	}
	
	/**
	 * Produce a new CallbackContainer.
	 * @return OLPBlackbox_Rule_CallbackContainer
	 */
	protected function freshMockCallbackRule()
	{
		return $this->getMock(
			'OLPBlackbox_Rule_CallbackContainer', 
			array(), 
			array($this->freshRuleMock())
		);
	}
	
	/**
	 * Make a new OLPBlackbox_RuleDecorator_BehaviorModifier with a contained rule.
	 * 
	 * @param Blackbox_IRule $rule Inner rule which will be governed by the 
	 * conditionals in the wrapping OLPBlackbox_RuleDecorator_BehaviorModifier object.
	 * @return OLPBlackbox_RuleDecorator_BehaviorModifier
	 */
	protected function freshDecorator(Blackbox_IRule $rule = NULL, $event_log = NULL)
	{
		$rule = $this->getMock(
			'OLPBlackbox_RuleDecorator_BehaviorModifier', 
			array('getConfig'),
			array($rule, $event_log)
		);
		$rule->expects($this->any())
			->method('getConfig')
			->will($this->returnValue($this->config));
		
		return $rule;
	}
	
	/**
	 * Produce a rule with most of it's methods mocked up.
	 * 
	 * @return OLPBlackbox_Rule
	 */
	protected function freshRuleMock()
	{
		return $this->getMock(
			'OLPBlackbox_Rule', 
			array('isValid', 'setSkippable', 'setRuleValue', 'getRuleValue', 'runRule')
		);
	}
}

?>