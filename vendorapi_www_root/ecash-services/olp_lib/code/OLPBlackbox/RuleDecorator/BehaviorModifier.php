<?php

/**
 * A decorator which wraps an OLPBlackbox_Rule and can modify it's runtime
 * behavior for rule conditions.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_RuleDecorator_BehaviorModifier implements Blackbox_IRule
{
	const SHOULD_SKIP = 'skip';
	const RULE_VALUE_USE_SOURCE = 'rule_value_use_source';
	const RULE_VALUE_USE_FLAG = 'rule_value_use_flag';
	
	/**
	 * Rule we'd like to alter the behavior of (potentially).
	 * @var OLPBlackbox_Rule $rule
	 */
	protected $rule;

	/**
	 * @var Event_Log
	 */
	protected $event_log;
	
	/**
	 * List of flags defining how this rule uses it's sub-rule.
	 * @var array
	 */
	protected $behaviors = array();
	
	/**
	 * List of callback items.
	 * @var array
	 */
	protected $callback_rules = array();
	
	/**
	 * Create a new decorator to modify the behavior of the $rule passed in.
	 * 
	 * @param OLPBlackbox_Rule $rule The rule to alter the behavior of.
	 * @return void
	 */
	function __construct(OLPBlackbox_Rule $rule = NULL, $event_log = NULL)
	{
		if ($rule) $this->setRule($rule);
		
		if (is_object($event_log) && method_exists($event_log, 'Log_Event'))
		{
			// TODO: Change this to an interface 
			$this->event_log = $event_log;
		}
	}
	
	/**
	 * String version of this callback for debugging purposes.
	 * @return string
	 */
	public function __toString()
	{
		$callbacks = array();
		foreach ($this->callback_rules as $callback_rule)
		{
			$callbacks[] = strval($callback_rule);
		}
		return sprintf("Runtime Conditional:\nCallbacks: %s\nRule: %s\n",
			implode("\n", $callbacks),
			strval($this->rule)
		);
	}
	
	/**
	 * Set the rule, necessary when, for factory/creation problems the rule is
	 * not available when the decorator is being set up.
	 * @param OLPBlackbox_Rule $rule The rule to decorate.
	 * @return void
	 */
	public function setRule(Blackbox_IRule $rule)
	{
		$this->validateDelegateRule($rule);
		$this->rule = $rule;
	}
	
	/**
	 * Run the underlying rule (or skip it) based on the behavior set up on this
	 * decorator.
	 * 
	 * @see Blackbox_IRule::isValid()
	 * @param mixed $data The data used to validate the rule. 
	 * @param obj $state_data The mutable state data object for the ITarget running the rule. 
	 * @return bool TRUE if valid, FALSE otherwise
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (!$this->rule instanceof Blackbox_IRule)
		{
			throw new RuntimeException('cannot run without rule');
		}
		
		foreach ($this->callback_rules as $callback_rule)
		{
			$callback_rule->isValid($data, $state_data);
		}
		
		$this->modifyRuleValue($data, $state_data, $this->getConfig());		
		
		$return = TRUE;
		
		if (!$this->behavior(self::SHOULD_SKIP))
		{
			$return = $this->rule->isValid($data, $state_data);
		}
		else 
		{
			$this->logSkipEvent($data, $state_data);
		}
		
		return $return;
	}
	
	/**
	 * If behavior is set to change the value of {@see $this->rule}, change it
	 * here.
	 * 
	 * @param Blackbox_Data $data Application data.
	 * @param Blackbox_IStateData $state_data Blackbox run state.
	 * @return void
	 */
	protected function modifyRuleValue(
		Blackbox_Data $data,
		Blackbox_IStateData $state_data
	)
	{
		$class_name = $this->behavior(self::RULE_VALUE_USE_SOURCE);
		$flag = $this->behavior(self::RULE_VALUE_USE_FLAG);
		
		if ($class_name && $flag)
		{
			foreach (array($data, $state_data, $this->getConfig()) as $source)
			{
				if ($source instanceof $class_name)
				{
					if (!$this->rule instanceof OLPBlackbox_Rule_IHasValueAccess)
					{
						throw new InvalidArgumentException(
							get_class($this->rule) . ' is not OLPBlackbox_Rule_IHasValueAccess'
						);
					}
					
					$this->rule->setRuleValue($source->$flag);
					break;
				}
			}
		}
	}
	
	/**
	 * Add a callback to be run prior to the other behavior in isValid.
	 * 
	 * For runtime conditions, these callbacks will modify $this object, but it's
	 * entirely possible for that not to happen ... but that might produce results
	 * which are difficult to trace, so be careful about that.
	 * 
	 * @param OLPBlackbox_Rule_CallbackContainer $rule A callback to run.
	 * @return void
	 */
	public function addCallbackRule(OLPBlackbox_Rule_CallbackContainer $rule)
	{
		$this->callback_rules[] = $rule;
	}
	
	/**
	 * @return array List of OLPBlackbox_Rule_CallbackContainer objects.
	 */
	public function getCallbackRules()
	{
		return $this->callback_rules;
	}
	
	/**
	 * Make the rule that this object is decorating skippable.
	 * @param bool $bool TRUE if the rule should be skippable, otherwise FALSE
	 * @return void
	 */
	public function setSkippable($bool)
	{
		$this->rule->setSkippable($bool);
	}
	
	/**
	 * Tells the decorator to pull a value from a data source ($source) such as
	 * blackbox data or state data and set the underlying rule's value to that.
	 * 
	 * @param string $source Class name for the source (Blackbox_Data, etc.) which
	 * is available during isValid()
	 * @param string $flag The property to check on the $source.
	 * @return void
	 */
	public function setRuleValueFromFlag($source, $flag)
	{
		if (!OLPBlackbox_Config::isValidDataSource($source))
		{
			throw new InvalidArgumentException("$source is not a valid source type");
		}
		
		$this->setBehavior(self::RULE_VALUE_USE_SOURCE, $source);
		$this->setBehavior(self::RULE_VALUE_USE_FLAG, $flag);
	}
	
	/**
	 * Tell the decorator to skip the execution of the rule.
	 * @param bool $bool TRUE to skip, FALSE otherwise
	 * @return void
	 */
	public function skip($bool = TRUE)
	{
		$this->setBehavior(self::SHOULD_SKIP, $bool);
	}
	
	/**
	 * Log the fact that the rule being decorated has been skipped.
	 * 
	 * @param Blackbox_Data $data The application data being processed.
	 * @param Blackbox_IStateData $state_data The state of the branch of Blackbox being run.
	 * @return void
	 */
	protected function logSkipEvent(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if ($this->event_log)	// TODO: instanceof Event_Log)
		{
			$this->event_log->Log_Event(
				$this->rule->getEventName(),
				'CONDITIONAL_SKIP',
				$state_data->name,
				$data->application_id,
				$this->getConfig()->blackbox_mode
			);
		}
	}
	
	/**
	 * Makes sure that the rule passed in is both skippable and can have it's 
	 * value accessed.
	 * 
	 * @throws InvalidArgumentException
	 * @param Blackbox_IRule $rule The rule to validate. 
	 * @return void
	 */
	protected function validateDelegateRule(Blackbox_IRule $rule)
	{
		if (!$rule instanceof OLPBlackbox_Rule_ISkippable)
		{
			throw new InvalidArgumentException(
				get_class($rule) . ' is not OLPBlackbox_Rule_ISkippable'
			);
		}
	}
	
	/**
	 * Remember a behavior to apply during {@see isValid()}.
	 * @param string $name The name of the behavior, eg: self::SHOULD_SKIP
	 * @param mixed $value The value of the behavior, eg: TRUE
	 * @return void
	 */
	protected function setBehavior($name, $value)
	{
		$this->behaviors[$name] = $value;
	}
	
	/**
	 * Check if the decorator is supposed to act in a certain way.
	 * @param string $name the name of the behavior to check.
	 * @return mixed|NULL The behavior's result.
	 */
	protected function behavior($name)
	{
		return array_key_exists($name, $this->behaviors)
			? $this->behaviors[$name]
			: NULL;
	}
	
	/**
	 * Get the configuration for this Decorator.
	 * @todo pass this in, why do we have a get?
	 * @return OLPBlackbox_Config
	 */
	protected function getConfig()
	{
		return OLPBlackbox_Config::getInstance();
	}
}

?>