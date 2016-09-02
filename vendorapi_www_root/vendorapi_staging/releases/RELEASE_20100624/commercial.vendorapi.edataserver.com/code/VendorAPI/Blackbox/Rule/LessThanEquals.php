<?php
/**
 * VendorAPI_Blackbox_Rule_LessThanEquals class file.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */

/**
 * Checks if one value is less than or equal another value.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_LessThanEquals extends VendorAPI_Blackbox_Rule_GreaterThan
{
	/**
	 * Runs the Less Than Equals rule.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 * 
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return !parent::runRule($data, $state_data);
	}
}

?>
