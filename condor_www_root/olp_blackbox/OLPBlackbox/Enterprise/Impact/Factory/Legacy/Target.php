<?php
/**
 * Defines the OLPBlackbox_Factory_Legacy_TargetImpact class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */

/**
 * Class to assemble targets for Impact.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Impact_Factory_Legacy_Target
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

		// set up the call type for the DataX rule
		if ($target_name == 'ic')
		{
			$call_type = OLPBlackbox_Enterprise_Impact_Rule_DataX::TYPE_IDVE_IMPACT;
			$event_name = OLPBlackbox_Config::EVENT_DATAX_IC_IDVE;
		}
		elseif ($target_name == 'ifs')
		{
			$call_type = OLPBlackbox_Enterprise_Impact_Rule_DataX::TYPE_IDVE_IMPACT;
			$event_name = OLPBlackbox_Config::EVENT_DATAX_IC_IDVE;
		}
		elseif ($target_name == 'icf')
		{
			$call_type = OLPBlackbox_Enterprise_Impact_Rule_DataX::TYPE_IDVE_ICF;
			$event_name = OLPBlackbox_Config::EVENT_DATAX_IDVE_ICF;
		}
		elseif ($target_name == 'ipdl')
		{
			$call_type = OLPBlackbox_Enterprise_Impact_Rule_DataX::TYPE_IDVE_IPDL;
			$event_name = OLPBlackbox_Config::EVENT_DATAX_IDVE_IPDL;
		}
		else
		{
			throw new Blackbox_Exception(
				'could not determine call type for Impact target '.$target_name
			);
		}

		// target we will return
		$target = new OLPBlackbox_Enterprise_CFETarget($target_row['property_short'], $target_row['target_id']);

		// create base (generic) rules (uses config, no need to pass debug info)
		$rule_collection = new Blackbox_RuleCollection();
		$rule_factory = OLPBlackbox_Factory_Legacy_RuleCollection::getInstance($target_row['property_short']);
		$rule_collection->addRule($rule_factory->getRuleCollection($target_row));

		if (OLPBlackbox_Config::getInstance()->blackbox_mode == OLPBlackbox_Config::MODE_BROKER)
		{
			// We only want to check limits in broker mode
			$rule_collection->addRule(
				OLPBlackbox_Factory_Legacy_LimitCollection::getLimitCollection($target_row)
			);

			// add UsedABACheck
			$rule_collection->addRule(
				new OLPBlackbox_Enterprise_Generic_Rule_UsedABACheck(
					array($target_name)
				)
			);
		}

		// create rules to be run during the pickTarget() phase of bb
		$pick_target_rules = new Blackbox_RuleCollection();
		$pick_target_rules->addRule(self::getPreviousCustomerRule($target_row));

		// Add qualify rule
		$pick_target_rules->addRule(
			new OLPBlackbox_Enterprise_Generic_Rule_LegacyQualifiesForAmount()
		);

		if (OLPBlackbox_Config::getInstance()->blackbox_mode == OLPBlackbox_Config::MODE_BROKER)
		{
			// add frequency score rule
			$pick_target_rules->addRule(
				OLPBlackbox_Factory_Legacy_Target::getFrequencyScoreRule($target_row)
			);

			$pick_target_rules->addRule(
				self::getDataXRule($event_name, $call_type, $target_name)
			);

			$pick_target_rules->addRule(
				new OLPBlackbox_Enterprise_Impact_Rule_WinnerVerifiedStatus()
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
	 * Generate a DataX Rule for the target being assembled.
	 *
	 * @param string $event_name Name of the event to log to event log.
	 * @param string $call_type The type of DataX call to make.
	 * @param string $target_name Same as legacy "property_short"
	 *
	 * @return IRule object
	 */
	protected static function getDataXRule($event_name, $call_type, $target_name)
	{
		$debug = OLPBlackbox_Config::getInstance()->debug;

		// impact datax calls combine IDV and perf calls,
		// so we skip if either are to be excluded from this run of blackbox
		if ($debug->debugSkipRule(OLPBlackbox_DebugConf::DATAX_IDV)
			|| $debug->debugSkipRule(OLPBlackbox_DebugConf::DATAX_PERF))
		{
			$datax_rule = new OLPBlackbox_DebugRule($event_name);
		}
		else
		{
			$datax_rule = new OLPBlackbox_Enterprise_Impact_Rule_DataX(
				$call_type,
				$target_name
			);
		}

		$datax_rule->setEventName($event_name);

		return $datax_rule;
	}
}

?>
