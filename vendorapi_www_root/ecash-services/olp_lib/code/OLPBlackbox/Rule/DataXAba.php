<?php

require_once 'aba.1.php';

/**
 * Runs the datax aba check against the customer's bank aba number
 *
 * @author Eric Johney <eric.johney@sellingsource.com>
 */
class OLPBlackbox_Rule_DataXAba extends OLPBlackbox_Rule implements OLPBlackbox_Factory_Legacy_IReusableRule
{
	/**
	 * Returns whether the rule has a bank_aba to run
	 * If the rule can't be run, onSkip() will be called
	 *
	 * @param Blackbox_Data $data the data the rule is running against
	 * @param Blackbox_IStateData $state_data information about the state of the Blackbox_ITarget which desires to run the rule.
	 *
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return !empty($data->bank_aba);
	}
	
	/**
	 * Runs the DataXAba rule.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 * 
	 * @return bool
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (empty($state_data->datax_aba_decision))
		{
			// use ABA_1 class from lib5
			$aba = new ABA_1();
			
			// pass NULL for the license key as it is unused by this call.  It does need the app id and bank_aba to check
			$state_data->datax_aba_decision = $aba->VerifyVerbose(NULL, $data->application_id, $data->bank_aba);
		}

		// the aba check passed if we didn't recieve an error back and we didn't get a fail_code back
		return !isset($state_data->datax_aba_decision['DataXError']) && !isset($state_data->datax_aba_decision['fail_code']);
	}
}
?>