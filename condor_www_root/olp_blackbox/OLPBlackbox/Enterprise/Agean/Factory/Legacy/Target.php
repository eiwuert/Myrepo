<?php
/**
 * Defines the OLPBlackbox_Factory_Legacy_TargetAgean class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Factory for legacy olp clk specific target.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Agean_Factory_Legacy_Target
{
	/**
	 * Gets an instance of a target with the Agean specific stuff...
	 *
	 * @param array $target_row An array with the target information
	 *
	 * @return ITarget
	 */
	public static function getTarget($target_row)
	{
		$config = OLPBlackbox_Config::getInstance();

		// Create the target collection.
		$target = new OLPBlackbox_Enterprise_Target($target_row['property_short'], $target_row['target_id']);
		
		$ordered_rule_collection = new OLPBlackbox_OrderedRuleCollection();

		$ordered_rule_collection->addRule(self::getRules($target_row));

		$target->setPickTargetRules(self::getPickTargetRules($target_row));
		$target->setRules($ordered_rule_collection);
		return $target;
	}

	/**
	 * Returns a rule collection of the basic Blackbox rules.
	 *
	 * @param array $target_row an array of rule information for the target
	 * @return OLPBlackbox_RuleCollection
	 */
	protected static function getRules(array $target_row)
	{
		$rule_collection = new OLPBlackbox_RuleCollection();
		$rule_factory = OLPBlackbox_Factory_Legacy_RuleCollection::getInstance($target_row['property_short']);
		$rule_collection->addRule($rule_factory->getRuleCollection($target_row));

		if (OLPBlackbox_Config::getInstance()->title_loan
			&& (OLPBlackbox_Config::getInstance()->blackbox_mode == OLPBlackbox_Config::MODE_BROKER
				|| OLPBlackbox_Config::getInstance()->blackbox_mode == OLPBlackbox_Config::MODE_ECASH_REACT))
		{
			$rule_collection->addRule(self::getTitleLoanRules());
		}

		if (OLPBlackbox_Config::getInstance()->blackbox_mode == OLPBlackbox_Config::MODE_BROKER)
		{
			$rule_collection->addRule(OLPBlackbox_Factory_Legacy_LimitCollection::getLimitCollection($target_row));
			$rule_collection->addRule(new OLPBlackbox_Enterprise_Generic_Rule_UsedABACheck(array($target_row['property_short'])));
		}

		return $rule_collection;
	}

	/**
	 * Returns a rule collection of the pickTarget rules.
	 *
	 * @param array $target_row an array of rule information for the target
	 * @return OLPBlackbox_RuleCollection
	 */
	protected static function getPickTargetRules(array $target_row)
	{
		$pick_target_rules = new Blackbox_RuleCollection();

		if (OLPBlackbox_Config::getInstance()->blackbox_mode == OLPBlackbox_Config::MODE_BROKER)
		{
			// Setup frequency scoring rule
			$pick_target_rules->addRule(OLPBlackbox_Factory_Legacy_Target::getFrequencyScoreRule($target_row));
		}

		$pick_target_rules->addRule(self::getPreviousCustomerRule($target_row));

		// set up the Qualify rule
		$pick_target_rules->addRule(new OLPBlackbox_Enterprise_Agean_Rule_QualifiesForAmount());

		if (OLPBlackbox_Config::getInstance()->blackbox_mode == OLPBlackbox_Config::MODE_BROKER 
			|| (OLPBlackbox_Config::getInstance()->blackbox_mode == MODE_ECASH_REACT && !OLPBlackbox_Config::getInstance()->verified_react))
		{
			// add DataX rule second to last.
			$pick_target_rules->addRule(self::getDataXRule($target_row['property_short']));

			// OLPBlackbox_Enterprise_Agean_Rule_WinnerVerifiedStatus takes care of debugging and mode stuff (MUST ADD LAST)
			$pick_target_rules->addRule(new OLPBlackbox_Enterprise_Agean_Rule_WinnerVerifiedStatus());
		}

		return $pick_target_rules;
	}

	/**
	 * Gets the previous customer rule
	 *
	 * @return Blackbox_IRule
	 */
	protected static function getPreviousCustomerRule(array $row)
	{
		$f = OLPBlackbox_Enterprise_Generic_Factory_Legacy_PreviousCustomer::getInstance(
			$row['property_short'],
			OLPBlackbox_Config::getInstance()
		);
		return $f->getPreviousCustomerRule();
	}

	/**
	 * Returns a DataX rule instance.
	 *
	 * @param string $target_name the name of the target
	 * @return OLPBlackbox_Enterprise_Agean_Rule_DataX
	 */
	protected static function getDataXRule($target_name)
	{
		// Agean has a title loan specific DataX call
		if (OLPBlackbox_Config::getInstance()->title_loan)
		{
			$datax_event_name = OLPBlackbox_Config::EVENT_DATAX_AGEAN_TITLE;
			$datax_call_type = OLPBlackbox_Enterprise_Agean_Rule_DataX::TYPE_AGEAN_TITLE;
		}
		else
		{
			// non-title loan
			$datax_event_name = OLPBlackbox_Config::EVENT_DATAX_AGEAN_PERF;
			$datax_call_type = OLPBlackbox_Enterprise_Agean_Rule_DataX::TYPE_AGEAN_PERF;
		}

		if (OLPBlackbox_Config::getInstance()->debug->debugSkipRule(OLPBlackbox_DebugConf::DATAX_PERF))
		{
			$datax_rule = new OLPBlackbox_DebugRule();
		}
		else
		{
			$datax_rule = new OLPBlackbox_Enterprise_Agean_Rule_DataX($datax_call_type, $target_name);
		}
		$datax_rule->setEventName($datax_event_name);

		return $datax_rule;
	}

	/**
	 * Returns a rule collection with the title loan rules.
	 *
	 * @return OLPBlackbox_RuleCollection
	 */
	protected static function getTitleLoanRules()
	{
		$config = OLPBlackbox_Config::getInstance();
		$debug = $config->debug;

		$rule_collection = new OLPBlackbox_RuleCollection();

		$vehicle_year_event = 'VEHICLE_YEAR';
		$vehicle_milage_event = 'VEHICLE_MILEAGE';
		$title_loan_event = 'TITLE_LOAN_EXCL_STATES';

		if ($debug->debugSkipRule())
		{
			$rule_collection->addRule(new OLPBlackbox_DebugRule($title_loan_event));
			$rule_collection->addRule(new OLPBlackbox_DebugRule($vehicle_milage_event));
			$rule_collection->addRule(new OLPBlackbox_DebugRule($vehicle_year_event));
		}
		else
		{
			$rule = OLPBlackbox_Factory_Rules::getRule('NotIn');
			$rule->setupRule(array(
				OLPBlackbox_Rule::PARAM_FIELD => 'home_state',
				OLPBlackbox_Rule::PARAM_VALUE  => array('AK')
			));
			$rule->setEventName($title_loan_event);
			$rule->setStatName(strtolower($title_loan_event));
			$rule_collection->addRule($rule);

			$rule = OLPBlackbox_Factory_Rules::getRule('LessThan');
			$rule->setupRule(array(
				OLPBlackbox_Rule::PARAM_FIELD => 'vehicle_mileage',
				OLPBlackbox_Rule::PARAM_VALUE  => 150000
			));
			$rule->setEventName($vehicle_milage_event);
			$rule->setStatName(strtolower($vehicle_milage_event));
			$rule_collection->addRule($rule);

			$rule = OLPBlackbox_Factory_Rules::getRule('GreaterThanEquals');
			$rule->setupRule(array(
				OLPBlackbox_Rule::PARAM_FIELD => 'vehicle_year',
				OLPBlackbox_Rule::PARAM_VALUE  => 1998
			));
			$rule->setEventName($vehicle_year_event);
			$rule->setStatName(strtolower($vehicle_year_event));
			$rule_collection->addRule($rule);
		}

		return $rule_collection;
	}
}
?>
