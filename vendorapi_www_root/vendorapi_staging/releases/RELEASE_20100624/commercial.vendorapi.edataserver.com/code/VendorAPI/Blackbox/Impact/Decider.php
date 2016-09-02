<?php

/**
 * Impact's customer decider
 *
 * Removes the underactive status (pointless, since their threshold is 0, but I digress)
 * Removes the disagreed checks (time and count threshold)
 *
 * @package VendorAPI
 * @subpackage PreviousCustomer
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_Impact_Decider extends VendorAPI_Blackbox_Generic_Decider
{
	/**
	 * Returns a customer classification based on their history
	 *
	 * @param ECash_CustomerHistory $history
	 * @return VendorAPI_Blackbox_Generic_Decision
	 */
	public function getDecision(ECash_CustomerHistory $history)
	{
		$active = ($history->getCountActive() + $history->getCountPending());
		$decision = NULL;

		if ($this->isDNL($history))
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DONOTLOAN;
		}
		elseif ($this->isActiveWithCompany($history))
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_OVERACTIVE;
		}
		elseif ($history->getCountBad())
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_BAD;
		}
		elseif ($this->isDenied($history))
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_DENIED;
		}
		elseif ($active > $this->active_threshold)
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_OVERACTIVE;
		}
		elseif ($history->getCountPaid() || $history->getCountSettled())
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_REACT;
		}
		else
		{
			$decision = VendorAPI_Blackbox_Generic_Decision::CUSTOMER_NEW;
		}

		return new VendorAPI_Blackbox_Generic_Decision($decision);
	}
}

?>
