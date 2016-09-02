<?php
/**
 * Withheld target rule.
 * 
 * This rule runs before a target is picked to determine if a previously attempted campaign had this
 * campaign's name in the withheld target array/list. If it does exist in that array, then the rule
 * fails.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_WithheldTargets extends OLPBlackbox_Rule implements OLPBlackbox_IPickTargetRule
{
	/**
	 * Run the withheld target rule.
	 *
	 * @param Blackbox_Data $data the data to run the rule on
	 * @param Blackbox_IStateData $state_data the state data to run the rule on
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$valid = TRUE;
		if (!empty($state_data->withheld_targets))
		{
			// If we're not in the array, we're valid
			$valid = !in_array(strtolower($state_data->campaign_name), $state_data->withheld_targets);
		}
		
		return $valid;
	}
	
	/**
	 * We can always run this rule.
	 *
	 * @param Blackbox_Data $data the data to check if we can run this rule
	 * @param Blackbox_IStateData $state_data the state data to check if we can run this rule
	 * @return unknown
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}
	
	/**
	 * Run when the rule returns as valid.
	 *
	 * @param Blackbox_Data $data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// Don't hit an event log entry
		
		$this->triggerEvents(__FUNCTION__, $state_data);
	}
}
?>
