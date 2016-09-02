<?php
/**
 * Defines the OLPBlackbox_Rule_Impact_WinnerVerifiedStatus class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */

/**
 * Rule class to log information about whether the application being processed needs manual verification.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Impact_Rule_WinnerVerifiedStatus extends OLPBlackbox_Enterprise_Generic_Rule_WinnerVerifiedStatus
{
	/**
	 * Determines if the this rule object has the data it needs to run.
	 *
	 * @param Blackbox_Data $data State data about the application we're considering.
	 * @param Blackbox_IStateData $state_data Data about the current ITarget running this IRule
	 *
	 * @return bool Whether or not this rule should be run.
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return $this->canRunSameWorkAndHomePhoneCheck($data, $state_data)
			&& parent::canRun($data, $state_data);
	}

	/**
	 * Run the checks for this rule.
	 *
	 * @param Blackbox_Data $data State data about the application we're processing.
	 * @param Blackbox_IStateData $state_data State data about the ITarget running
	 *
	 * @return boolean Whether the rule passed or not.
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->sameWorkAndHomePhoneCheck($data, $state_data);

		return parent::runRule($data, $state_data);
	}
}
?>
