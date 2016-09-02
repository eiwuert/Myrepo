<?php

/**
 * Legacy factory for AALM targets.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Enterprise_AALM_Factory_Legacy_Target
{
	/**
	 * Return ITarget object, instantiated with AALM specific things.
	 *
	 * @param array $target_row An array with the target information
	 *
	 * @return ITarget object
	 */
	public static function getTarget($target_row)
	{
		// the target we will return
		$target = new OLPBlackbox_Enterprise_CFETarget($target_row['property_short'], $target_row['target_id']);
		
		// Begin with the basic rules.
		$rule_collection = new OLPBlackbox_RuleCollection();
		$rule_factory = OLPBlackbox_Factory_Legacy_RuleCollection::getInstance($target_row['property_short']);
		$rule_collection->addRule($rule_factory->getRuleCollection($target_row));
		$rule_collection->addRule(OLPBlackbox_Factory_Legacy_LimitCollection::getLimitCollection($target_row));

		// UsedABACheck does appropriate checks to DebugConf
		// so there's no need for it here.
		$rule_collection->addRule(
			new OLPBlackbox_Enterprise_Generic_Rule_UsedABACheck(
				array($target_row['property_short'])
			)
		);

		// rules to run during pickTarget
		$pick_target_rules = new Blackbox_RuleCollection();

		$pick_target_rules->addRule(self::getPreviousCustomerRule($target_row));

		// rules run during pickTarget(), which are postprocessing
		// items which may still fail a target.
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
				self::getDataXPerfRule($target_row['property_short'])
			);

			$pick_target_rules->addRule(
				new OLPBlackbox_Enterprise_Generic_Rule_WinnerVerifiedStatus()
			);
		}

		$target->setRules($rule_collection);
		$target->setPickTargetRules($pick_target_rules);

		return $target;
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
	 * Generate a DataX Perf rule for AALM.
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
			$datax_perf_rule = new OLPBlackbox_Enterprise_AALM_Rule_DataX(
				OLPBlackbox_Enterprise_AALM_Rule_DataX::TYPE_PERF_MLS,
				$account_name
			);
		}

		$datax_perf_rule->setEventName(OLPBlackbox_Config::EVENT_DATAX_AALM);

		return $datax_perf_rule;
	}
}

?>
