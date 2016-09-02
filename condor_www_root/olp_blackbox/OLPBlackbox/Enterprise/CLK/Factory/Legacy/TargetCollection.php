<?php
/**
 * Defines the OLPBlackbox_Enterprise_CLK_Factory_Legacy_TargetCollection class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Factory for legacy olp target collections specific to clk.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Enterprise_CLK_Factory_Legacy_TargetCollection
{
	/**
	 * Gets an instance of a target collection with the CLK specific stuff...
	 *
	 * @param array $target_row An array with the target information
	 *
	 * @return ITarget
	 */
	public static function getTargetCollection($target_row)
	{
		// Create the target collection.
		$target_collection = new OLPBlackbox_Enterprise_TargetCollection(
			$target_row['property_short']
		);

		// Set the picker we want to use.
		$target_collection->setPicker(
			OLPBlackbox_Factory_Picker::getPicker('PERCENT')
		);
		$target_collection->setPostTargetRules(self::getPostTargetRules($target_row));

		return $target_collection;
	}

	/**
	 * Gets the rule collection for rules run after pickTarget
	 *
	 * @return Blackbox_RuleColelction
	 */
	protected static function getPostTargetRules(array $row)
	{
		$post_target_rules = new Blackbox_RuleCollection();

		$factory = OLPBlackbox_Enterprise_Generic_Factory_Legacy_PreviousCustomer::getInstance($row['property_short'], OLPBlackbox_Config::getInstance());
		$post_target_rules->addRule($factory->getPreviousCustomerRule());

		if (OLPBlackbox_Config::getInstance()->blackbox_mode !== OLPBlackbox_Config::MODE_ONLINE_CONFIRMATION)
		{
			$post_target_rules->addRule(self::getUsedAbaRule());
			$post_target_rules->addRule(self::getDataxIDVRule());
		}

		return $post_target_rules;
	}

	/**
	 * Gets the previous customer rule
	 *
	 * @return Blackbox_IRule
	 */
	protected static function getPreviousCustomerRule(array $row)
	{
		$f = new OLPBlackbox_Enterprise_Generic_Factory_Legacy_PreviousCustomer();
		return $f->getRule($row);
	}

	/**
	 * Gets the Used Info check (AKA Used ABA Rule)
	 *
	 * @return Blackbox_IRule
	 */
	protected static function getUsedAbaRule()
	{
		$config = OLPBlackbox_Config::getInstance();

		// honor debugging rule skips
		if ($config->debug->debugSkipRule(OLPBlackbox_DebugConf::USED_INFO))
		{
			return new OLPBlackbox_DebugRule(
				OLPBlackbox_Config::EVENT_USED_ABA_CHECK
			);
		}

		$sub_companies = EnterpriseData::getCompanyProperties(
			EnterpriseData::COMPANY_CLK
		);

		// set up ABA rule. doesn't need event name set, event logging
		// is taken care of by this complex rule itself.
		$rule = new OLPBlackbox_Enterprise_Generic_Rule_UsedABACheck(
			$sub_companies,
			EnterpriseData::COMPANY_CLK
		);

		if ($config->blackbox_mode == OLPBlackbox_Config::MODE_PREQUAL)
		{
			$rule->setSkippable(TRUE);
		}
		return $rule;
	}

	/**
	 * Gets the DataX IDV rule
	 *
	 * @return Blackbox_IRule
	 */
	protected static function getDataxIDVRule()
	{
		$config = OLPBlackbox_Config::getInstance();

		// skip if we're skipping
		if ($config->debug->debugSkipRule(OLPBlackbox_DebugConf::DATAX_IDV))
		{
			return new OLPBlackbox_DebugRule(
				OLPBlackbox_Config::EVENT_DATAX_IDV
			);
		}

		// determine the rule type based on mode
		if ($config->blackbox_mode == OLPBlackbox_Config::MODE_PREQUAL)
		{
			$call_type = OLPBlackbox_Enterprise_CLK_Rule_DataX::TYPE_IDV_PREQUAL;
		}
		elseif ($config->do_datax_rework)
		{
			$call_type = OLPBlackbox_Enterprise_CLK_Rule_DataX::TYPE_IDV_REWORK;
		}
		else
		{
			$call_type = OLPBlackbox_Enterprise_CLK_Rule_DataX::TYPE_IDV;
		}

		$idv = new OLPBlackbox_Enterprise_CLK_Rule_DataX($call_type, 'BB');
		$idv->setEventName(OLPBlackbox_Config::EVENT_DATAX_IDV);
		return $idv;
	}
}
?>
