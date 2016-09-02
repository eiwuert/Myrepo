<?php
/**
 * OLPBlackbox_Rule_DateNotIn class file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Checks to make sure the current date is not in an array of dates that
 * should be rejected.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Rule_DateNotIn extends OLPBlackbox_Rule
{
	/**
	 * We dont need to do the normal data value check, always return true.
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
	 * Runs the DateNotIn rule.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 * 
	 * @return bool
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$utils = Blackbox_Utils::getInstance();
		
		if (!is_array($this->getRuleValue()))
		{
			throw new Blackbox_Exception('Data type mismatch: rule data should be an array.');
		}
		
		// Check to see if the formatted date for today is in a list of dates.
		$today = date('Y-m-d', $utils->getToday());
		return !in_array($today, $this->getRuleValue());
	}
}

?>
