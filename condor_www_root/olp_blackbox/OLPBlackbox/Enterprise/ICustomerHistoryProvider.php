<?php

/**
 * Provides customer histories by various criterion
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
interface OLPBlackbox_Enterprise_ICustomerHistoryProvider
{
	/**
	 * Excludes an application from the generated history
	 *
	 * @param int $app_id
	 * @return void
	 */
	public function excludeApplication($app_id);

	/**
	 * Finds customer history by the given conditions
	 *
	 * @param array $conditions
	 * @param OLPBlackbox_Enterprise_CustomerHistory $history
	 * @return OLPBlackbox_Enterprise_CustomerHistory
	 */
	public function getHistoryBy(array $conditions, OLPBlackbox_Enterprise_CustomerHistory $history = NULL);
}

?>