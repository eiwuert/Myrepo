<?php

class VendorAPI_PreviousCustomer_Criteria_BankAccount extends VendorAPI_PreviousCustomer_Criteria_Abstract
{
	protected function getCriteriaMapping()
	{
		return array(
			'bank_aba' => 'bankAba',
			'bank_account' => 'bankAccount',
		);
	}

	protected function getIgnoredStatuses()
	{
		return array('denied', 'paid', 'active', 'pending');
	}

	protected function overrideDoNotLoanLookup()
	{
		return TRUE;
	}
}

?>
