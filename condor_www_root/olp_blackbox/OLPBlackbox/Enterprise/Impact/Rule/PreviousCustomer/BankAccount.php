<?php

/**
 * Impact checks by only ABA + Account; no SSN
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Impact_Rule_PreviousCustomer_BankAccount extends OLPBlackbox_Enterprise_Generic_Rule_PreviousCustomer_BankAccount
{
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
			&& isset($data->bank_account);
	}

	/**
	 * The conditions for the ECash provider
	 *
	 * @param Blackbox_Data $data
	 * @return array
	 */
	protected function getECashConditions(Blackbox_Data $data)
	{
		return array(
			'bank_aba' => $data->bank_aba,
			'bank_account' => $data->bank_account,
		);
	}
}

?>