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
	 * @var OperatingHours
	 */
	protected $operating_hours;
	
	/**
	 * OLPBlackbox_Rule_OperatingHours constructor.
	 *
	 * @param Operating_Hours $operating_hours the operating hours object
	 */
	public function __construct(OperatingHours $operating_hours) // Operating Hours Class?
	{
		$this->operating_hours = $operating_hours;
		parent::__construct();
	}
	
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
		$utils = Blackbox_Utils::getInstance();
		
		// We are going to assume that we are going to return a TRUE response
		// unless we find a reason otherwise.
		$valid = TRUE;
		
		$today = $utils->getToday();
		$day_of_week = strtolower(date('D', $today));
		$date = date('Y-m-d', $today);
		
		// First we need to make sure today isnt in the "special case" date
		// array that will be used to override normal operating hours.  If
		// we cant find it there, then we need to look in the normal day of
		// week array to see if there are operating hours there.
		if (!$todays_hours = $this->operating_hours->getDateHours($date))
		{
			$todays_hours = $this->operating_hours->getDayOfWeekHours($day_of_week);
		}
		
		if (is_array($todays_hours))
		{
			// Now that we know we have a specific set(s) of operating hours
			// that the time must fall into, we need to assume we are going to
			// fail.  If we find a set of operating hours the time falls into
			// we will know its valid and can set it as such, and break out.
			$valid = FALSE;
			
			foreach ($todays_hours as $hour_group)
			{
				// See if "today" falls between the start and end time.
				$start = strtotime($hour_group['start'], $today);
				$end = strtotime($hour_group['end'], $today);
				if (($today > $start) && ($today < $end))
				{
					$valid = TRUE;
					break;
				}
			}
		}
		
		return $valid;
	}
	
}

?>
