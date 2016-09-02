<?php
/**
 * Blackbox_ITarget interface file
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

interface Blackbox_ITarget
{
	/**
	 * Runs the rules for this target and returns whether the target is still valid.
	 * 
	 * Returns a TRUE if all rules passed, and FALSE if any rules failed.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_StateData $state_data state data to do validation on
	 * @return bool
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data);
	
	/**
	 * Picks a valid target.
	 *
	 * @param Blackbox_Data $data data to run any additional checks on
	 * @return Blackbox_IWinner|bool
	 */
	public function pickTarget(Blackbox_Data $data);
	
	/**
	 * Sets the rules for a target.
	 * 
	 * Rules can be a RuleCollection or an individual Rule.
	 *
	 * @param Blackbox_IRule $rules the rules to run on the target
	 * @return void
	 */
	public function setRules(Blackbox_IRule $rules);
	
	/**
	 * Return the state data for a target.
	 *
	 * @return Blackbox_StateData
	 */
	public function getStateData();
}
?>
