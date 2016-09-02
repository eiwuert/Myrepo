<?php
/**
 * Factory for creating OLP Blackbox rules.
 *
 * @author Matthew Jump <matthew.jump@sellingsource.com>
 */
class OLPBlackbox_Factory_Rule extends OLPBlackbox_Factory_ModelFactory
{
	/**
	 * Keep track of simple rules we've instantiated.
	 *
	 * @var array of Blackbox_IRules
	 */
	protected static $references = array();
					
	/**
	 * 
	 * @var LenderAPI_Factory_Client
	 */
	protected $client_factory;
	
	/**
	 * 
	 * @var OLP_Message_Factory
	 */
	protected $message_factory;


	/**
	 * Sets up and returns an OLPBlackbox_Rule_* class.
	 * @param string $name This will be appended to "OLPBlackbox_Rule_" to
	 * produce the class name to be loaded.
	 * @param array $params The parameters to call {@see OLPBlackbox_Rule::setupRule} on.
	 * @return OLPBlackbox_Rule
	 */
	public function getOLPBlackboxRule($name, $params = array())
	{
		return $this->constructRule($name, $params);
	}

	/**
	 * Creates a hash to uniquely identify a rule.
	 * @param Blackbox_Models_Rule $rule_model The model to use to construct the
	 * hash.
	 * @return string sha1 hash.
	 */
	protected function createRuleHash(Blackbox_Models_Rule $rule_model)
	{
		return sha1($rule_model->rule_definition_id . $rule_model->rule_value);
	}

	/**
	 * Looks in the rule cache for a rule indexed by the rule hash. -- Dr. Seuss
	 * @param string $rule_hash The hash created by {@see self::createRuleHash()}
	 * @return NULL|Blackbox_IRule
	 */
	protected function getCachedRule($rule_hash)
	{
		if (array_key_exists($rule_hash, self::$references)
			&& self::$references[$rule_hash] instanceof Blackbox_IRule)
		{
			return self::$references[$rule_hash];
		}
		
		return NULL;
	}

	/**
	 * Store a rule in a cache.
	 * @param string $rule_hash The hash to index the rule with.
	 * @param Blackbox_IRule $rule The rule to cache.
	 * @return void
	 */
	protected function cacheRule($rule_hash, Blackbox_IRule $rule)
	{
		self::$references[$rule_hash] = $rule;
	}

	/**
	 * Checks if the rule can be stored and retrieved from the cache
	 *
	 * @param Blackbox_Models_Rule $rule_model
	 * @param array $rule_conditions List of rule conditions as produced by the
	 * rule condition factory.
	 * @return bool
	 */
	protected function isRuleCacheable(Blackbox_Models_Rule $rule_model, $rule_conditions)
	{
		// The rule can not be cached if it needs to check for conditions
		// because the conditions are not part of the hash.
		return !$rule_conditions;
	}
	
	/**
	 * Produce rule conditions to be attached to a behavior altering decorator.
	 * @param OLPBlackbox_RuleDecorator_BehaviorModifier $rule The decorator to
	 * attach the conditions to.
	 * @param int $rule_id The id of the rule being constructed.
	 * @return array List of rule condition objects.
	 */
	protected function getRuleConditions(OLPBlackbox_RuleDecorator_BehaviorModifier $rule, $rule_id)
	{
		$factory = $this->getRuleConditionsFactory();
		return $factory->getRuleConditions(
			$rule_id, 
			new Blackbox_Models_View_RuleConditions($this->getDbConnection()),
			$rule
		);
	}
	
	/**
	 * @return OLPBlackbox_Factory_RuleCondition
	 */
	protected function getRuleConditionsFactory()
	{
		return new OLPBlackbox_Factory_RuleCondition();
	}
	
	/**
	 * Retreive the list of event bus events to trigger on the rule.
	 *
	 * @param int $rule_id
	 * @return array
	 */
	protected function getRuleEvents($rule_id)
	{
		$model_factory = $this->getModelFactory();
		$rule_events_view = $model_factory->getViewModel('RuleEvents');
		
		$events = $rule_events_view->loadAllBy(array('rule_id' => $rule_id));
		
		return $events;
	}
	
	/**
	 * Returns a rule for the passed in rule_model
	 *
	 * @param Blackbox_Models_Rule $rule_model
	 * @return OLPBlackbox_Rule
	 */
	public function getRule(Blackbox_Models_Rule $rule_model)
	{
		$rule = NULL;
		$rule_definition_model = $this->getRuleDefinition(
			$rule_model->rule_definition_id
		);
				
		$conditional_decorator = new OLPBlackbox_RuleDecorator_BehaviorModifier(
			NULL, NULL /* $this->getConfig()->event_log - turn off to debug on live, TODO put back in [DO] */
		);
		$rule_conditions = $this->getRuleConditions(
			$conditional_decorator, $rule_model->rule_id
		);
		$rule_events = $this->getRuleEvents($rule_model->rule_id);
		
		$rule_is_cacheable = $this->isRuleCacheable($rule_model, $rule_conditions);

		if ($rule_is_cacheable)
		{
			$rule_hash = $this->createRuleHash($rule_model);

			if ($this->getCachedRule($rule_hash) instanceof Blackbox_IRule)
			{
				$rule = $this->getCachedRule($rule_hash);
			}
		}
		
		if (empty($rule))
		{
			if ($rule_definition_model->name_short == 'income_direct_deposit')
			{
				// Income_direct_deposit expects a true or false string
				// which is what is already stored in the db
				$rule_value = $rule_model->rule_value;
			}
			else
			{
				//Convert DB strings to intended types
				if (substr($rule_model->rule_value, 0, 2) == 'a:')
				{
					$rule_value = unserialize($rule_model->rule_value);
				}
				elseif ($rule_model->rule_value === 'TRUE')
				{
					$rule_value = TRUE;
				}
				elseif ($rule_model->rule_value === 'FALSE')
				{
					$rule_value = FALSE;
				}
				elseif (!isset($rule_value))
				{
					$rule_value = $rule_model->rule_value;
				}
			}
	
			if (is_string($rule_value) && trim($rule_value) == '')
			{
				return FALSE;
			}
	
			// suppression lists don't use the regular rule factories
			if ('suppression_lists' == $rule_definition_model->name_short)
			{
				$list_factory = $this->getSuppressionListFactory();
				$rule = $list_factory->getSuppressionLists($rule_value);
			}
	
			$field = $this->getRuleDefinitionField($rule_definition_model);
	
			$rule_action = $rule_model->rule_action;
	
			// suppression are already rules by this point
			if (isset($rule))
			{
				$rule_class = $rule;
			}
			else
			{
				$rule_class = $this->getRuleClassName($rule_definition_model->rule_class_id);
			}
	
			if (!($rule_class instanceof Blackbox_IRule))
			{
				$rule = $this->getOLPBlackboxRule($rule_class);
				$rule->setupRule(array(
					OLPBlackbox_Rule::PARAM_FIELD => $field,
					OLPBlackbox_Rule::PARAM_VALUE => $rule_value,
					OLPBlackbox_Rule::PARAM_ACTION => $rule_action,
				));	
			}
	
			if ($this->ruleIsSkippable($rule, $rule_definition_model))
			{
				$rule->setSkippable(TRUE);
			}
	
			if ($rule_definition_model->event)
			{
				$rule->setEventName($rule_definition_model->event);
			}
	
			if ($rule_definition_model->stat)
			{
				$rule->setStatName($rule_definition_model->stat);
			}
			
			if (!empty($rule_events))
			{
				foreach ($rule_events AS $event)
				{
					$rule->addEventTrigger($event->trigger, $event->event);
				}
			}
			
			if ($rule_is_cacheable && $rule instanceof OLPBlackbox_Factory_Legacy_IReusableRule)
			{
				$this->cacheRule($rule_hash, $rule);
			}			
		}

		// If the rule is not a sell rule and we're supposed to debug skip it, return a debugSkipRule rule
		if (!($rule instanceof OLPBlackbox_ISellRule)
			&& $this->ruleShouldDebugSkip($rule_definition_model->name_short))
		{
			$rule = new OLPBlackbox_DebugRule($rule_definition_model->event);
		}
		
		if ($rule_conditions)
		{
			$conditional_decorator->setRule($rule);
			$rule = $conditional_decorator;
		}
		
		return $rule;
	}
	
	/**
	 * Produce a class name from a rule_class_id by using a reference table model.
	 * @param int $rule_class_id The rule_class_id from the rule_class table.
	 * @return string Class name (unverified)
	 */
	protected function getRuleClassName($rule_class_id)
	{
		return $this->getModelFactory()
			->getReferenceTable('RuleClass', TRUE)
			->toName($rule_class_id);
	}
	
	/**
	 * Determines whether a rule is skippable.
	 * @todo Eventually this should check the mode for the rule, probably, and
	 * there should be a 'skippable' property in the rule_mode table.
	 * @param Blackbox_IRule $rule The actual rule that has been assembled.
	 * @param Blackbox_Models_RuleDefinition $rule_definition_model The model
	 * that holds the definition of the rule we need to determine skippable
	 * status on.
	 * @return bool TRUE if the rule can be skipped
	 */
	protected function ruleIsSkippable(
		Blackbox_IRule $rule,
		Blackbox_Models_RuleDefinition $rule_definition_model)
	{
		return (($this->getConfig()->blackbox_mode != OLPBlackbox_Config::MODE_BROKER
				&& !$rule instanceof OLPBlackbox_RuleCollection)
			|| $rule_definition_model->name_short == 'max_loan_amount_requested'
			|| $rule_definition_model->name_short == 'min_loan_amount_requested');
	}
	
	/**
	 * Determine the field that a blackbox rule should look at from the rule
	 * definition model.
	 * @param Blackbox_Models_RuleDefinition $rule_definition_model The definition to
	 * look at for the field.
	 * @return mixed string field or list of string fields.
	 */
	protected function getRuleDefinitionField(Blackbox_Models_RuleDefinition $rule_definition_model)
	{
		$field = $rule_definition_model->field;
		
		if ($field !== FALSE)
		{
			if (substr($field, 0, 2) == 'a:')
			{
				$field = unserialize($field);
			}
		}
		
		return $field;
	}

	/**
	 * Returns an instance of the OLPBlackbox_Factory_Legacy_SuppressionList.
	 *
	 * @return OLPBlackbox_Factory_Legacy_SuppressionList
	 */
	protected function getSuppressionListFactory()
	{
		return new OLPBlackbox_Factory_Legacy_SuppressionList();
	}

	/**
	 * Returns an instance of OLPBlackbox_Config.
	 *
	 * @return OLPBlackbox_Config
	 */
	protected function getConfig()
	{
		return OLPBlackbox_Config::getInstance();
	}
	
	/**
	 * Setup and return an instance of the OLPBlackbox_Rule_FrequencyScore rule.
	 *
	 * @param Blackbox_Models_Rule $rule_model The model to construct the
	 * frequency score with
	 * @return OLPBlackbox_Rule_FrequencyScore
	 */
	public function getFrequencyScoreRule(Blackbox_Models_Rule $rule_model)
	{		
		// Setup the frequency scoring rule
		if (strlen($rule_model->rule_value) > 3)
		{
			$frequency_limits = unserialize($rule_model->rule_value);
		}

		// If we didn't get a valid value or the serialization failed, give us an empty array
		if (!isset($frequency_limits) || $frequency_limits === FALSE)
		{
			$frequency_limits = array();
		}

		// Get the rule_definition model so we can get the field and class_model
		$rule_definition_model = $this->getRuleDefinition($rule_model->rule_definition_id);

		// Get the class_model so we can get the class name
		$rule_class_ref_table = $this->getModelFactory()->getReferenceTable('RuleClass', TRUE);

		// Create the rule
		$freq_rule = $this->constructRule(
			$rule_class_ref_table->toName($rule_definition_model->rule_class_id),
			array(
				OLPBlackbox_Rule::PARAM_FIELD => $rule_definition_model->field,
				OLPBlackbox_Rule::PARAM_VALUE => $frequency_limits
			)
		);

		return $freq_rule;
	}
	
	/**
	 * Constructs a rule from a name and some parameters.
	 * @param string $name
	 * @param array $params
	 * @return OLPBlackbox_Rule
	 */
	protected function constructRule($name, $params = array())
	{
		$class = 'OLPBlackbox_Rule_' . $name;
		
		$method = "construct{$name}Rule";
		if (method_exists($this, $method))
		{
			$instance = $this->$method();
		}
		else
		{
			if (!class_exists($class))
			{
				throw new Blackbox_Exception("Invalid rule $name given.");
			}
			
			$instance = new $class();
		}

		if (!empty($params))
		{
			$instance->setupRule($params);
		}

		return $instance;
	}

	/**
	 * Create a new multicampaign recur rule and pass
	 * it a blackbox factory.
	 * @return OLPBlabkox_Rule_MultiCampaignRecur
	 */
	protected function constructMultiCampaignRecurRule()
	{
		return new OLPBlackbox_Rule_MultiCampaignRecur($this->getOLPFactory());
	}

	/**
	 *
	 * @return OLPBlackbox_Rule_LeadSentToCampaign 
	 */
	protected function constructLeadSentToCampaignRule()
	{
		return new OLPBlackbox_Rule_LeadSentToCampaign($this->getOLPFactory());
	}

	/**
	 * Constructs a LenderPost rule.
	 *
	 * @return OLPBlackbox_Rule_LenderPost
	 */
	protected function constructLenderPostRule()
	{
		return $this->createLenderPostRule(LenderAPI_Generic_Client::POST_TYPE_STANDARD);
	}
	
	/**
	 * Creates a new lendor post rule.
	 * @param string $type
	 * @return OLPBlackbox_Rule_LenderPost
	 */
	public function createLenderPostRule($type)
	{
		$rule = new OLPBlackbox_Rule_LenderPost(
			$this->getLenderPostClientFactory()->getClient(
				OLPBlackbox_Config::getInstance()->mode,
				$type
			)
		);
		$rule->setPostType($type);
		
		$this->attachPostRecorder($rule);
		$this->attachLenderApiEventFirer($rule);
		
		return $rule;	
	}
	
	/**
	 * Attaches a post recorder to an observable rule
	 * @param OLPBlackbox_Rule_IObservable $rule
	 * @return void
	 */
	protected function attachPostRecorder(OLPBlackbox_Rule_IObservable $rule)
	{
		$rule->attach($this->createPostRecorder());
	}
	
	/**
	 * Returns a new post recorder instance
	 * @return OLPBlackbox_LenderAPIPostRecorder
	 */
	protected function createPostRecorder()
	{
		return new OLPBlackbox_LenderAPIPostRecorder(
			$this->getOLPFactory(),
			$this->getMessageFactory(),
			$this->getConfig()->applog);
	}
	
	/**
	 * Attach an observer to fire an event on lender api
	 * responses
	 * @param OLPBlackbox_Rule_IObservable $rule
	 * @return void
	 */
	protected function attachLenderApiEventFirer(OLPBlackbox_Rule_IObservable $rule)
	{
		if (!$this->getConfig()->event_bus instanceof OLP_IEventBus) return;
		$rule->attach(new OLPBlackbox_LenderAPIEventFirer($this->getConfig()->event_bus));
	}

	/**
	 * Return a message factory
	 * @return OLP_Message_Factory
	 */
	protected function getMessageFactory()
	{
		if (!$this->message_factory instanceof OLP_Message_Factory)
		{
			$message_config = new OLP_Message_Config();
			$message_config->setEnvironment(strtolower(OLPBlackbox_Config::getInstance()->mode));
			$this->message_factory = new OLP_Message_Factory($message_config);
		}
		return $this->message_factory;	
	}
	
	/**
	 * Should this rule debug skip based on the debug conf
	 *
	 * @param string $rule_name
	 * @return bool
	 */
	protected function ruleShouldDebugSkip($rule_name)
	{
		return $this->getDebug()->debugSkipRule(OLPBlackbox_DebugConf::RULES, $rule_name);
	}
	
	
	/**
	 *  Return an instance of the LenderAPI client factory
	 * @return LenderAPI_Factory_Client
	 */
	protected function getLenderPostClientFactory()
	{
		if (!$this->client_factory instanceof LenderAPI_Factory_Client)
		{
			$this->client_factory = new LenderAPI_Factory_Client();
		}
		return $this->client_factory;
	}

}
?>
