<?php

/**
 * A criteria object for pulling all applications from a customer by bank account and a list of abas.
 *
 * This is done to accomplish the BofA fruad check.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
class VendorAPI_PreviousCustomer_Criteria_BankAccountGroupedAba extends VendorAPI_PreviousCustomer_Criteria_Abstract
{
	/**
	 * @var array
	 */
	protected $bank_abas;

	/**
	 * Overridden to allow passing in a list of bank abas.
	 * @param VendorAPI_PreviousCustomer_CustomerHistoryStatusMap $status_map
	 * @param array $bank_abas
	 * @return void
	 */
	public function __construct(VendorAPI_PreviousCustomer_CustomerHistoryStatusMap $status_map, array $bank_abas = array())
	{
		parent::__construct($status_map);
		$this->bank_abas = $bank_abas;
	}

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

	protected function getAdditionalCriteria()
	{
		return array(
			'bankAba' => array('in', implode(',', $this->bank_abas))
		);
	}

}

?>
