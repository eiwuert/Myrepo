<?php

/**
 * The previous customer check factory for AALM
 * AALM wants a 60 day disagreed threshold
 * 
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class VendorAPI_Blackbox_AALM_PreviousCustomerFactory extends VendorAPI_Blackbox_Generic_PreviousCustomerFactory
{
	/**
	 * @see VendorAPI_Blackbox_Generic_PreviousCustomerFactory
	 * @var string
	 */
	protected $disagreed_time_threshold = '-60 days';

}

?>
