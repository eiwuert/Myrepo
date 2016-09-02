<?php

/**
 * OPM customer decider
 * @package VendorAPI
 * @subpackage PreviousCustomer
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 */
class VendorAPI_Blackbox_OPM_Decider extends VendorAPI_Blackbox_Generic_Decider
{

	/**
	 * Returns a customer classification based on their history
	 *
	 * @param ECash_CustomerHistory $history
	 * @return VendorAPI_Blackbox_Generic_Decision
	 */
	public function getDecision(ECash_CustomerHistory $history)
	{

		$decision = NULL;
		/*
		* OPM does not want any application that is not a react or organic lead to be bought again if they exist [#35257]
		*/
		if ($history->getApplicationCount() > 0)
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DENIED;
		}
		else
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_NEW;
		}

		return new VendorAPI_Blackbox_Generic_Decision($decision);
	}
}


?>
