<?php
/**
 * Interface for the factory
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
interface VendorAPI_CFE_IRulesetFactory
{
	/**
	 * Returns the ruleset
	 * @return array
	 */
	public function getRuleset(DOMDocument $doc);
}
?>