<?php

/**
 * An interface that describes a Blackbox Rule
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
 */
interface Blackbox_IRule
{
	/**
	 * Determines whether a rule is valid.
	 *
	 * @param mixed $data The data used to validate the rule.
	 * @param obj $state_data The mutable state data object for the ITarget running the rule.
	 * 
	 * @return bool
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data);
}

?>
