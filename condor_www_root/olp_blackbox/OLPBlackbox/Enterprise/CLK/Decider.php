<?php

/**
 * CLK's customer decider; adds the customer_disagreed status
 * @author David Watkins <david.watkins@sellingsource.com>
 */
class OLPBlackbox_Enterprise_CLK_Decider extends OLPBlackbox_Enterprise_Generic_Decider
{
	/**
	 * Returns a customer classification based on their history
	 *
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @return string
	 */
	public function getDecision(OLPBlackbox_Enterprise_CustomerHistory $history)
	{
		// If parent::getDecision flags it as new, check to make sure it shouldn't be denied due to multiple disagrees.
		if (($parent_decision = parent::getDecision($history)) === self::CUSTOMER_NEW)
		{
			$disagreed = ($history->getCountDisagreed() + $history->getCountConfirmedDisagreed());
		
			// Check if customer exceeds disagreed_threshold and has no previous paid apps (not a react)
			if (($disagreed > $this->disagreed_threshold) && ($history->getCountPaid() == 0))
			{
				return self::CUSTOMER_DISAGREED;
			}
		}
		
		return $parent_decision;
	}
}

?>