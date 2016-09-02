<?php

/**
 * A generic customer decider
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Generic_Decider implements OLPBlackbox_Enterprise_ICustomerHistoryDecider
{
	/**
	 * @var int
	 */
	protected $active_threshold;

	/**
	 * @var string
	 */
	protected $denied_threshold;
	
	/**
	 * @var int
	 */
	protected $disagreed_threshold;

	/**
	 * @param int $active_threshold Number of active loans the customer can have and still be active
	 * @param string $denied_threshold Time period within which a denied loan results in denials
	 * @param int $disagreed_threshold Number of disagreed or confirmed_disagreed apps the customer can have and still be active
	 */
	public function __construct($active_threshold = 1, $denied_threshold = '-30 days', $disagreed_threshold = 1)
	{
		// Add disagreed threshold to check for disagreed apps - GForge #8774 [DW]
		$this->active_threshold = $active_threshold;
		$this->denied_threshold = $denied_threshold;
		$this->disagreed_threshold = $disagreed_threshold;
	}

	/**
	 * Returns a customer classification based on their history
	 *
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @return string
	 */
	public function getDecision(OLPBlackbox_Enterprise_CustomerHistory $history)
	{
		// Allow for a null $denied_threshold - GForge #8062 [DW]
		$denied_threshold = is_string($this->denied_threshold) ? strtotime($this->denied_threshold) : $this->denied_threshold;
		$active = ($history->getCountActive() + $history->getCountPending());

		if ($history->getCountBad())
		{
			return self::CUSTOMER_BAD;
		}
		elseif (!is_null($denied_threshold) && ($history->getLastDeniedDate() > $denied_threshold))
		{
			// If $denied_threshold is null, skip check - GForge #8062 [DW]
			return self::CUSTOMER_DENIED;
		}
		elseif ($active > $this->active_threshold)
		{
			return self::CUSTOMER_OVERACTIVE;
		}
		elseif ($active)
		{
			return self::CUSTOMER_UNDERACTIVE;
		}
		elseif ($history->getCountPaid())
		{
			return self::CUSTOMER_REACT;
		}

		return self::CUSTOMER_NEW;
	}

	/**
	 * Indicates whether a classification of customer will be bought
	 *
	 * @param string $decision
	 * @return bool
	 */
	public function isValid($decision)
	{
		switch ($decision)
		{
			case self::CUSTOMER_UNDERACTIVE:
			case self::CUSTOMER_REACT:
			case self::CUSTOMER_NEW:
				return TRUE;
		}
		return FALSE;
	}
}

?>