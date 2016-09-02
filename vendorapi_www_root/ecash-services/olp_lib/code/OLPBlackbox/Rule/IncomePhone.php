<?php
/**
 * Blackbox_Rule_IncomePhone class file.
 *
 * @author Rob Voss <rob.voss@sellingsource.com>
 */

/**
 * Checks the home/work phone against each other depending on the income source.
 *
 * @author Rob Voss <rob.voss@sellingsource.com>
 */
class OLPBlackbox_Rule_IncomePhone extends OLPBlackbox_Rule_NotCompare
{
	/**
	 * Just making sure we actually have the two phone numbers we need.
	 *
	 * @param Blackbox_Data $data the data the rule is running against
	 * @param Blackbox_IStateData $state_data information about the state of the Blackbox_ITarget which desires to run the rule.
	 *
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (!empty($data->phone_home) && 
			!empty($data->phone_work))
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Runs the Income Phone rule.
	 *
	 * @param BlackBox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 * 
	 * @return bool TRUE if the values do not match.
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if ($data->income_type == 'BENEFITS'
			&& $this->getRuleValue() == 'INCOME')
		{
			return TRUE;
		}
		
		return parent::runRule($data, $state_data);
	}
}

?>
