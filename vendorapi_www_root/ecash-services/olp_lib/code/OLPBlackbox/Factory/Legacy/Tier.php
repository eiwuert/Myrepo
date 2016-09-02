<?php
/**
 * Defines the OLPBlackbox_Factory_Legacy_Tier class.
 *
 * @author Matt Piper <matt.piper@sellingsource.com> 
 */

/**
 * Factory for legacy olp tiers.
 *
 * @author Matt Piper <matt.piper@sellingsource.com> 
 */
class OLPBlackbox_Factory_Legacy_Tier
{
	/**
	 * Gets an instance of a target collection for the tier.
	 *
	 * @param array $tier_row An array with the tier information
	 * 
	 * @return OLPBlackbox_TargetCollection
	 */
	public static function getTier($tier_row)
	{
		// Pass in the tier number
		$tier_data = new OLPBlackbox_TierStateData(array('tier_number' => $tier_row['tier_number']));
		
		// A tier is nothing more than a target collection.
		$target_collection = new OLPBlackbox_TargetCollection($tier_row['name'], $tier_data);
		$target_collection->getStateData()->weight_type = $tier_row['weight_type'];		
		
		// Set the picker we want to use.
		$target_collection->setPicker(OLPBlackbox_Factory_Picker::getPicker($tier_row['weight_type']));
		
		return $target_collection;
	}
}
?>
