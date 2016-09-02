<?php
/**
 * Defines the OLPBlackbox_Factory_Legacy_TargetCollection class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */

/**
 * Factory for legacy olp target collections.
 *
 * @author Matt Piper <matt.piper@sellingsource.com>
 */
class OLPBlackbox_Factory_Legacy_TargetCollection
{
	/**
	 * Gets an instance of a target collection.
	 *
	 * @param array $target_row An array with the tier information
	 *
	 * @return ITarget
	 */
	public static function getTargetCollection($target_row)
	{
		$property_short = strtolower($target_row['property_short']);

		if (strcasecmp($property_short, EnterpriseData::COMPANY_CLK) === 0)
		{
			// We know clk has specific rules and stuff we need to add to the
			// target collection, so call the CLK factory to set that all up.
			$target_collection = OLPBlackbox_Enterprise_CLK_Factory_Legacy_TargetCollection::getTargetCollection($target_row);
		}
		else
		{
			// Create the target collection.
			$target_collection = new OLPBlackbox_TargetCollection($target_row['target_name']);
			// Set the picker we want to use.
			$target_collection->setPicker(OLPBlackbox_Factory_Picker::getPicker('PERCENT'));
		}
		
		$target_collection->getStateData()->weight_type = 'PERCENT';

		return $target_collection;
	}
}
?>
