<?php

/**
 * Interface for interacting with previous customer criterion definitions.
 */
interface VendorAPI_PreviousCustomer_ICriterion
{
	/**
	 * Returns criteria adequate for use in the app service previous customer calls
	 *
	 * @param array $app_data
	 * @return array
	 */
	public function getAppServiceObject(array $app_data);

	/**
	 * Processes a given array of apps based on the criteria definition and returns the processed results.
	 *
	 * @param array $apps
	 * @return array
	 */
	public function postProcessResults(array $apps);
}

?>
