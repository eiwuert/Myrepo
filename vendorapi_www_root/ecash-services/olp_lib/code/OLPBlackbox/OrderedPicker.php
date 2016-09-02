<?php
/**
 * Picks targets in order of how they've been added to the collection, but also allows you
 * a little more flexibility and control than just using a straight-up ordered collection.
 * 
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLPBlackbox_OrderedPicker extends OLPBlackbox_Picker
{
	/**
	 * Get the next viable target and remove that target from the pickable targets
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return Blackbox_ITarget
	 */
	protected function getNextTarget(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return array_shift($this->pickable);
	}

}

?>
