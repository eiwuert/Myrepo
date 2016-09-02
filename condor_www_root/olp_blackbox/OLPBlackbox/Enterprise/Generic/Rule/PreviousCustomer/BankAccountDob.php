<?php

/**
 * The previous customer check by bank aba, account, and SSN
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomer_BankAccountDob extends OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomer
{
	/**
	 * Gives a short name for the rule
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'ACCOUNT_DOB';
	}

	/**
	 * Indicates whether the rule has the proper information to run
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state)
	{
		return isset($data->bank_aba)
			&& isset($data->bank_account)
			&& isset($data->dob);
	}

	/**
	 * Gets the conditions for the ECash provider
	 *
	 * @param Blackbox_Data $data
	 * @return array
	 */
	protected function getECashConditions(Blackbox_Data $data)
	{
		return array(
			'bank_aba' => $data->bank_aba,
			'bank_account' => $data->permutated_bank_account,
			'dob' => $data->dob,
		);
	}

	/**
	 * Gets the conditions for the OLP provider
	 *
	 * @param Blackbox_Data $data
	 * @return unknown
	 */
	protected function getOLPConditions(Blackbox_Data $data)
	{
		return NULL;
	}
}

?>