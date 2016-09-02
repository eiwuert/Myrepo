<?php
/**
 * Blackbox_Rule_GreaterThanEquals class file.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */

/**
 * Checks if one value is greater than or equal to another value.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */
class Blackbox_Rule_GreaterThanEquals extends Blackbox_Rule_LessThan
{
	/**
	 * Runs the Greater Than Equals rule.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 * 
	 * @return bool TRUE if $data is greater than or equal to $this->rule_value
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return !parent::runRule($data, $state_data);
	}
}

?>
