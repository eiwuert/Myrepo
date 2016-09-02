<?php

/**
 * The previous customer check factory for Impact
 * They use the basic checks, except their active threshold is 0
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Impact_PreviousCustomerFactory extends VendorAPI_Blackbox_Generic_PreviousCustomerFactory
{
	/**
	 * @see VendorAPI_Blackbox_Generic_PreviousCustomerFactory
	 * @var string
	 */
	protected $denied_time_threshold = '-60 days';

	/**
	 * Impact has their own special bank account check that doesn't include the SSN
	 *
	 * @param VendorAPI_PreviousCustomer_CriteriaContainer $container
	 * @return void
	 */
	protected function addCriteria(VendorAPI_PreviousCustomer_CriteriaContainer $container)
	{
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_Ssn($this->getCustomerHistoryStatusMap()));
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_EmailDob($this->getCustomerHistoryStatusMap()));
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_HomePhone($this->getCustomerHistoryStatusMap()));
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_BankAccount($this->getCustomerHistoryStatusMap()));
		$container->addCriteria(new VendorAPI_PreviousCustomer_Criteria_License($this->getCustomerHistoryStatusMap()));
	}
}

?>
