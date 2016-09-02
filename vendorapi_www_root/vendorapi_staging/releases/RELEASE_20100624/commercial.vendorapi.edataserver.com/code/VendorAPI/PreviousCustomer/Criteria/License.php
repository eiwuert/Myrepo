<?php

class VendorAPI_PreviousCustomer_Criteria_License extends VendorAPI_PreviousCustomer_Criteria_Abstract
{
	//TODO: EPMDEV we need to add state to this check too.
	protected function getCriteriaMapping()
	{
		return array(
			'legal_id_number' => 'legalIdNumber',
		);
	}

	protected function skipCriteria($app_data)
	{
		return !isset($app_data['legal_id_number'])
			|| in_array(strtolower(trim($app_data['legal_id_number'])), array('', 'none', 'n/a', 'na'))
			|| preg_match('#^0+$#', $app_data['legal_id_number']);
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
