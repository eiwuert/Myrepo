<?php

/**
 * Adds event log stuff to the base rule collection
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_RuleCollection extends Blackbox_RuleCollection implements OLP_ISubscriber, OLPBlackbox_Rule_ISkippable
{
	const PASS = 'pass';
	const FAIL = 'fail';

	/**
	 * The event name that will be hit for this rule.
	 *
	 * @var string
	 */
	protected $event_name;

	/**
	 * The stat name that will be hit for this rule.
	 *
	 * @var string
	 */
	protected $stat_name;
	
	/**
	 * Event bus events to hit based upon triggers.
	 *
	 * @param array
	 */
	protected $event_triggers = array();
	
	/**
	 * Sets the event name for a rule.
	 *
	 * @param string $event the name of the event
	 * @return void
	 */
	public function setEventName($event)
	{
		$this->event_name = $event;
	}

	/**
	 * Sets the stat name for a rule.
	 *
	 * @param string $stat the name of the stat
	 * @return void
	 */
	public function setStatName($stat)
	{
		$this->stat_name = $stat;
	}
	
	/**
	 * Adds event bus events for a rule.
	 *
	 * @param string $trigger
	 * @param string $event_type
	 * @return void
	 */
	public function addEventTrigger($trigger, $event_type)
	{
		$trigger = strtolower($trigger);
		
		if (!isset($this->event_triggers[$trigger])) $this->event_triggers[$trigger] = array();
		
		$this->event_triggers[$trigger][] = $event_type;
	}
	
	/**
	 * Returns either the rule at the index specified or NULL.
	 *
	 * @param int $index
	 * @return Blackbox_IRule|NULL
	 */
	public function getRuleAtIndex($index)
	{
		if (array_key_exists($index, $this->rules)
			&& $this->rules[$index] instanceof Blackbox_IRule)
		{
			return $this->rules[$index];
		}
		
		return NULL;
	}

	/**
	 * Overloads base isValid and adds event hitting
	 *
	 * @param Blackbox_Data $data data passed to validate the collection
	 * @param Blackbox_IStateData $state_data state data passed to validate the collection
	 * @return bool
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->sendEvent(OLPBlackbox_Event::EVENT_VALIDATION_START);
		$valid = parent::isValid($data, $state_data);

		// hit pass/fail event
		$result = ($valid ? self::PASS : self::FAIL);
		$this->hitRuleEvent($result, $data, $state_data);

		$this->sendEvent(OLPBlackbox_Event::EVENT_VALIDATION_END);
		$this->triggerEvents($valid ? 'OnValid' : 'OnInvalid', $state_data);
		return $valid;
	}
	
	/**
	 * Called by the parent each time a rule is to be run.
	 *
	 * @param Blackbox_IRule $rule The rule to run.
	 * @param Blackbox_Data $data The application data to run the rule with.
	 * @param Blackbox_IStateData $state_data The current state of blackbox.
	 * @return bool Whether the rule passes (TRUE) or not (FALSE).
	 */
	protected function runRule(Blackbox_IRule $rule, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->sendEvent(OLPBlackbox_Event::EVENT_NEXT_RULE);
		return parent::runRule($rule, $data, $state_data);
	}
	
	/**
	 * If an event bus is present, send this event.
	 *
	 * @param string $event_type The type of event to send, see the constants
	 * in class OLPBlackbox_Event.
	 * @return void
	 */
	protected function sendEvent($event_type)
	{
		if ($this->getEventBus() instanceof OLP_IEventBus)
		{
			$this->getEventBus()->notify(
				new OLPBlackbox_Event($event_type, $this->getDefaultEventAttrs())
			);
		}
	}
	
	/**
	 * Default attributes for an event sent from this object.
	 *
	 * @return array
	 */
	protected function getDefaultEventAttrs()
	{
		return array(
			OLPBlackbox_Event::ATTR_SENDER => OLPBlackbox_Event::TYPE_RULE_COLLECTION,
			'class' => get_class($this),
			OLPBlackbox_Event::ATTR_SENDER_HASH => spl_object_hash($this),
		);
	}
	
	/**
	 * Returns either the event bus or NULL.
	 *
	 * @return OLP_IEventBus|NULL
	 */
	protected function getEventBus()
	{
		if ($this->getConfig() instanceof OLPBlackbox_Config
			&& $this->getConfig()->event_bus instanceof OLP_IEventBus)
		{
			return $this->getConfig()->event_bus;
		}
		
		return NULL;
	}
	
	/**
	 * Implemented for OLP_ISubscriber interface, accepts EventBus events.
	 *
	 * @param OLP_IEvent $event The event which has occurred.
	 * @return void
	 */
	public function notify(OLP_IEvent $event)
	{
		if (in_array($event->getType(), array(
			OLPBlackbox_Event::EVENT_BLACKBOX_TIMEOUT,
			OLPBlackbox_Event::EVENT_GLOBAL_MILITARY_FAILURE,
		)))
		{
			$this->valid = FALSE;
		}
	}
	
	/**
	 * Prepend a rule to this collection's list of rules.
	 * @post The rule passed in will be at the beginning of this collection's
	 * list of rules.
	 * @param Blackbox_IRule $rule The rule to prepend.
	 * @return void
	 */
	public function prependRule(Blackbox_IRule $rule)
	{
		array_unshift($this->rules, $rule);
	}

	/**
	 * Hits the event name for this rule.
	 *
	 * Returns TRUE if the event was hit successfully, FALSE on fail.
	 *
	 * @param string $result the result of the rule, used as the response for the event
	 * @param Blackbox_Data $data the data passed to the rule
	 * @param Blackbox_IState $state_data the state data passed to the rule
	 * @return bool
	 */
	protected function hitRuleEvent($result, Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (strlen($this->event_name) > 0)
		{
			$target_name = empty($state_data->campaign_name) ? $state_data->name : $state_data->campaign_name;
			
			$this->hitEvent(
				$this->event_name,
				$result,
				$data->application_id,
				$target_name,
				$this->getConfig()->blackbox_mode
			);
		}
	}

	/**
	 * Hits an event in the event log.
	 *
	 * @param string $name           the name of the event to hit
	 * @param string $result         the reulst of the event
	 * @param int    $application_id the application ID to hit the event on
	 * @param string $target         the target associated with the event
	 * @param string $mode           the mode of the event
	 * @return void
	 */
	protected function hitEvent($name, $result, $application_id, $target = NULL, $mode = NULL)
	{
		OLPBlackbox_Config::getInstance()->event_log->Log_Event(
			$name,
			$result,
			$target,
			$application_id,
			$mode
		);
	}
	
	/**
	 * Trigger events for this rule.
	 *
	 * @param $trigger
	 * @param Blackbox_IStateData $state_data
	 * @return void
	 */
	protected function triggerEvents($trigger, Blackbox_IStateData $state_data)
	{
		$trigger = strtolower($trigger);
		
		if (isset($this->event_triggers[$trigger]))
		{
			foreach ($this->event_triggers[$trigger] AS $event_type)
			{
				$this->sendEvent($event_type, $state_data);
			}
		}
	}

	/**
	 * To facilitate unit testing this function should be how rules get the config.
	 *
	 * @return OLPBlackbox_Config object
	 */
	protected function getConfig()
	{
		return OLPBlackbox_Config::getInstance();
	}
	
	/**
	 * Dummy function so we can be skipped by RuleConditions.
	 *
	 * @param bool $skippable
	 * @return void
	 */
	public function setSkippable($skippable = TRUE)
	{
	}
	
	/**
	 * Removes a rule specified by object.
	 * @param Blackbox_IRule $rule The rule to remove from this collection,
	 * assumedly gotten by iterating this collection.
	 * @return void
	 */
	public function removeRule(Blackbox_IRule $rule)
	{
		$remove = NULL;
		
		foreach ($this->rules as $k => $r)
		{
			if ($rule === $r)
			{
				$remove = $k;
			}
		}
		
		if ($remove !== NULL)
		{
			unset($this->rules[$remove]);
			$this->rules = array_values($this->rules);
		}
	}
	
	/**
	 * Orders rules in the rules collection. This is needed if there is
	 * preference of order in running the rules. One case might be running
	 * the military rule before all the others.
	 *
	 * @param array $rule_instances - instances of the rules in order
	 * @return void
	 */
	public function orderRules(array $rule_instances)
	{
		if (!empty($rule_instances))
		{
			$rule_instances = array_reverse($rule_instances, TRUE);
			
			foreach ($rule_instances as $instance)
			{
				$this->pushToTop($instance);
			}
		}
	}
	
	/**
	 * Find the given instance in the collection and move it to the top so that it would run first
	 *
	 * @param Blackbox_IRule $instance
	 * @return void
	 */
	protected function pushToTop(Blackbox_IRule $instance)
	{
		if (!empty($this->rules))
		{
			foreach ($this->rules as $index => $rule)
			{
				if ($rule instanceof $instance)
				{
					$temp = $this->rules[$index];
					unset($this->rules[$index]);
					
					$this->rules = array_merge(array($temp), $this->rules);
					break;
				}
			}
		}
	}
}

?>