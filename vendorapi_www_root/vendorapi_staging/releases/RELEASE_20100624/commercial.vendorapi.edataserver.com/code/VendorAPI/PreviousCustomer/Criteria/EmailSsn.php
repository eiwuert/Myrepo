<?php

class VendorAPI_PreviousCustomer_Criteria_EmailSsn extends VendorAPI_PreviousCustomer_Criteria_Abstract
{
	protected function getCriteriaMapping()
	{
		return array(
			'email' => 'email',
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
