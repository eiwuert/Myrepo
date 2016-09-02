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
		elseif (EnterpriseData::COMPANY_QEASY == EnterpriseData::getCompany($target_row['property_short']))
		{
			// Set up LCS company specific targets.
			$target = OLPBlackbox_Enterprise_QEasy_Factory_Legacy_Target::getTarget($target_row);
		}
		elseif (CompanyData::isCompanyProperty(CompanyData::COMPANY_CASHNET, $target_row['property_short'])
			|| CompanyData::isCompanyProperty(CompanyData::COMPANY_ZIPCASH, $target_row['property_short']))
		{
			// CashNet and ZipCash have their own rules to run, so we need to allow them to set up their own rules.
			$target = self::getGenericTarget($target_row, FALSE);
		}
		elseif (EnterpriseData::COMPANY_HMS == EnterpriseData::getCompany($target_row['property_short']))
		{
			// HMS targets
			$target = OLPBlackbox_Enterprise_HMS_Factory_Legacy_Target::getTarget($target_row);
		}
		elseif (EnterpriseData::COMPANY_OPM == EnterpriseData::getCompany($target_row['property_short']))
		{
			// OPM targets
			$target = OLPBlackbox_Enterprise_OPM_Factory_Legacy_Target::getTarget($target_row);
		}
		elseif (EnterpriseData::COMPANY_DMP == EnterpriseData::getCompany($target_row['property_short']))
		{
			// DMP targets
			$target = OLPBlackbox_Enterprise_DMP_Factory_Legacy_Target::getTarget($target_row);
		}
		elseif (EnterpriseData::COMPANY_MMP == EnterpriseData::getCompany($target_row['property_short']))
		{
			// MMP targets
			$target = OLPBlackbox_Enterprise_MMP_Factory_Legacy_Target::getTarget($target_row);
		}
		elseif (preg_match('/\_uk\d*$/i', $target_row['property_short']))
		{
			// Setup UK targets.
			$target = OLPBlackbox_Factory_Legacy_TargetUK::getTarget($target_row);
		}
		else
		{
			// Create the generic target.
			$target = self::getGenericTarget($target_row);
		}
		
		return $target;
	}

	/**
	 * Gets a generic target.
	 *
	 * @param array $target_row array of rules from the OLP database
	 * @param bool $default_rules TRUE if we're going to use the default rules.
	 * @return OLPBlackbox_Target
	 */
	protected static function getGenericTarget(array $target_row, $default_rules = TRUE)
	{
		// Create the generic target.
		$target = new OLPBlackbox_Target($target_row['property_short'], $target_row['target_id']);

		// Set the rule collection for this target.
		$rules = new Blackbox_RuleCollection();
		
		if ($default_rules)
		{
			// This is supposed to be called without a company name because it's supposed to hit the default case.
			$rule_factory = OLPBlackbox_Factory_Legacy_RuleCollection::getInstance();
		}
		else
		{
			$rule_factory = OLPBlackbox_Factory_Legacy_RuleCollection::getInstance($target_row['property_short']);
		}
		
		$rules->addRule($rule_factory->getRuleCollection($target_row));
		$rules->addRule(OLPBlackbox_Factory_Legacy_LimitCollection::getLimitCollection(
			$target_row)
		);

		$target->setRules($rules);

		// Setup the picktarget rule collection
		$target->setPickTargetRules(OLPBlackbox_Factory_Legacy_Target::getPickTargetCollection($target_row));

		return $target;
	}

	/**
	 * This will set up the PickTarget Rule collection with Frequency Score and Withheld targets in it
	 *
	 * @param array $target_row An array with the tier information
	 * @return Blackbox_RuleCollection $rules
	 */
	public static function getPickTargetCollection($target_row)
	{
		// Set the rule collection for this target.
		$rules = new Blackbox_RuleCollection();
		
		// Get the Frequency Score rule and Withheld Targets rule and add them to the rule collection
		$rules->addRule(OLPBlackbox_Factory_Legacy_Target::getFrequencyScoreRule($target_row));
		$rules->addRule(OLPBlackbox_Factory_Legacy_Target::getWithheldTargetsRule($target_row));
		
		return $rules;
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
	
	/**
	 * Setup and return an instance of the OLPBlackbox_Rule_WithheldTargets rule.
	 *
	 * @param array $target_row An array with the tier information
	 * @return OLPBlackbox_Rule_WithheldTargets
	 */
	public static function getWithheldTargetsRule($target_row)
	{
		if (OLPBlackbox_Config::getInstance()->blackbox_mode == OLPBlackbox_Config::MODE_BROKER)
		{
			$rule = OLPBlackbox_Factory_Rules::getRule('WithheldTargets');
			$rule->setEventName(OLPBlackbox_Config::EVENT_WITHHELD_TARGETS);
		}
		else 
		{
			$rule = new OLPBlackbox_DebugRule();
			$rule->setEventName(OLPBlackbox_Config::EVENT_WITHHELD_TARGETS);
		}
		
		return $rule;
	}
}
?>
