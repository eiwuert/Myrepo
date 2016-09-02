<?php
/**
 * Defines the OLPBlackbox_Enterprise_CLK_Factory_Legacy_Target class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Factory for legacy olp clk specific target.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Enterprise_CLK_Factory_Legacy_Target
{
	/**
	 * Gets an instance of a target with the CLK specific stuff...
	 *
	 * @param array $target_row An array with the target information
	 *
	 * @return Blackbox_ITarget
	 */
	public static function getTarget(array $target_row)
	{
		// Create the target collection.
		$target = new OLPBlackbox_Enterprise_CLK_Target($target_row['property_short'], $target_row['target_id']);
		$target->setRules(self::getRules($target_row));
		$target->setPickTargetRules(self::getPickTargetRules($target_row));

		return $target;
	}

	/**
	 * Returns a rule collection for rules that run during Target::isValid()
	 *
	 * @param array $target_row Row from database containing target info.
	 *
	 * @return Blackbox_RuleCollection
	 */
	protected static function getRules(array $target_row)
	{
		// Get the RuleCollection factory instance
		$rule_collection_factory = OLPBlackbox_Factory_Legacy_RuleCollection::getInstance($target_row['property_short']);

		// create base (generic) rules (uses config, no need to pass debug info)
		$rule_collection = new Blackbox_RuleCollection();
		$rule_collection->addRule($rule_collection_factory->getRuleCollection($target_row));
		$rule_collection->addRule(
			OLPBlackbox_Factory_Legacy_LimitCollection::getLimitCollection($target_row)
		);

		return $rule_collection;
	}

	/**
	 * Returns a rule collection of rules that run during Target::pickTarget()
	 *
	 * @param array $target_row Row from db containing target info.
	 *
	 * @return Blackbox_RuleCollection
	 */
	protected static function getPickTargetRules(array $target_row)
	{
		$blackbox_mode = OLPBlackbox_Config::getInstance()->blackbox_mode;

		// rules that will be run during the pickTarget() phase of bb
		$pick_target_rules = new Blackbox_RuleCollection();

		// Setup frequency scoring rule
		if ($blackbox_mode !== OLPBlackbox_Config::MODE_ONLINE_CONFIRMATION
			&& $blackbox_mode !== OLPBlackbox_Config::MODE_ECASH_REACT)
		{
			$pick_target_rules->addRule(OLPBlackbox_Factory_Legacy_Target::getFrequencyScoreRule($target_row));
		}

		// qualify
		$pick_target_rules->addRule(self::getQualifyRule());

		// DataX/Verify stuff is only done in BROKER, and they must be run last,
		// in the order provided (DataX then Verify)
		if ($blackbox_mode == OLPBlackbox_Config::MODE_BROKER)
		{
			$pick_target_rules->addRule(self::getDataxPerfRule($target_row['property_short']));
			$pick_target_rules->addRule(self::getVerificationRule($target_row['property_short']));
		}

		return $pick_target_rules;
	}

	/**
	 * Gets the qualify rule.
	 *
	 * @return Blackbox_IRule
	 */
	protected static function getQualifyRule()
	{
		// Add qualify rule
		$qualify_rule = new OLPBlackbox_Enterprise_Generic_Rule_LegacyQualifiesForAmount();
		if (OLPBlackbox_Config::getInstance()->blackbox_mode == OLPBlackbox_Config::MODE_PREQUAL)
		{
			$qualify_rule->setSkippable(TRUE);
		}
		return $qualify_rule;
	}

	/**
	 * Gets the verification rules
	 *
	 * @param string $target_name Row from the database containing target info.
	 *
	 * @return Blackbox_IRule
	 */
	protected static function getVerificationRule($target_name)
	{
		// debugging is covered by the verify rules themselves
		if ($target_name == 'UFC')
		{
			return new OLPBlackbox_Enterprise_CLK_UFC_Rule_WinnerVerifiedStatus();
		}
		return new OLPBlackbox_Enterprise_CLK_Rule_WinnerVerifiedStatus();
	}

	/**
	 * Gets the Datax performance rule
	 *
	 * @param string $target_name Target name (aka property_short for legacy)
	 * @return Blackbox_IRule
	 */
	protected static function getDataxPerfRule($target_name)
	{
		// OLPBlackbox_DebugConf used for debugging flag retrieval
		$debug = OLPBlackbox_Config::getInstance()->debug;

		if ($debug->debugSkipRule(OLPBlackbox_DebugConf::DATAX_PERF))
		{
			$datax_rule = new OLPBlackbox_DebugRule();
		}
		else
		{
			$datax_rule = new OLPBlackbox_Enterprise_CLK_Rule_DataX(
				OLPBlackbox_Enterprise_CLK_Rule_DataX::TYPE_PERF,
				$target_name
			);
		}

		$datax_rule->setEventName(OLPBlackbox_Config::EVENT_DATAX_PERF);

		return $datax_rule;
	}
}
?>
