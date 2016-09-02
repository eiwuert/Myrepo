<?php

/**
 * Test the factory that produces rule conditions.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 * @subpackage Factory
 */
class OLPBlackbox_Factory_RuleConditionTest extends PHPUnit_Framework_TestCase
{
	const DEFAULT_CONDITION_FLAG = 'application_id';
	const DEFAULT_CONDITION_VALUE = 1000;
	
	/**
	 * Connection to the test blackbox database.
	 * @var DB_Database_1
	 */
	protected $db;
	
	/**
	 * @var OLPBlackbox_Factory_RuleCondition
	 */
	protected $factory;
	
	/**
	 * @var OLPBlackbox_RuleDecorator_BehaviorModifier
	 */
	protected $rule_decorator;
	
	/**
	 * Create variables for the tests.
	 * @return void
	 */
	protected function setUp()
	{
		$this->db = TEST_DB_CONNECTOR(TEST_BLACKBOX);
		$this->factory = $this->freshFactory();
		$this->rule_decorator = $this->freshMockRuleDecorator();
	}
	
	/**
	 * @dataProvider typeAssembledCorrectlyProvider
	 */
	public function testTypeAssembledCorrectly($type_name, $expected_class, $expected_value, $value = NULL)
	{
		$override = array('type' => $type_name);
		if ($value) $override['value'] = $value;
		$model = $this->freshMockRuleConditionsModel($override);
		
		// "1" is the "rule_id" which is irrelevant here, factory uses it to query model normally
		$conditions = $this->factory->getRuleConditions(1, $model, $this->rule_decorator);
		$this->assertFalse(empty($conditions[0]), "didn't get condition from factory");
		$this->assertTrue($conditions[0] instanceof OLPBlackbox_Rule_CallbackContainer);
		
		
		$rule_in_question = $conditions[0]->getRule();
		$this->assertTrue(
			$rule_in_question instanceof $expected_class, 
			"Rule produced by factory was not $expected_class, got " . get_class($rule_in_question)
		);
		$this->assertEquals(
			$expected_value, 
			$rule_in_question->getRuleValue(), 
			"produced a callback rule with an unexpected value."
		);
	}
	
	public static function typeAssembledCorrectlyProvider()
	{
		return array(
			// type name, expected class, expected rule value
			array(Blackbox_Models_RuleConditionType::COND_ALWAYS, 'OLPBlackbox_Rule_Boolean', TRUE),
			array(Blackbox_Models_RuleConditionType::COND_FLAG_EQ, 'OLPBlackbox_Rule_EqualsNoCase', self::DEFAULT_CONDITION_VALUE),
			array(Blackbox_Models_RuleConditionType::COND_FLAG_NOT_EQ, 'OLPBlackbox_Rule_NotEqualsNoCase', self::DEFAULT_CONDITION_VALUE),
			array(Blackbox_Models_RuleConditionType::COND_FLAG_NOT_SET, 'OLPBlackbox_Rule_PropertyNotSet', self::DEFAULT_CONDITION_FLAG),
			array(Blackbox_Models_RuleConditionType::COND_FLAG_SET, 'OLPBlackbox_Rule_PropertySet', self::DEFAULT_CONDITION_FLAG),
			array(Blackbox_Models_RuleConditionType::COND_FLAG_IN, 'OLPBlackbox_Rule_PropertyIn', array(1, 2, 3), '1,2, 3'),
		);
	}
	
	/**
	 * @dataProvider actionAssembledCorrectlyProvider
	 */
	public function testActionAssembledCorrectly($action_name, $expected_function, $expected_arguments)
	{
		$model = $this->freshMockRuleConditionsModel(array('action' => $action_name));
		
		$conditions = $this->factory->getRuleConditions(1, $model, $this->rule_decorator);
		$this->assertFalse(empty($conditions[0]), "didn't get condition from factory");
		$this->assertTrue($conditions[0] instanceof OLPBlackbox_Rule_CallbackContainer);
		
		$callbacks = $conditions[0]->getOnValidCallbacks();
		$this->assertFalse(empty($callbacks[0]), 'callback was not constructed');
		$rule_in_question = $callbacks[0];
		
		/* @var $rule_in_question OLPBlackbox_Rule_Callback */
		$this->assertEquals(
			$expected_function, 
			$rule_in_question->getFunction(),
			"Method/function name in callback object was wrong."
		);
		$this->assertEquals(
			array(),
			array_diff($expected_arguments, $rule_in_question->getArguments()),
			"Arguments should have been " . print_r($expected_arguments, TRUE) 
			. ", instead got " .print_r($rule_in_question->getArguments(), TRUE)
		);
	}
	
	public function testRunRuleActionAssemblesWithSkipOnInvalid()
	{
		$model = $this->freshMockRuleConditionsModel(array('action' => Blackbox_Models_RuleConditionAction::ACTION_RUN_RULE));
		$conditions = $this->factory->getRuleConditions(1, $model, $this->rule_decorator);
		$this->assertTrue($conditions[0] instanceof OLPBlackbox_Rule_CallbackContainer);
		
		$callbacks = $conditions[0]->getOnInvalidCallbacks();
		$this->assertThat($callbacks[0], $this->isInstanceOf("OLPBlackbox_Rule_Callback"));
		$function = $callbacks[0]->getFunction();
		$this->assertEquals("skip", $function);
		$args = $callbacks[0]->getArguments();
		$this->assertEquals(array(true), $args);
	}
	
	public static function actionAssembledCorrectlyProvider()
	{
		return array(
			array(Blackbox_Models_RuleConditionAction::ACTION_SKIP, 'skip', array()),
			array(Blackbox_Models_RuleConditionAction::ACTION_MAKE_NOT_SKIPPABLE, 'setSkippable', array(FALSE)),
			array(Blackbox_Models_RuleConditionAction::ACTION_MAKE_SKIPPABLE, 'setSkippable', array(TRUE)),
			array(Blackbox_Models_RuleConditionAction::ACTION_REPLACE_WITH_VALUE, 'setRuleValue', array(self::DEFAULT_CONDITION_VALUE)),
			array(
				Blackbox_Models_RuleConditionAction::ACTION_REPLACE_WITH_FLAG, 
				'setRuleValueFromFlag', 
				array(
					OLPBlackbox_Config::DATA_SOURCE_BLACKBOX, 
					self::DEFAULT_CONDITION_FLAG)),
		);
	}
	
	// -------------------------------------------------------------------------
		
	/**
	 * @return Blackbox_Models_View_RuleConditions
	 */
	protected function freshMockRuleConditionsModel(array $override_values = array())
	{
		$model = $this->getMock(
			'Blackbox_Models_View_RuleConditions', 
			array('loadAllBy'), 
			array($this->db)
		);
		$model->expects($this->any())
			->method('loadAllBy')
			->will($this->returnValue(array($model)));
		
		// arbitrarily chosen, but valid, configuration.
		$model->flag = self::DEFAULT_CONDITION_FLAG;
		$model->type = Blackbox_Models_RuleConditionType::COND_FLAG_SET;
		$model->action = Blackbox_Models_RuleConditionAction::ACTION_REPLACE_WITH_VALUE;
		$model->source = Blackbox_Models_RuleConditionSource::SOURCE_APPLICATION_DATA;
		$model->value = self::DEFAULT_CONDITION_VALUE;
		
		foreach ($override_values as $key => $value)
		{
			$model->$key = $value;
		}
		
		return $model;
	}
	
	/**
	 * The factory beingn tested accepts a RuntimeConditional rule to attach
	 * conditions to.
	 * @param OLPBlackbox_Rule $rule Optional rule to decorate.
	 * @return OLPBlackbox_RuleDecorator_BehaviorModifier
	 */
	protected function freshMockRuleDecorator(OLPBlackbox_Rule $rule = NULL)
	{
		return new OLPBlackbox_RuleDecorator_BehaviorModifier($rule);
	}
	
	/**
	 * Create a rule conditions model from a configuration.
	 * @param array $condition_model_config An associative array indicating the
	 * values that the resulting model should have.
	 * @return Blackbox_Models_View_RuleConditions
	 */
	protected function freshMockConditionsModelFromConfig(array $condition_model_config)
	{
		$rule_conditions_model = $this->getMock(
			'Blackbox_Models_View_RuleConditions', 
			array('loadAllBy'),
			array($this->db)
		);
		$rule_conditions_model->expects($this->any())
			->method('loadAllBy')
			->will($this->returnValue($this->getRuleConditionFromConfig($condition_model_config)));
		
		return $rule_conditions_model;
	}
	
	/**
	 * @return OLPBlackbox_Factory_RuleCondition
	 */
	protected function freshFactory()
	{
		return new OLPBlackbox_Factory_RuleCondition();
	}
	
	/**
	 * Return a rule conditions model using an associative array to set the 
	 * properties.
	 * 
	 * @param array $config Assoc array used to set properties.
	 * @return Blackbox_Models_View_RuleConditions
	 */
	protected function getRuleConditionFromConfig(array $config)
	{
		$condition = new Blackbox_Models_View_RuleConditions($this->db);
		foreach ($config as $key => $value)
		{
			$condition->$key = $value;
		}
		
		return array($condition);
	}
}

?>