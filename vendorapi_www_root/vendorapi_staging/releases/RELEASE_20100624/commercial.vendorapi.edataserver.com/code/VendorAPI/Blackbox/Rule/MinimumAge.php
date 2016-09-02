<?php
/**
 * VendorAPI_Blackbox_Rule_MinimumAge class file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Check a dob against a minimum age.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_MinimumAge extends VendorAPI_Blackbox_Rule
{
	/**
	 * Returns whether the rule has sufficient data to run
	 * If the rule can't be run, onSkip() will be called
	 *
	 * @param Blackbox_Data $data the data the rule is running against
	 * @param Blackbox_IStateData $state_data information about the state of the Blackbox_ITarget which desires to run the rule.
	 *
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return parent::canRun($data, $state_data) && $this->getDataValue($data) !== NULL;
	}
	
	/**
	 * Runs the MinimumAge rule.  Expected DOB format: m/d/y (ex 01/01/2006)
	 *
	 * Took logic from "legacy olp" blackbox Minimum_Age rule - which by the way
	 * was incorrect and if today is your 18th birthday, it doesnt consider
	 * you 18, so you would have to wait to apply until the day after your
	 * birthday.  It was decided to let it continue working that way.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 * 
	 * @return bool
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// If no age was set (it was 0) then the rule should be turned off, so
		// we can just skip this rule and return true.
		if (!$age = $this->getRuleValue())
		{
			return TRUE;
		}
		
		// Make sure we are trying to work with a valid age.
		if (!is_numeric($age))
		{
			throw new Blackbox_Exception('Invalid Age In Minimum Age');
		}
		
		// Take the dob data and break it out into the chunks of data we need.
		$dob = $this->getDataValue($data);
		if (!$dob) return FALSE;

		$matches = array();
		$year = $month = $day = NULL;

		// we parse the date and use mktime instead of strtotime() because they handle
		// pre 1970 dates differently. mktime() gives a negative timestamp which works fine.
		if (preg_match("/([\d]{4})-([\d]{1,2})-([\d]{1,2})/", $dob, $matches))
		{
			$year = $matches[1];
			$month = $matches[2];
			$day = $matches[3];
		}
		elseif (preg_match("/([\d]{1,2})\/([\d]{1,2})\/(\d{4}|\d{2})/", $dob, $matches))
		{
			$month = $matches[1];
			$day = $matches[2];
			$year = $matches[3];
		}
		else
		{
			throw new Blackbox_Exception('Invalid DOB Format In Minimum Age');
		}

		$dob_timestamp = mktime(0, 0, 0, $month, $day, $year);
		$now_timestamp = strtotime("-$age years");

		return ($dob_timestamp < $now_timestamp) ? TRUE : FALSE;
	}

	/**
	 * Runs when the rule returns invalid.
	 *
	 * @param Blackbox_Data $data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		parent::onInvalid($data, $state_data);

		/**
		 * If you don't meet the age limit for one company, you won't meet them for all.
		 */
		if(isset($state_data->fail_type) && is_a($state_data->fail_type, 'VendorAPI_Blackbox_FailType'))
		{
			$state_data->fail_type->setFail(VendorAPI_Blackbox_FailType::FAIL_ENTERPRISE);
		}
	}

}

?>
