<?php

/**
 * Checks to see if a date is older than a specified number of months.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */
class OLPBlackbox_Rule_DateMonthsBefore extends OLPBlackbox_Rule
{
	/**
	 * Runs the Date Months Before rule.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 * 
	 * @return bool TRUE if $data comes after $this->rule_value
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$rule_value = $this->getRuleValue();

		$check_date = strtotime("-{$rule_value} months");
		$data_value = strtotime($this->getDataValue($data)); 

		return ($data_value < $check_date);
	}
}

?>
