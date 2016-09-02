<?php
/**
 * Blackbox_Rule_Equals class file.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */

/**
 * Checks if one value is equal to another value.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */
class OLPBlackbox_Rule_Equals extends OLPBlackbox_Rule implements OLPBlackbox_Factory_Legacy_IReusableRule
{
	/**
	 * Runs the Equals rule.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 * 
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return ($this->getDataValue($data) == $this->getRuleValue());
	}
}

?>
