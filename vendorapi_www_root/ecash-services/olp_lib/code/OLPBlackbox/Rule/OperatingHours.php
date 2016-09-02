<?php
/**
 * OLPBlackbox_Rule_OperatingHours class file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Checks to see if the target has specific operating hours set, and
 * makes sure the current date/time meet those requirements.
 *
 * Note: In the "legacy code", if the start time was 8:00am, at
 * 8:00:00am, it would fail, and at 8:00:01am it would pass.  In the grand
 * scheme of things, probably not a big deal, even though logically it seems
 * wrong.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Rule_OperatingHours extends OLPBlackbox_Rule implements OLPBlackbox_Factory_Legacy_IReusableRule
{
	/**
	 * The operating hours object.
	 *
	 * @var OLPBlackbox_OperatingHours
	 */
	protected $operating_hours;
	
	/**
	 * We dont need to do the normal data value check since we are only
	 * working with the current time and the operating_hours passed in,
	 * so lets always return true.
	 *
	 * @param Blackbox_Data $data the data the rule is running against
	 * @param Blackbox_IStateData $state_data information about the state of the Blackbox_ITarget which desires to run the rule.
	 *
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}
	
	/**
	 * Runs the OperatingHours rule.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 *
	 * @return bool
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$date_time_string = date('Y-m-d H:i:s', Blackbox_Utils::getInstance()->getToday());
		return $this->getOperatingHoursObject()->isOpen($date_time_string);
	}
	
	/**
	 * Moved this from Rules.php and the constructor so that it conforms to
	 * automatic rule building. [MJ]
	 *
	 * @param array $params
	 * @return void
	 */
	public function setupRule($params)
	{
		/* Operating hours currently works out of an array where keys:
		 * 0 => mon-sun start
		 * 1 => mon-sun end
		 * 2 => sat start
		 * 3 => sat end
		 * 4 => sun start
		 * 5 => sun end
		 * 6 => special date if applicable
		 * 7 => date specific start
		 * 8 => date specific end
		 *
		 * If sat and sun arent specified, use values for mon-fri.
		 *
		 * The times are in 12 hour format, and use HH:MM:AM, so we need
		 * to strip out the : between the AM or PM, and then calculate
		 * the 24 hour formatted time.
		 */
		try
		{
			$operating_hours = $this->getOperatingHoursObject();
			$rule_value = $params[OLPBlackbox_Rule::PARAM_VALUE];
			
			// Loop through and clean up the data.
			$replace = array('/:AM/', '/:PM/');
			$with = array('AM', 'PM');
			foreach (array_keys($rule_value) as $key)
			{
				if ($key != 6)
				{
					$rule_value[$key] = preg_replace($replace, $with, $rule_value[$key]);
					$rule_value[$key] = date('H:i', strtotime($rule_value[$key]));
				}
			}
	
			// Set mon through friday
			if ($rule_value[0] && $rule_value[1])
			{
				$operating_hours->addDayOfWeekHours('Mon', 'Fri', $rule_value[0], $rule_value[1]);
			}
			//Set saturday
			if (isset($rule_value[2]) && isset($rule_value[3]))
			{
				$operating_hours->addDayOfWeekHours('Sat', 'Sat', $rule_value[2], $rule_value[3]);
			}
			else
			{
				$operating_hours->addDayOfWeekHours('Sat', 'Sat', $rule_value[0], $rule_value[1]);
			}
			//Set sunday
			if (isset($rule_value[4]) && isset($rule_value[5]))
			{
				$operating_hours->addDayOfWeekHours('Sun', 'Sun', $rule_value[4], $rule_value[5]);
			}
			else
			{
				$operating_hours->addDayOfWeekHours('Sun', 'Sun', $rule_value[0], $rule_value[1]);
			}
			//Set special
			if (isset($rule_value[7]) && isset($rule_value[8]))
			{
				// Normalize out the date...  We are expecting it to come in as
				// m-j-Y.  Ex, 12-5-2008 = Dec 5th, 2008.			
				$special_date_parts = explode('-', $rule_value[6]);
				$special_date = date('Y-m-d', mktime(0,0,0,$special_date_parts[0],$special_date_parts[1],$special_date_parts[2]));
				$operating_hours->addDateHours($special_date, $special_date, $rule_value[7], $rule_value[8]);
			}
			
			parent::setupRule($params);
		}
		// On an invalid argument exception, throw a Blackbox_Exception to allow it to be caught by the rule factory
		catch (InvalidArgumentException $e)
		{
			throw new Blackbox_Exception("OperatingHours Rule SetUp Exception: " . $e->getMessage(), $e->getCode());
		}
	}

	/**
	 * Get the operating hours object
	 *
	 * @return OLPBlackbox_OperatingHours
	 */
	protected function getOperatingHoursObject()
	{
		if (empty($this->operating_hours))
		{
			$this->operating_hours = new OLPBlackbox_OperatingHours();
		}
		return $this->operating_hours;
	}
}
?>