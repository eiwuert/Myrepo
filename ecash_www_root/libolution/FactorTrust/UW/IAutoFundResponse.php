<?php
/**
 * Interface for FactorTrust responses that need to check for Auto Fund decisions.
 *
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 */
interface FactorTrust_UW_IAutoFundResponse 
{
	/**
	 * Checks for the Auto Fund decision in the FactorTrust packet.
	 *
	 * @return bool
	 */
	public function getAutoFundDecision();
}

?>
