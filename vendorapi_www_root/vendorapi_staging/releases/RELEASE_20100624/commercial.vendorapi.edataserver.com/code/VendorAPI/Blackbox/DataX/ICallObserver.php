<?php

/**
 * Observer of DataX calls
 *
 * Generally used to set adverse actions on failures.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
interface VendorAPI_Blackbox_DataX_ICallObserver
{
	/**
	 * Fired when a complete call has been made
	 *
	 * @param VendorAPI_Blackbox_Rule_DataX $caller
	 * @param TSS_DataX_Result $result
	 * @param Blackbox_IStateData $state
	 * @param Blackbox_Data $data
	 * @return void
	 */
	public function onCall(
		VendorAPI_Blackbox_Rule_DataX $caller,
		TSS_DataX_Result $result,
		$state,
		VendorAPI_Blackbox_Data $data
	);
}
?>