<?php
/**
 * OLPBlackbox_Rule_BankAccountType class file.
 * This rule class was added as there was no single standard rule type that could 
 * handle the logic well in dealing with BOTH and NONE. [GF#17707][AE]
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */

/**
 * Check bank account type supplied against the allowable types fore the target
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPBlackbox_Rule_BankAccountType extends OLPBlackbox_Rule implements OLPBlackbox_Factory_Legacy_IReusableRule
{
	/*
	 * Constants for the account types and values specified in the rule definition
	 * All account types are to be defined as upper case as they will be matched against
	 * upper case strings
	 */
	const CHECKING = 'CHECKING';
	const SAVINGS = 'SAVINGS';
	const BOTH = '';
	
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
		return (!empty($data->bank_account_type));
	}

	/**
	 * Checks to see if the bank account supplied is an allowable bank account type
	 * As the previous "standard" rule uses EqualsNoCase, this class provides 
	 *   
	 *
	 * @param Blackbox_Data $data Data to run validation checks on
	 * @param Blackbox_IStateData $state_data an IStateData object which contains the caller's (Blackbox_ITarget) state.
	 * 
	 * @return bool
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{

		// Get alowed account types for the rule value

		// Convert the rule value to upper case as the rule is case insensitive
		$rule_value = strtoupper($this->getRuleValue());
		switch ($rule_value)
		{
			// Both type gets CHECKING and SAVINGS
			case self::BOTH:
				$allowed_types = array(self::CHECKING, self::SAVINGS);
				break;
			// By default, we use the rule value as is
			default:
				$allowed_types = array($rule_value);
				break;
		}
		
		// Get the account type out of the data array and covert it to upper case
		$account_type = strtoupper($data->bank_account_type);
		
		// Return TRUE/FALSE based on in_array for account type against allowed
		// account types
		return in_array($account_type, $allowed_types);
	}
}

?>
