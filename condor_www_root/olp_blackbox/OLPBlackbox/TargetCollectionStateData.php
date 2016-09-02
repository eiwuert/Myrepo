<?php
/**
 * Definition of OLPBlackbox_TargetCollectionStateData class.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */

/**
 * Class for holding state information for OLPBlackbox_TargetCollection objects.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */
class OLPBlackbox_TargetCollectionStateData extends Blackbox_StateData
{
	/**
	 * Constructs a OLPBlackbox_TargetCollectionStateData with optional values.
	 *
	 * Note: Data set with the constructor ignores mutable/immutable rules.
	 *
	 * @param array $data data to initialize the object with
	 * @return void
	 */
	function __construct($data = NULL)
	{
		// initialize allowable keys (mutable_keys or immutable_keys)
		$this->immutable_keys[] = 'target_collection_name';
		$this->immutable_keys[] = 'tier_number';
		
		parent::__construct($data);
	}
}
?>
