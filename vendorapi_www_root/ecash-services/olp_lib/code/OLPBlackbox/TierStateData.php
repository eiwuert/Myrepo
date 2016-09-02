<?php

/**
 * Class for holding state information for tiers.
 *
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com> 
 */
class OLPBlackbox_TierStateData extends OLPBlackbox_TargetCollectionStateData
{
	/**
	 * Constructs an OLPBlackbox_TierStateData with optional values.
	 *
	 * Note: Data set with the constructor ignores mutable/immutable rules.
	 *
	 * @param array $data data to initialize the object with
	 * @return void
	 */
	function __construct($data = NULL)
	{
		// initialize allowable keys (mutable_keys or immutable_keys)
		$this->immutable_keys[] = 'tier_number';
		
		parent::__construct($data);
	}
}
?>
