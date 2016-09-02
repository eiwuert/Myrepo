<?php

/**
 * A response that contains a "lead cost"
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
interface Clarity_UW_IPricedResponse
{
	/**
	 * The calculated lead cost
	 * @return int
	 */
	public function getLeadCost();
}
