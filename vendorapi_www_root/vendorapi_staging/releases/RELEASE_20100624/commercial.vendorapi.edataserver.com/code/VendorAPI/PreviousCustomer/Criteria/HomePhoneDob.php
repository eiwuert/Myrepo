<?php

class VendorAPI_PreviousCustomer_Criteria_HomePhoneDob extends VendorAPI_PreviousCustomer_Criteria_Abstract
{
	protected function getCriteriaMapping()
	{
		return array(
			'phone_home' => 'homePhone',
			'dob' => 'dateOfBirth'
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
