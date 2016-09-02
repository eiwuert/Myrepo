<?php

class VendorAPI_PreviousCustomer_Criteria_BankAccountDob extends VendorAPI_PreviousCustomer_Criteria_Abstract
{
	protected function getCriteriaMapping()
	{
		return array(
			'bank_aba' => 'bankAba',
			'bank_account' => 'bankAccount',
			'dob' => 'dateOfBirth',
		);
	}

	protected function getIgnoredStatuses()
	{
		return array('paid', 'settled');
	}

	protected function overrideDoNotLoanLookup()
	{
		return TRUE;
	}
}

?>
