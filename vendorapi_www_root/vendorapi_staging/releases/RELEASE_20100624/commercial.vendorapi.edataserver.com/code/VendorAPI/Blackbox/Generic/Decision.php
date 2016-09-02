<?php

/**
 * Represents a decision returned by a PreviousCustomer_Decider.
 *
 * @package VendorAPI
 * @subpackage PreviousCustomer
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class VendorAPI_Blackbox_Generic_Decision
{
	/**
	 * Customer has been denied in the past; will not be funded
	 */
	const CUSTOMER_DENIED = 'denied';

	/**
	 * Customer has withdrawn within the threshold; will not be funded
	 */
	const CUSTOMER_WITHDRAWN = 'withdrawn';

	/**
	 * Customer has a bad loan in their history; will not be funded
	 */
	const CUSTOMER_BAD = 'bad';

	/**
	 * Customer has too many active accounts; will not be funded again
	 */
	const CUSTOMER_OVERACTIVE = 'overactive';

	/**
	 * Customer has active accounts; can still be funded
	 */
	const CUSTOMER_UNDERACTIVE = 'underactive';

	/**
	 * Customer has not been seen before; can be funded
	 */
	const CUSTOMER_NEW = 'new';

	/**
	 * Customer currently has no active accounts, but has been funded in the past; can be funded
	 */
	const CUSTOMER_REACT = 'new/react';

	// Added to check for multiple disagrees - GForge #8774 [DW]
	/**
	 * Customer has cancelled or disagreed to multiple apps in last 24 hours; will not be funded
	 */
	const CUSTOMER_DISAGREED = 'cancel/disagree';

	/**
	 * Customer is on the Do Not Loan list without having an override to save them.
	 */
	const CUSTOMER_DONOTLOAN = 'do_not_loan';

	/**
	 * The decision token for this object.
	 *
	 * @var string
	 */
	protected $decision;

	/**
	 * @var string
	 */
	protected $debug_info;

	/**
	 * @param string $decision One of the decision constants from this class.
	 */
	public function __construct($decision, $debug_info = NULL)
	{
		if (!$this->validDecision($decision) && !$this->invalidDecision($decision))
		{
			throw new InvalidArgumentException(sprintf(
				'unknown decision %s', $decision)
			);
		}

		$this->decision = $decision;
		$this->debug_info = $debug_info;
	}

	/**
	 * Decides whether the decision this objects represents is valid.
	 *
	 * 'Valid' in this context is the same as valid for rules. That is, if an
	 * application should be failed on the basis of this decision, return FALSE.
	 *
	 * @return bool
	 */
	public function isValid()
	{
		if ($this->validDecision($this->decision))
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Determines if a string token is a invalid state.
	 *
	 * @param string $decision The string constant token.
	 *
	 * @return bool
	 */
	protected function invalidDecision($decision)
	{
		switch ($decision)
		{
			case self::CUSTOMER_BAD:
			case self::CUSTOMER_DENIED:
			case self::CUSTOMER_DISAGREED:
			case self::CUSTOMER_OVERACTIVE:
			case self::CUSTOMER_WITHDRAWN:
			case self::CUSTOMER_DONOTLOAN:
				return TRUE;
		}

		return FALSE;
	}

	/**
	 * Determines if a decision is valid.
	 *
	 * @param string $decision The decision constant to check.
	 *
	 * @return bool Whether the decision string token is a valid state.
	 */
	protected function validDecision($decision)
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

	/**
	 * Return the decision string token.
	 *
	 * @return string
	 */
	public function getDecision()
	{
		return $this->decision;
	}

	/**
	 * Return the decision string token.
	 *
	 * @return string
	 */
	public function getDebugInfo()
	{
		return $this->debug_info;
	}

	/**
	 * The string representation of this object is the string token it encapsulates.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getDecision();
	}
}

?>
