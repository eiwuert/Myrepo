<?php
/**
 * Factory for legacy OLP targets.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Factory_Legacy_Target
{
	/**
	 * Gets an instance of the target we need.
	 *
	 * @param array $target_row An array with the tier information
	 * @return ITarget
	 */
	public static function getTarget($target_row)
	{
		if (EnterpriseData::COMPANY_CLK == EnterpriseData::getCompany($target_row['property_short']))
		{
			// We know clk has specific rules and stuff we need to add to the
			// clk target, so call the CLK factory to set that all up.
			$target = OLPBlackbox_Enterprise_CLK_Factory_Legacy_Target::getTarget($target_row);
		}
		elseif (EnterpriseData::COMPANY_AGEAN == EnterpriseData::getCompany($target_row['property_short']))
		{
			// We know agean has specific rules and stuff we need to add to the
			// agean target, so call the Agean factory to set that all up.
			$target = OLPBlackbox_Enterprise_Agean_Factory_Legacy_Target::getTarget($target_row);
		}
		elseif (EnterpriseData::COMPANY_IMPACT == EnterpriseData::getCompany($target_row['property_short']))
		{
			// Set up impact company specific targets.
			$target = OLPBlackbox_Enterprise_Impact_Factory_Legacy_Target::getTarget($target_row);
		}
		elseif (EnterpriseData::COMPANY_GENERIC == EnterpriseData::getCompany($target_row['property_short']))
		{
			// AALM has some individual rules that are different.
			$target = OLPBlackbox_Enterprise_AALM_Factory_Legacy_Target::getTarget($target_row);
		}
		elseif (EnterpriseData::COMPANY_LCS == EnterpriseData::getCompany($target_row['property_short']))
		{
			// Set up LCS company specific targets.
			$target = OLPBlackbox_Enterprise_LCS_Factory_Legacy_Target::getTarget($target_row);
		}
		elseif (preg_match('/\_uk\d*$/i',$target_row['property_short']))
		{
			// Setup UK targets.
			$target = OLPBlackbox_Factory_Legacy_TargetUK::getTarget($target_row);
		}
		else
		{
			// Create the generic target.
			$target = self::getGenericTarget($target_row);
		}
		if (EnterpriseData::isCFE(EnterpriseData::resolveAlias($target_row['property_short'])) &&
			!$target instanceof OLPBlackbox_Enterprise_ICFETarget
		)
		{
			throw new Exception("Property {$target_row['property_short']} is CFE and target is not CFE.");
		}
		return $target;
	}

	/**
	 * Gets a generic target.
	 *
	 * @param array $target_row array of rules from the OLP database
	 * @return OLPBlackbox_Target
	 */
	protected static function getGenericTarget(array $target_row)
	{
		// Create the generic target.
		$target = new OLPBlackbox_Target($target_row['property_short'], $target_row['target_id']);

		// Set the rule collection for this target.
		$rules = new Blackbox_RuleCollection();
		// This is supposed to be called without a company name because it's supposed to hit the default case.
		$rule_factory = OLPBlackbox_Factory_Legacy_RuleCollection::getInstance();
		$rules->addRule($rule_factory->getRuleCollection($target_row));
		$rules->addRule(OLPBlackbox_Factory_Legacy_LimitCollection::getLimitCollection($target_row));

		$target->setRules($rules);

		// Setup the frequency score rule
		$target->setPickTargetRules(OLPBlackbox_Factory_Legacy_Target::getFrequencyScoreRule($target_row));

		return $target;
	}

	/**
	 * Setup and return an instance of the OLPBlackbox_Rule_FrequencyScore rule.
	 *
	 * @param array $target_row An array with the tier information
	 * @return OLPBlackbox_Rule_FrequencyScore
	 * @todo Move this to a more appropriate place, it doesn't really belong in the target factory
	 */
	public static function getFrequencyScoreRule($target_row)
	{
		// If we have no_checks set, return a debug rule.
		if (OLPBlackbox_Config::getInstance()->blackbox_mode !== OLPBlackbox_Config::MODE_BROKER)
		{
			$rule = new OLPBlackbox_DebugRule();
			$rule->setEventName(OLPBlackbox_Config::EVENT_FREQUENCY_SCORE);
			return $rule;
		}
		
		// Setup the frequency scoring rule
		if (strlen($target_row['frequency_decline']) > 3)
		{
			$frequency_limits = unserialize($target_row['frequency_decline']);
		}

		// If we didn't get a valid value or the serialization failed, give us an empty array
		if (!isset($frequency_limits) || $frequency_limits === FALSE)
		{
			$frequency_limits = array();
		}

		$freq_rule = OLPBlackbox_Factory_Rules::getRule(
			'FrequencyScore',
			array(
				OLPBlackbox_Rule::PARAM_FIELD => 'email_primary',
				OLPBlackbox_Rule::PARAM_VALUE => $frequency_limits
			)
		);

		return $freq_rule;
	}
}
?>
