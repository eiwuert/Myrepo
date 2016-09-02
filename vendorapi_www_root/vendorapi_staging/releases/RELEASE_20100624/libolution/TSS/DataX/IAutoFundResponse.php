<?php
/**
 * Interface for DataX responses that need to check for Auto Fund decisions.
 *
 * @author Richard Bunce <richard.bunce@sellingsource.com>
 */
interface TSS_DataX_IAutoFundResponse 
{
	/**
	 * Checks for the Auto Fund decision in the DataX packet.
	 *
	 * @return bool
	 */
	public function getAutoFundDecision();
}

?>
