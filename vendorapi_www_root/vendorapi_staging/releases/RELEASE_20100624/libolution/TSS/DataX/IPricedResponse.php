<?php

/**
 * A response that contains a "lead cost"
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
interface TSS_DataX_IPricedResponse
{
	/**
	 * The calculated lead cost
	 * @return int
	 */
	public function getLeadCost();
}
