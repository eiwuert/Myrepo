<?php

/**
 * The enterprise target adds customer history to the state data
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_CLK_Target extends OLPBlackbox_Enterprise_Target
{
	/**
	 * Setup the state for this Target object.
	 *
	 * @param Blackbox_IStateData $state_data StateData to add to the state
	 * @return void
	 */
	protected function initState(Blackbox_IStateData $state_data = NULL)
	{
		/* GForge #11055 - CLK targets should not have customer_history in
		 * their state data. OLPBlackbox_Enterprise_TargetCollectionStateData
		 * has it for us.
		 */
		
		$initial_data = array(
			'target_name' => $this->name,
			'name' => $this->name,
			'customer_history' => NULL,
		);
		$this->state_data = new OLPBlackbox_Enterprise_TargetStateData($initial_data);

		if (!is_null($state_data))
		{
			$this->state_data->addStateData($state_data);
		}
		
	}
}
?>