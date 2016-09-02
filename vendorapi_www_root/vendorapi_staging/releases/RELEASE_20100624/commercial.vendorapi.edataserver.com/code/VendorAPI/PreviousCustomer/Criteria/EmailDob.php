<?php

class VendorAPI_PreviousCustomer_Criteria_EmailDob extends VendorAPI_PreviousCustomer_Criteria_Abstract
{
	protected function getCriteriaMapping()
	{
		return array(
			'email' => 'email',
			'dob' => 'dateOfBirth',
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
