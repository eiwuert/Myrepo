<?php
/**
 * Defines the OLPBlackbox_Factory_Legacy_RuleCollection class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Factory for legacy olp rule collections.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Factory_Legacy_RuleCollection
{
	/**
	 * Array of rule collection factory instances.
	 * 
	 * Array will keep instances of the different company instances.
	 *
	 * @var array
	 */
	protected static $factory_instances = array();
	
	/**
	 * Instance of the rule factory to use.
	 *
	 * @var OLPBlackbox_Factory_Legacy_Rule
	 */
	protected $rule_factory;
	
	/**
	 * Constructor for OLPBlackbox_Factory_Legacy_RuleCollection.
	 *
	 * @param OLPBlackbox_Factory_Legacy_Rule $rule_factory the rule factory to use
	 */
	public function __construct(OLPBlackbox_Factory_Legacy_Rule $rule_factory)
	{
		$this->rule_factory = $rule_factory;
	}
	
	/**
	 * Returns an instance of OLPBlackbox_Factory_Legacy_RuleCollection.
	 *
	 * @param string $name_short the property short of the target we're getting rules for
	 * @return OLPBlackbox_Factory_Legacy_RuleCollection
	 */
	public static function getInstance($property_short = 'default')
	{
		/**
		 * Originally, we were passing in the company name, but it seems a little more intuitive to pass
		 * in the property short and then do the conversion here.
		 */
		$company_name = $property_short;
		if (strcasecmp($company_name, 'default') != 0)
		{
			$company_name = EnterpriseData::getCompany($property_short);
		}
		
		if (!isset(self::$factory_instances[$company_name]))
		{
			switch ($company_name)
			{
				case EnterpriseData::COMPANY_CLK:
					self::$factory_instances[$company_name] = new OLPBlackbox_Enterprise_CLK_Factory_Legacy_RuleCollection(
						OLPBlackbox_Factory_Legacy_Rule::getInstance($property_short)
					);
					break;
				case EnterpriseData::COMPANY_IMPACT:
					self::$factory_instances[$company_name] = new OLPBlackbox_Enterprise_Impact_Factory_Legacy_RuleCollection(
						OLPBlackbox_Factory_Legacy_Rule::getInstance($property_short)
					);
					break;
				case EnterpriseData::COMPANY_AGEAN:
					self::$factory_instances[$company_name] = new OLPBlackbox_Factory_Legacy_RuleCollection(
						OLPBlackbox_Factory_Legacy_Rule::getInstance($property_short)
					);
					break;
				default:
					self::$factory_instances[$company_name] = new OLPBlackbox_Factory_Legacy_RuleCollection(
						OLPBlackbox_Factory_Legacy_Rule::getInstance($property_short)
					);
					break;
			}
		}
		
		return self::$factory_instances[$company_name];
	}
	
	/**
	 * Gets an instance of a rule collection with the rules added.
	 *
	 * @param array $rules An array with all of the rules we want to add.
	 *
	 * @return OLPBlackbox_RuleCollection
	 */
	public function getRuleCollection($rules)
	{
		// will hold directives like NO_CHECKS, etc.
		$debug = OLPBlackbox_Config::getInstance()->debug;

		// If we have no_checks set, then just return a DebugRule
		if ($debug->debugSkipRule())
		{
			$rule = new OLPBlackbox_DebugRule();
			$rule->setEventName(OLPBlackbox_Config::EVENT_RULES);
			return $rule;
		}

		switch (OLPBlackbox_Config::getInstance()->blackbox_mode)
		{
			case OLPBlackbox_Config::MODE_ONLINE_CONFIRMATION:
				$rule = $this->getOnlineConfirmationRuleCollection($rules);
				break;
			case OLPBlackbox_Config::MODE_ECASH_REACT:
				$rule = $this->getReactRuleCollection($rules);
				break;
			case OLPBlackbox_Config::MODE_BROKER:
			default:
				$rule = $this->getDefaultRuleCollection($rules);
				break;
		}

		return $rule;
	}

	/**
	 * Returns the default rule collection.
	 *
	 * @param array $rules an array of rules we'll add to the collection
	 * @param array $allowed_rules an array of strings of rule names that this collection will be restricted to
	 * @return OLPBlackbox_RuleCollection
	 */
	protected function getDefaultRuleCollection(array $rules, array $allowed_rules = NULL)
	{
		// We found some rules we need to add, so setup a rule collection.
		$rule_collection = new OLPBlackbox_RuleCollection();
		$rule_collection->setEventName(OLPBlackbox_Config::EVENT_RULES);

		// Loop through each column and switch off of a hardcoded list of
		//   rules to set the appropriate BBx rule to run.
		foreach ($rules as $rule_name => $rule_value)
		{
			// If we have $allowed_rules set, check that the rule we're adding is allowed
			if (is_array($allowed_rules) && !in_array(strtolower($rule_name), $allowed_rules))
			{
				continue;
			}
			
			$rule = $this->rule_factory->getRule($rule_name, $rule_value);
			
			if ($rule instanceof Blackbox_IRule)
			{
				$rule_collection->addRule($rule);
			}
		}

		return $rule_collection;
	}
	
	/**
	 * Returns the rule collection containing the suppression lists.
	 *
	 * @param array $lists an array of lists to add to the collection
	 * @return OLPBlackbox_RuleCollection
	 */
	protected function getSuppressionLists(array $lists)
	{
		$suppression_list = new OLPBlackbox_Factory_Legacy_SuppressionList();
		
		return $suppression_list->getSuppressionLists($lists);
	}

	/**
	 * Generic online confirmation rule collection function.
	 * 
	 * This will just return the normal set of rules.
	 *
	 * @param array $rules an array of rules
	 * @return OLPBlackbox_RuleCollection
	 */
	protected function getOnlineConfirmationRuleCollection(array $rules)
	{
		$allowed_rules = array(
			'suppression_lists'
		);
		return $this->getDefaultRuleCollection($rules, $allowed_rules);
	}
	
	/**
	 * Returns a rule collection, stripped down for react rules.
	 *
	 * @param array $rules
	 * @return OLPBlackbox_RuleCollection
	 */
	protected function getReactRuleCollection(array $rules)
	{
		$allowed_rules = array(
			'suppression_lists',
			'military',
			'minimum_income',
			'excluded_states',
			'restricted_states'
		);
		
		return $this->getDefaultRuleCollection($rules, $allowed_rules);
	}
}
?>
