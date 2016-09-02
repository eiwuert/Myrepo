<?php
/**
 * Checks if a value is not in a specified array of values.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_NotIn extends VendorAPI_Blackbox_Rule_In
{
	/**
	 * Runs the Not In rule.
	 *
	 * @param BlackBox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 * 
	 * @return bool TRUE if $data is not in the the rule value array
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return !parent::runRule($data, $state_data);
	}
}

?>
