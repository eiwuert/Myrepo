<?php

/**
 * A factory for producing rule conditions, which are OLPBlackbox_Rule_CallbackContainer objects.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 * @subpackage Factory
 */
class OLPBlackbox_Factory_RuleCondition
{	
	/**
	 * Produce rule conditions for a rule id.
	 * 
	 * @todo change the hint for Blackbox_Models_View_RuleConditions to OLP_IModel
	 * so a cached version can be passed.
	 * @param int $rule_id The rule ID to get conditions for.
	 * @param Blackbox_Models_View_RuleConditions $rule_conditions_model The 
	 * rule conditions model to use to use to fetch the database configuration
	 * for the conditions.
	 * @param OLPBlackbox_RuleDecorator_BehaviorModifier $rule The rule decorator
	 * to attach callbacks to.
	 * @return array List of OLPBlackbox_Rule_CallbackContainer objects.
	 */
	public function getRuleConditions(
		$rule_id, 
		Blackbox_Models_View_RuleConditions $rule_conditions_model,
		OLPBlackbox_RuleDecorator_BehaviorModifier $rule)
	{
		$conditions = array();
		foreach ($rule_conditions_model->loadAllBy(array('rule_id' => $rule_id)) as $condition_model)
		{
			// $conditional_rule decides if the condition should apply
			$conditional_rule = $this->getConditionalRule($condition_model);
			
			$callback_decorator = new OLPBlackbox_Rule_CallbackContainer($conditional_rule);
			
			// if the condition DOES apply, affect the run state of $rule
			$this->setCallbacksToAffectRule($condition_model, $callback_decorator, $rule);
			
			$conditions[] = $callback_decorator;
		}
		
		return $conditions;
	}

	/**
	 * Set up the actions to be taken when the $condition_rule runs, as dictated
	 * by the $condition_model.
	 * 
	 * Example: This wires up the  $conditional_rule to call skip() on the 
	 * $rule_object if the $condition_model's action says to do that. 
	 * 
	 * @param Blackbox_Models_View_RuleConditions $condition_model The model which
	 * will tell this function how to configure the calllbacks.
	 * @param OLPBlackbox_Rule_CallbackContainer $condition_rule This rule will 
	 * be the actor and call methods on $rule_object.
	 * @param OLPBlackbox_RuleDecorator_BehaviorModifier $rule_object The decorator
	 * to be affected by the execution of $condition_rule
	 * @return void
	 */
	protected function setCallbacksToAffectRule(
		Blackbox_Models_View_RuleConditions $condition_model,
		OLPBlackbox_Rule_CallbackContainer $condition_rule,
		OLPBlackbox_RuleDecorator_BehaviorModifier $rule_object)
	{
		if ($condition_model->action == Blackbox_Models_RuleConditionAction::ACTION_SKIP)
		{
			// run skip() on $rule_object when $condition_rule is invalid.
			$condition_rule->newOnValidCallback('skip', array(), $rule_object);
		}
		elseif ($condition_model->action == Blackbox_Models_RuleConditionAction::ACTION_REPLACE_WITH_FLAG)
		{
			// run setRuleValueFromFlag() on $rule_object when $condition_rule is valid.
			$condition_rule->newOnValidCallback(
				'setRuleValueFromFlag', 
				array(
					$this->getPropertySetRuleSource($condition_model->source), 
					$condition_model->flag
				),
				$rule_object
			);
		}
		elseif ($condition_model->action == Blackbox_Models_RuleConditionAction::ACTION_MAKE_NOT_SKIPPABLE
			|| $condition_model->action == Blackbox_Models_RuleConditionAction::ACTION_MAKE_SKIPPABLE)
		{
			$skip = $condition_model->action == Blackbox_Models_RuleConditionAction::ACTION_MAKE_SKIPPABLE ? TRUE : FALSE;
			$condition_rule->newOnValidCallback('setSkippable', array($skip), $rule_object);
		}
		elseif ($condition_model->action == Blackbox_Models_RuleConditionAction::ACTION_REPLACE_WITH_VALUE)
		{
			$condition_rule->newOnValidCallback('setRuleValue', array($condition_model->value), $rule_object);
		}
		elseif ($condition_model->action == Blackbox_Models_RuleConditionAction::ACTION_RUN_RULE)
		{
			$condition_rule->newOnInvalidCallback('skip', array(true), $rule_object);
		}
		else
		{
			throw new RuntimeException(
				"unsure how to assemble callback for {$condition_model->action}"
			);
		}
		
		// since we're taking care of wiring up the callbacks to the rule, we also
		// hook up the callbacks tot he rule.
		$rule_object->addCallbackRule($condition_rule);
	}
	
	/**
	 * Returns the correct kind of conditional rule to run based on the database
	 * configuration.
	 * 
	 * This is the actual "condition to be met" for rule conditions.
	 * 
	 * @throws InvalidArgumentException
	 * @param Blackbox_Models_View_RuleConditions $condition_model
	 * @return Blackbox_IRule
	 */
	protected function getConditionalRule(Blackbox_Models_View_RuleConditions $condition_model)
	{
		if ($condition_model->type == Blackbox_Models_RuleConditionType::COND_FLAG_SET
			|| $condition_model->type == Blackbox_Models_RuleConditionType::COND_FLAG_NOT_SET)
		{
			$class = ($condition_model->type == Blackbox_Models_RuleConditionType::COND_FLAG_SET
				? 'OLPBlackbox_Rule_PropertySet'
				: 'OLPBlackbox_Rule_PropertyNotSet');
			
			$rule = new $class(
				$condition_model->flag,
				$this->getPropertySetRuleSource($condition_model->source) 
			);
		}
		elseif ($condition_model->type == Blackbox_Models_RuleConditionType::COND_ALWAYS)
		{
			// this should happen all the time, there's no 'when' or 'if'
			$rule = new OLPBlackbox_Rule_Boolean();
			$rule->setRuleValue(TRUE);
		}
		elseif ($condition_model->type == Blackbox_Models_RuleConditionType::COND_FLAG_NOT_EQ
			|| $condition_model->type == Blackbox_Models_RuleConditionType::COND_FLAG_EQ) //self::RUN_WHEN_FLAG_COMPARES)
		{
			// TODO: not sure why there is no OLPBlackbox_RuleDecorator_Not but 
			// there should be.
			$class = ($condition_model->type == Blackbox_Models_RuleConditionType::COND_FLAG_NOT_EQ
				? 'OLPBlackbox_Rule_NotEqualsNoCase'
				: 'OLPBlackbox_Rule_EqualsNoCase');
			$rule = new $class();
			$rule->setupRule(array(
				Blackbox_StandardRule::PARAM_FIELD => $condition_model->flag,
				Blackbox_StandardRule::PARAM_VALUE => $condition_model->value,
			));
		}
		elseif ($condition_model->type == Blackbox_Models_RuleConditionType::COND_FLAG_IN)
		{
			$rule = new OLPBlackbox_Rule_PropertyIn(
				$condition_model->flag, 
				array_map('trim', explode(',', trim($condition_model->value))), 
				$this->getPropertySetRuleSource($condition_model->source)
			);
		}
		else 
		{
			throw new RuntimeException(
				"unsure how to assemble '{$condition_model->type}' conditional."
			);
		}
		if ($rule instanceof OLPBlackbox_Rule_IMultiDataSource && !empty($condition_model->source))
		{
			$rule->setDataSource($this->getPropertySetRuleSource($condition_model->source));
		}
		
		return $rule;
	}
	
	/**
	 * Determines the data source to use when setting up a PropertySet rule.
	 * @param string $database_source_name The database value for the source.
	 * @return string Name of the value to feed to a PropertySet rule. 
	 */
	protected function getPropertySetRuleSource($database_source_name)
	{
		if ($database_source_name == Blackbox_Models_RuleConditionSource::SOURCE_RUNTIME_CONFIG)
		{
			return OLPBlackbox_Config::DATA_SOURCE_CONFIG;
		}
		elseif ($database_source_name == Blackbox_Models_RuleConditionSource::SOURCE_APPLICATION_DATA)
		{
			return OLPBlackbox_Config::DATA_SOURCE_BLACKBOX;
		}
		elseif ($database_source_name == 'runtime state')
		{
			return OLPBlackbox_Config::DATA_SOURCE_STATE;
		}
		else
		{
			throw new RuntimeException(
				$database_source_name . ' is not a valid data source'
			);
		}
	}
}

?>
