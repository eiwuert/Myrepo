<?php

/**
 * Generic interface for vendorapi clients.
 * 
 * @package LendorAPI
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
interface LenderAPI_IClient
{
	/**
	 * Post a lead to a vendor.
	 * 
	 * @param string $vendor_name Campaign to post to.
	 * @param mixed $data_sources A list of data sources.
	 * @return response object
	 */
	public function postLead($vendor_name, $data_sources);
	
	/**
	 * Returns the response object this client will/does populate.
	 *
	 * @return LenderAPI_Response
	 */
	public function getResponse(); 
}
?>
