<?php
/**
 * Interface for Clarity responses that need to check for Auto Fund decisions.
 *
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 */
interface Clarity_UW_IAutoFundResponse 
{
	/**
	 * Checks for the Auto Fund decision in the Clarity packet.
	 *
	 * @return bool
	 */
	public function getAutoFundDecision();
}

?>
