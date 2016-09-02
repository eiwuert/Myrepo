<?php

/**
 * Makes a decision based on the customer history
 *
 * @package OLPVendorAPI
 * @subpackage PreviousCustomer
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
interface VendorAPI_Blackbox_ICustomerHistoryDecider
{
	/**
	 * Decides the type of customer based on their history
	 *
	 * @param ECash_CustomerHistory $result
	 * @return VendorAPI_Blackbox_Generic_Decision
	 */
	public function getDecision(ECash_CustomerHistory $result);
}

?>