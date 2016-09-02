<?php

/**
 * Limit collection factory.
 *
 * This factory generates a rule collection for hourly limits, daily limits, and the such.
 *
 * @author Andrew Minderd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Factory_Legacy_LimitCollection
{
	/**
	 * @var Stat_Limits
	 */
	protected static $stat_limits;

	/**
	 * Setup and return an instance of OLPBlackbox_Rule_Limit
	 *
	 * @param array $row
	 * @return Blackbox_IRule
	 */
	public static function getLimitCollection(array $row)
	{
		// If we have no_checks set, return a debug rule.
		if (OLPBlackbox_Config::getInstance()->debug->debugSkipRule(OLPBlackbox_DebugConf::LIMITS))
		{
			$rule = new OLPBlackbox_DebugRule();
			$rule->setEventName(OLPBlackbox_Config::EVENT_LIMITS);
			$rule->setStatName(strtolower(OLPBlackbox_Config::EVENT_LIMITS));
			return $rule;
		}

		if (OLPBlackbox_Config::getInstance()->bypassLimits($row['property_short']))
		{
			// We just want to skip this rule, but I really would rather not see a event_log entry either
			return new OLPBlackbox_DebugRule();
		}

		$collection = new OLPBlackbox_RuleCollection();
		$collection->setEventName(OLPBlackbox_Config::EVENT_LIMITS);

		$collection->addRule(self::getDailyLimitRule($row));

		return $collection;
	}

	/**
	 * Gets the daily limit rule
	 * The daily limit will either be the limit, or the current hourly limit; only
	 * one takes effect at any given time.
	 *
	 * @param array $row
	 * @return OLPBlackbox_Rule_Limit
	 */
	protected static function getDailyLimitRule(array $row)
	{
		$limit = $row['limit'];
		$event = OLPBlackbox_Config::EVENT_DAILY_LIMIT;

		// dow limits are stored as an array; dow=>limit
		if (($dow_limit = self::getDOWLimit($row['daily_limit'], $limit)) !== NULL)
		{
			$limit = $dow_limit;
		}

		// hourly limits are stored as an array; hour=>limit
		if (($hourly_limit = self::getHourlyLimit($row['hourly_limit'], $limit)) !== NULL)
		{
			$limit = $hourly_limit;
			$event = OLPBlackbox_Config::EVENT_HOURLY_LIMIT;
		}

		// limit multiplier allows a 'global' limit to be shared across companies
		// with a different percentage allocated to each
		if ($row['limit_mult'])
		{
			$limit += round($limit * $row['limit_mult']);
		}

		/* @var $rule OLPBlackbox_Rule_Limit */
		$rule = OLPBlackbox_Factory_Rules::getRule(
			'Limit',
			array(
				OLPBlackbox_Rule::PARAM_FIELD => self::getTargetStat($row['property_short']),
				OLPBlackbox_Rule::PARAM_VALUE => $limit,
			)
		);
		$rule->setEventName($event);
		$rule->setStatLimits(self::getStatLimits());

		return $rule;
	}

	/**
	 * Gets the current DOW limit for a serialized limit array
	 *
	 * @param string $serialized_limits
	 * @param int $daily_limit
	 * @return int|null
	 */
	protected static function getDOWLimit($serialized_limits, $daily_limit)
	{
		// dow limits are stored as an array; dow=>limit
		if (($dow_limits = self::unpackLimits($serialized_limits)) !== FALSE)
		{
			return self::getCurrentDOWLimit($dow_limits, $daily_limit);
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
	protected static function getHourlyLimit($serialized_limits, $daily_limit)
	{
		// hourly limits are stored as an array; hour=>limit
		if (($hourly_limits = self::unpackLimits($serialized_limits)) !== FALSE)
		{
			return self::getCurrentHourLimit($hourly_limits, $daily_limit);
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
	protected static function unpackLimits($limit)
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
	protected static function getTargetStat($name)
	{
		$stat = 'look';

		// <hack>
		$fields = array(
			'pcl' => 'bb_pcl_look',
			'ucl' => 'bb_ucl_look',
			'ca'  => 'bb_ca_look',
			'ufc' => 'bb_ufc_look',
			'd1'  => 'bb_d1_look',
			'cap' => 'bb_cap_agree',
		);

		$name = strtolower($name);

		if (isset($fields[$name]))
		{
			$stat = $fields[$name];
		}
		else
		{
			$stat = "bb_{$name}";
		}
		return $stat;
	}

	/**
	 * Gets a Stat_Limits instance
	 *
	 * @return Stat_Limits
	 */
	public static function getStatLimits()
	{
		if (!self::$stat_limits)
		{
			$config = OLPBlackbox_Config::getInstance();
			$sql = $config->olp_db;
			$db_name = $config->olp_db->db_info['db'];

			self::$stat_limits = new Stat_Limits($sql, $db_name);
		}
		return self::$stat_limits;
	}

	/**
	 * Decodes the DOW limits and returns the limit for the current day
	 *
	 * @param array $dow_limit
	 * @return int
	 */
	protected static function getCurrentDOWLimit(array $dow_limit)
	{
		// This checks to sees if the campaign is using the new limit column.  If so it will replace
		// the old row value with a the new limit.
		$day_index = (date('N', Blackbox_Utils::getToday()) - 1);

		$limit = ($dow_limit[7] == '1')
			? $dow_limit[$day_index]
			: $dow_limit[8];
		return $limit;
	}

	/**
	 * Decodes the hourly limits and returns the limit for the current hour
	 * If the limits are between 0 and 1 they are assumed to be percents of $daily_limit
	 *
	 * @param array $hourly_limits
	 * @param int $daily_limit
	 * @return int
	 */
	protected static function getCurrentHourLimit(array $hourly_limits, $daily_limit)
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
				if ($limit >= 0 && $limit < 1)
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