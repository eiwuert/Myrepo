<?php

/**
 * Impact's customer decider; removes the underactive status
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Impact_Decider extends OLPBlackbox_Enterprise_Generic_Decider
{
	/**
	 * Returns a customer classification based on their history
	 *
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @return string
	 */
	public function getDecision(OLPBlackbox_Enterprise_CustomerHistory $history)
	{
		$denied_threshold = strtotime($this->denied_threshold);
		$active = ($history->getCountActive() + $history->getCountPending());

		if ($history->getCountBad())
		{
			return self::CUSTOMER_BAD;
		}
		elseif ($history->getLastDeniedDate() > $denied_threshold)
		{
			return self::CUSTOMER_DENIED;
		}
		elseif ($active > $this->active_threshold)
		{
			return self::CUSTOMER_OVERACTIVE;
		}
		elseif ($history->getCountPaid())
		{
			return self::CUSTOMER_REACT;
		}

		return self::CUSTOMER_NEW;
	}
}

?>