<?php
/**
 * OLPBlackbox_Rule_AbaCheck class file.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Checks to see if the customers bank aba/account number matches to a 
 * known list of bad account info.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Rule_AbaCheck extends OLPBlackbox_Rule implements OLPBlackbox_Factory_Legacy_IReusableRule
{
	
	/**
	 * Returns whether the rule has sufficient data to run
	 * If the rule can't be run, onSkip() will be called
	 *
	 * @param Blackbox_Data $data the data the rule is running against
	 * @param Blackbox_IStateData $state_data information about the state of the Blackbox_ITarget which desires to run the rule.
	 *
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return $data->bank_aba;
	}
	
	/**
	 * Runs the AbaCheck rule.
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 * 
	 * @return bool
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// Since the Aba_Bad function will return TRUE if a an account is bad,
		// we need to inverse it to FALSE to cause it to fail the rule.
		$bank_account = is_null($data->bank_account) ? NULL : $data->bank_account;
		$valid = !(Aba_Bad($data->bank_aba, $bank_account));
		
		return $valid;
	}
	/**
	 * When this rule is determined to be not valid, we must also set a state_data flag.
	 *
	 * @param Blackbox_Data $data Information app we're processing.
	 * @param Blackbox_IStateData $data Information about the calling ITarget.
	 * 
	 * @return void
	 */
	protected function onInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$state_data->global_rule_failure = 'AbaCheck';
		parent::onInvalid($data, $state_data);
	}
}

?>
