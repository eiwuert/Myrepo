<?php

/**
 * Observer of Factor Trust calls
 *
 * Generally used to set adverse actions on failures.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
interface VendorAPI_Blackbox_FactorTrust_ICallObserver
{
	/**
	 * Fired when a complete call has been made
	 *
	 * @param VendorAPI_Blackbox_Rule_FactorTrust $caller
	 * @param TSS_DataX_Result $result
	 * @param Blackbox_IStateData $state
	 * @param Blackbox_Data $data
	 * @return void
	 */
	public function onCall(
		VendorAPI_Blackbox_Rule_FactorTrust $caller,
		FactorTrust_UW_Result $result,
		$state,
		VendorAPI_Blackbox_Data $data
	);
}
?>