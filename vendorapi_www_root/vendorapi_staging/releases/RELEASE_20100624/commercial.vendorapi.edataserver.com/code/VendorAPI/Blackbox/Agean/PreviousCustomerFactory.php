<?php

/**
 * The previous customer check factory for Agean
 * They use the basic checks, except they don't include recovered apps as paid
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Agean_PreviousCustomerFactory extends VendorAPI_Blackbox_Generic_PreviousCustomerFactory
{
	/**
	 * Returns agean's customer status map
	 * @return AGEAN_VendorAPI_PreviousCustomer_CustomerHistoryStatusMap
	 */
	protected function getCustomerHistoryStatusMap()
	{
		return new AGEAN_VendorAPI_PreviousCustomer_CustomerHistoryStatusMap();
	}
}

?>