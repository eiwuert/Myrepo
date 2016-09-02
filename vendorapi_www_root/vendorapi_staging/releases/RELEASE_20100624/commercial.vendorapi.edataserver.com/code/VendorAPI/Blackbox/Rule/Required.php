<?php
/**
 * VendorAPI_Blackbox_Rule_Required class file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Checks to see if a required value exists.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_Required extends VendorAPI_Blackbox_Rule
{
	/**
	 * Returns whether the rule has enough information to run.
	 *
	 * The Required rule always has the correct information since that's what it's actually determining, whether
	 * we have the information.
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}
	
	/**
	 * Runs the Required rule.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 *
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return (bool) $this->getDataValue($data);
	}
}

?>
