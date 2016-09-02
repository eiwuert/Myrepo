<?php
/**
 * Blackbox_Rule_In class file.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */

/**
 * Checks if a value is in a specified array of values.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */
class VendorAPI_Blackbox_Rule_In extends VendorAPI_Blackbox_Rule
{
	/**
	 * Runs the In rule.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 *
	 * @return bool TRUE if $data is in the the rule value array
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (!is_array($this->getRuleValue()))
		{
			throw new Blackbox_Exception('Data type mismatch: rule value should be an array.');
		}

		$rv = $this->getRuleValue();
		$dv = $this->getDataValue($data);
		return in_array(strtoupper($dv), array_map('strtoupper', $rv));
	}
}

?>
