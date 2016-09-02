<?php

/**
 * A criteria object for pulling all applications from a customer by bank account with NO aba.
 *
 * This is done to accomplish the BofA fruad check.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_PreviousCustomer_Criteria_BankAccountNoAba extends VendorAPI_PreviousCustomer_Criteria_Abstract
{
	protected function getCriteriaMapping()
	{
		return array(
			'bank_account' => 'bankAccount',
		);
	}

	protected function getIgnoredStatuses()
	{
		return array();
	}

	protected function overrideDoNotLoanLookup()
	{
		return TRUE;
	}
}

?>
