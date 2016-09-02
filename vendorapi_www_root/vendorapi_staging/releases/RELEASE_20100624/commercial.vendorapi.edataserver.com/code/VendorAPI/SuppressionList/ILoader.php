<?php

/**
 * An interface for suppression list loaders.
 *
 * @author Mike Lively <mike.lively@sellingsource.com>
 */
interface VendorAPI_SuppressionList_ILoader
{
	/**
	 * Returns an array of suppression lists by the given name and type.
	 *
	 * @param string $name
	 * @param string $type
	 * @return VendorAPI_SuppressionList_Wrapper
	 */
	public function getByName($name, $type = NULL);
}

?>