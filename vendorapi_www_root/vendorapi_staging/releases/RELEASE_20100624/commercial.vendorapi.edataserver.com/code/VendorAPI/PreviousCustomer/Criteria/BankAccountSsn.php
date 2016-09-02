<?php

class VendorAPI_PreviousCustomer_Criteria_BankAccountSsn extends VendorAPI_PreviousCustomer_Criteria_Abstract
{
	protected function getCriteriaMapping()
	{
		return array(
			'bank_aba' => 'bankAba',
			'bank_account' => 'bankAccount',
			'ssn' => 'ssn'
		);
	}

	protected function getIgnoredStatuses()
	{
		return array('settled', 'paid');
	}

	protected function overrideDoNotLoanLookup()
	{
		return TRUE;
	}
}

?>
