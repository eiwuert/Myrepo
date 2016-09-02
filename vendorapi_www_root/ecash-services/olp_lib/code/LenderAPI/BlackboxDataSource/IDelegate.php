<?php

/**
 * A delegate used by {@see LenderAPI_BlackboxDataSource} to translate/produce
 * fields that must be offered to the VendorAPI transform layer.
 *
 * @version $Id: IDelegate.php 31577 2009-01-14 23:48:44Z olp_release $
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package VendorAPI
 */
interface LenderAPI_BlackboxDataSource_IDelegate
{
	/**
	 * Return the value of field this class is designed to return.
	 * @return mixed Usually a string value, to be used in XSLT transforms.
	 */
	public function value();

	public function setValue($value);
}
?>
