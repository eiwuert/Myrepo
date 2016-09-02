<?php

class VendorAPI_PreviousCustomer_Criteria_Ssn extends VendorAPI_PreviousCustomer_Criteria_Abstract
{
	protected function getCriteriaMapping()
	{
		return array(
			'ssn' => 'ssn',
		);
	}

	protected function getIgnoredStatuses()
	{
		return array();
	}

	protected function overrideDoNotLoanLookup()
	{
		return FALSE;
	}
}

?>
