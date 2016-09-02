<?php

/**
 * Adds customer_history to mutable state data
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_TargetCollectionStateData extends OLPBlackbox_TargetCollectionStateData
{
	/**
	 * Make a StateData object for use by OLPBlackbox_Enterprise_TargetCollection.
	 * 
	 * @param array $data associative array of keys to set on this state data.
	 * 
	 * @return void
	 */
	public function __construct(array $data = NULL)
	{
		$this->mutable_keys[] = 'customer_history';
		parent::__construct($data);
	}
}

?>