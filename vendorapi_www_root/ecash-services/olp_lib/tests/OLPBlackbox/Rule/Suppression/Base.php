<?php

/**
 * Common functionality for Suppression tests.
 * 
 * @package OLPBlackbox
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
abstract class OLPBlackbox_Rule_Suppression_Base extends PHPUnit_Framework_TestCase
{
	/**
	 * Create and return a state data object with failure_reasons set.
	 * 
	 * @see OLPBlackbox_FailureReasonList
	 *
	 * @return Blackbox_IStateData State data which allows failure_reasons key.
	 */
	protected function getFailureReasonsState()
	{
		$init = array('failure_reasons' => new OLPBlackbox_FailureReasonList());
		$top = new OLPBlackbox_StateData($init);
		$target_state = new OLPBlackbox_Enterprise_TargetStateData();
		$target_state->addStateData($top);
		$target_state->loan_actions = new OLPBlackbox_Enterprise_LoanActions();
		
		return $target_state;
	}
}

?>
