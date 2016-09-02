<?php

/**
 * Makes a decision based on the customer history
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
interface OLPBlackbox_Enterprise_ICustomerHistoryDecider
{
	/**
	 * Customer has been denied in the past; will not be funded
	 */
	const CUSTOMER_DENIED = 'denied';

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
	 * Decides the type of customer based on their history
	 *
	 * @param OLPBlackbox_Enterprise_CustomerHistory $result
	 * @return string
	 */
	public function getDecision(OLPBlackbox_Enterprise_CustomerHistory $result);

	/**
	 * Indicates whether a type of customer is valid
	 *
	 * @param string $decision
	 * @return bool
	 */
	public function isValid($decision);
}

?>