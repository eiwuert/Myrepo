<?php
/**
 * Defines the OLPBlackbox_Factory_Legacy_TargetLCS class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Class to assemble targets for LCS.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Enterprise_LCS_Factory_Legacy_Target
{
	/**
	 * Produce instances of ITargets with Impact specific properties.
	 *
	 * @param array $target_row An array with target information.
	 *
	 * @return ITarget instance.
	 */
	public static function getTarget($target_row)
	{
		// figure out the real property short
		$target_name = strtolower(EnterpriseData::resolveAlias($target_row['property_short']));

		// target we will return
		$target = new OLPBlackbox_Enterprise_CFETarget($target_row['property_short'], $target_row['target_id']);

		// create base (generic) rules (uses config, no need to pass debug info)
		$rule_collection = new Blackbox_RuleCollection();
		$rule_factory = OLPBlackbox_Factory_Legacy_RuleCollection::getInstance($target_row['property_short']);
		$rule_collection->addRule($rule_factory->getRuleCollection($target_row));
		$rule_collection->addRule(OLPBlackbox_Factory_Legacy_LimitCollection::getLimitCollection($target_row));
		$rule_collection->addRule(
			new OLPBlackbox_Enterprise_Generic_Rule_UsedABACheck(
				array($target_row['property_short'])
			)
		);
		
		// create rules to be run during the pickTarget() phase of bb
		$pick_target_rules = new Blackbox_RuleCollection();
		
		$pick_target_rules->addRule(self::getPreviousCustomerRule($target_row));

		// Add qualify rule
		$pick_target_rules->addRule(
			new OLPBlackbox_Enterprise_Generic_Rule_QualifiesForAmount()
		);

		if (OLPBlackbox_Config::getInstance()->blackbox_mode == OLPBlackbox_Config::MODE_BROKER)
		{
			// Add frequency scoring
			$pick_target_rules->addRule(
				OLPBlackbox_Factory_Legacy_Target::getFrequencyScoreRule($target_row)
			);

			$pick_target_rules->addRule(
				new OLPBlackbox_Enterprise_Generic_Rule_WinnerVerifiedStatus()
			);

			$pick_target_rules->addRule(
				self::getDataXPerfRule($target_row['property_short'])
			);
		}

		$target->setRules($rule_collection);
		$target->setPickTargetRules($pick_target_rules);

		return $target;
	}

	/**
	 * Gets the previous customer rule
	 *
	 * @param array $row
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
	 * Generate a DataX Perf rule for LCS.
	 * Added for GForge Ticket #9883 [AE]
	 * Copied from AALM Target
	 *
	 * @param string $account_name DataX account name. (usually target_name)
	 * @return Blackbox_IRule rule to add to collection
	 */
	protected static function getDataXPerfRule($account_name)
	{
		$config = OLPBlackbox_Config::getInstance();
		if ($config->debug->debugSkipRule(OLPBlackbox_DebugConf::DATAX_PERF))
		{
			$datax_perf_rule = new OLPBlackbox_DebugRule();
		}
		else
		{
			$datax_perf_rule = new OLPBlackbox_Enterprise_LCS_Rule_DataX(
				OLPBlackbox_Enterprise_LCS_Rule_DataX::TYPE_PERF,
				$account_name
			);
		}

		$datax_perf_rule->setEventName(OLPBlackbox_Config::EVENT_DATAX_LCS);

		return $datax_perf_rule;
	}

}

?>
