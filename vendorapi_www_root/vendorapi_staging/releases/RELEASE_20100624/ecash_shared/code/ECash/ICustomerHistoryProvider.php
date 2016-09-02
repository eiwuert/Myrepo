<?php

/**
 * Provides customer histories by various criterion
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
interface ECash_ICustomerHistoryProvider
{
	/**
	 * Excludes an application from the generated history
	 *
	 * @param int $app_id
	 * @return void
	 */
	public function excludeApplication($app_id);

	/**
	 * Sets a single company to retrieve results for
	 *
	 * @param string $name
	 * @return void
	 */
	public function setCompany($name);

	/**
	 * Finds customer history by the given conditions
	 *
	 * @param array $conditions
	 * @param ECash_CustomerHistory $history
	 * @return ECash_CustomerHistory
	 */
	public function getHistoryBy(array $conditions, ECash_CustomerHistory $history = NULL);
}

?>