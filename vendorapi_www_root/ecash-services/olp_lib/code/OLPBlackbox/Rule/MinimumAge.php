<?php
/**
 * OLPBlackbox_Rule_MinimumAge class file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Check a dob against a minimum age.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Rule_MinimumAge extends OLPBlackbox_Rule implements OLPBlackbox_Factory_Legacy_IReusableRule
{
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
		$matches = array();
		if (!$dob || !preg_match("/([\d]{1,2})\/([\d]{1,2})\/(\d{4}|\d{2})/",$dob,$matches))
		{
			throw new Blackbox_Exception('Invalid DOB Format In Minimum Age');
		}
		
		// Generate the timestamps we are going to use to compare.
		$dob_timestamp = mktime(0,0,0,$matches[1],$matches[2],$matches[3]);
		$now_timestamp = strtotime("-" . $age . " years");
		
		return ($dob_timestamp < $now_timestamp) ? TRUE : FALSE;
	}
}

?>
