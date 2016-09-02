<?php

/**
 * The enterprise target adds customer history to the state data
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Target extends OLPBlackbox_Target
{
	/**
	 * Picks a winner
	 *
	 * @param Blackbox_Data $data
	 * @return Blackbox_IWinner
	 */
	public function pickTarget(Blackbox_Data $data)
	{
		$winner = parent::pickTarget($data);
		
		// indicate whether this app is a react
		// For companies other than CLK, the previous customer checks are run during pickTarget
		// and so this needs to be after the pickTarget call.
		$this->state_data->is_react = $this->isReact($this->state_data);
		
		return $winner;
	}

	/**
	 * Indicates whether the current application is a react
	 *
	 * @param Blackbox_IStateData $state
	 * @return bool
	 */
	protected function isReact(Blackbox_IStateData $state)
	{
		return (isset($state->customer_history)
			&& $state->customer_history->getIsReact($state->target_name));
	}

	/**
	 * Setup the state for this Target object.
	 *
	 * @param Blackbox_IStateData $state_data StateData to add to the state
	 * @return void
	 */
	protected function initState(Blackbox_IStateData $state_data = NULL)
	{
		$initial_data = array(
			'target_name' => $this->name,
			'name' => $this->name,
			'customer_history' => new OLPBlackbox_Enterprise_CustomerHistory(),
			'target_id' => $this->id
		);
		$this->state_data = new OLPBlackbox_Enterprise_TargetStateData($initial_data);

		if (!is_null($state_data))
		{
			$this->state_data->addStateData($state_data);
		}
	}
}
?>
