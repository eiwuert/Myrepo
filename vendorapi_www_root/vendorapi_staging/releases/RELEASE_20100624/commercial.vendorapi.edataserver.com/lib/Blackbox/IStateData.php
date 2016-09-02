<?php
/**
 * Blackbox_IStateData interface definition.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */

/**
 * Interface for StateData classes for holding information related to objects running rules (such as ITargets)
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */
interface Blackbox_IStateData
{
	/**
	 * Adds a StateData object to the internal list to be checked for data.
	 * 
	 * @param object $data Blackbox_IStateData object
	 *
	 * @return void
	 */
	public function addStateData(Blackbox_IStateData $data);
}
?>
