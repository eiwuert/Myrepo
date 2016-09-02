<?php

/**
 * Limit collection factory.
 *
 * This factory generates a rule collection for hourly limits, daily limits, and the such.
 *
 * @author Andrew Minderd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Factory_LimitCollection extends OLPBlackbox_Factory_Rule
{
	/**
	 * Setup and return an instance of OLPBlackbox_Rule_Limit
	 *
	 * @param Blackbox_Models_Target $target_model
	 * @return Blackbox_IRule
	 */
	public function getLimitCollection(Blackbox_Models_IReadableTarget $target_model, $daily_model, $hourly_model)
	{
		$property_short = $target_model->property_short;
		
		// If we have no_checks set, return a debug rule.
		// create another function to check this
		if (OLPBlackbox_Config::getInstance()->debug->debugSkipRule(OLPBlackbox_DebugConf::LIMITS))
		{
			$rule = new OLPBlackbox_DebugRule();
			$rule->setEventName(OLPBlackbox_Config::EVENT_LIMITS);
			$rule->setStatName(strtolower(OLPBlackbox_Config::EVENT_LIMITS));
			return $rule;
		}

		if (OLPBlackbox_Config::getInstance()->bypassLimits($property_short))
		{
			// We just want to skip this rule, but I really would rather not see a event_log entry either
			return new OLPBlackbox_DebugRule();
		}

		$collection = new OLPBlackbox_RuleCollection();
		$collection->setEventName(OLPBlackbox_Config::EVENT_LIMITS);

		$this->addLimitRules($collection, $property_short, $daily_model, $hourly_model);

		return $collection;
	}

	/**
	 * Adds the daily and hourly limit rules.
	 *
	 * @param OLPBlackbox_RuleCollection $collection The collection to add the
	 * limit rule to.
	 * @param string $property_short
	 * @param Blackbox_Models_Rule $daily_model
	 * @param Blackbox_Models_Rule $hourly_model
	 * @return void
	 */
	public function addLimitRules(OLPBlackbox_RuleCollection $collection, $property_short, $daily_model, $hourly_model)
	{
		$daily_limit = $daily_model->rule_value;
		
		if (($dow_limit = $this->getDOWLimit($daily_limit)) !== NULL)
		{
			$event = OLPBlackbox_Config::EVENT_DAILY_LIMIT;
			
			$daily_limit_rule = $this->createLimitRule($daily_model->rule_id, $property_short, $dow_limit, $event);
			
			$collection->addRule($daily_limit_rule);
		}
		
		if (NULL != $hourly_model)
		{
			$hourly_limit = $hourly_model->rule_value;
			if (($hourly_limit = $this->getHourlyLimit($hourly_limit, $dow_limit)) !== NULL)
			{
				$event = OLPBlackbox_Config::EVENT_HOURLY_LIMIT;
				
				$hourly_limit_rule = $this->createLimitRule($hourly_model->rule_id, $property_short, $hourly_limit, $event);
			
				$collection->addRule($hourly_limit_rule);
			}
		}
	}

	/**
	 * Creates the limit rule and returns it.
	 *
	 * @param int $rule_id
	 * @param OLPBlackbox_RuleCollection $collection
	 * @param Blackbox_Models_IReadableTarget $target_model
	 * @param unknown_type $limit
	 * @param string $event
	 */
	protected function createLimitRule($rule_id, $property_short, $limit, $event)
	{
		/* @var $rule OLPBlackbox_Rule_Limit */
		$rule = $this->getRuleFactory($property_short)->getOLPBlackboxRule(
			$this->getRuleClassName(),
			array(
				OLPBlackbox_Rule::PARAM_FIELD => $this->getTargetStat($property_short),
				OLPBlackbox_Rule::PARAM_VALUE => $limit,
			)
		);
		
		$rule->setEventName($event);

		$conditional_decorator = new OLPBlackbox_RuleDecorator_BehaviorModifier();//NULL, $this->getConfig()->event_log);
		$rule_conditions = $this->getRuleConditions($conditional_decorator, $rule_id);
		if (!empty($rule_conditions))
		{
			$conditional_decorator->setRule($rule);
			$rule = $conditional_decorator;
		}
		
		return $rule;
	}
	
	/**
	 * Gets the current DOW limit for a serialized limit array
	 *
	 * @param string $serialized_limits
	 * @param int $daily_limit
	 * @return int|null
	 */
	protected function getDOWLimit($serialized_limits)
	{
		// dow limits are stored as an array; dow=>limit
		if (($dow_limits = $this->unpackLimits($serialized_limits)) !== FALSE)
		{
			return $this->getCurrentDOWLimit($dow_limits);
		}
		return NULL;
	}

	/**
	 * Gets the current hourly limit for a serialized limit array
	 *
	 * @param string $serialized_limits
	 * @param int $daily_limit
	 * @return int|null
	 */
	protected function getHourlyLimit($serialized_limits, $daily_limit)
	{
		// hourly limits are stored as an array; hour=>limit
		if (($hourly_limits = $this->unpackLimits($serialized_limits)) !== FALSE)
		{
			return $this->getCurrentHourLimit($hourly_limits, $daily_limit);
		}
		return NULL;
	}

	/**
	 * Convenience function to unserialize and check array-based limits (i.e., daily and hourly)
	 * Returns FALSE if the string is empty, does not unserialize to an array, or the array
	 * contains no values.
	 *
	 * @param string $limit
	 * @return array
	 */
	protected function unpackLimits($limit)
	{
		if (!empty($limit)
			&& is_array($limits = unserialize($limit))
			&& count($limits))
		{
			return $limits;
		}
		return FALSE;
	}

	/**
	 * Determines the proper stat to check for the target's limit
	 *
	 * @param string $name
	 * @return string
	 */
	public static function getTargetStat($name)
	{
		return 'bb_' . strtolower($name);
	}
	
	/**
	 * Returns the class name for the Limit rule
	 * 
	 * @return string
	 */
	protected function getRuleClassName()
	{
		return 'Limit';
	}

	/**
	 * Decodes the DOW limits and returns the limit for the current day
	 *
	 * @param array $dow_limit
	 * @return int
	 */
	protected function getCurrentDOWLimit(array $dow_limit)
	{
		// This checks to sees if the campaign is using the new limit column.  If so it will replace
		// the old row value with a the new limit.
		$day_index = (date('N', Blackbox_Utils::getToday()) - 1);

		return $dow_limit[$day_index];
	}

	/**
	 * Decodes the hourly limits and returns the limit for the current hour
	 * If the limits are between 0 and 1 they are assumed to be percents of $daily_limit
	 *
	 * @param array $hourly_limits
	 * @param int $daily_limit
	 * @return int
	 */
	protected function getCurrentHourLimit(array $hourly_limits, $daily_limit)
	{
		// get the current hour
		$cur_hour = date('H', Blackbox_Utils::getToday());
		$hour_limit = 0;

		if (isset($hourly_limits[$cur_hour]))
		{
			// just to make sure
			ksort($hourly_limits);

			// our limit for this hour
			foreach ($hourly_limits as $hour => $limit)
			{
				// convert to a float
				$limit = (float)$limit;

				// as a percentage of our daily limit?
				if ($limit >= 0 && $limit <= 1)
				{
					$limit = round($limit * $daily_limit);

				}

				$hour_limit += $limit;
				if ($hour == $cur_hour) break;
			}
			
			return $hour_limit;
		}
		return NULL;
	}
}

?>
