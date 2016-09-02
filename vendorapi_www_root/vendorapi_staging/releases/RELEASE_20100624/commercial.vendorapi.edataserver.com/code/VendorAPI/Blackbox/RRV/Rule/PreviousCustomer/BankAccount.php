<?php

/**
 * RRV checks by only ABA + Account; no SSN
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_Blackbox_RRV_Rule_PreviousCustomer_BankAccount extends VendorAPI_Blackbox_Rule_PreviousCustomer_BankAccountSsn
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
	protected function getConditions(Blackbox_Data $data)
	{
		return array(
			'bank_aba' => $data->bank_aba,
			'bank_account' => $data->bank_account,
		);
	}
}

?>