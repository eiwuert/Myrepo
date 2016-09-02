<?php

/**
 * The enterprise target adds customer history to the state data
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class OLPBlackbox_Enterprise_Target extends OLPBlackbox_Target
{
	/**
	 * Returns the winner object.
	 *
	 * @param Blackbox_Data $data
	 * @return Blackbox_IWinner
	 */
	public function getWinner(Blackbox_Data $data)
	{
		// New stat name 'ecash_look_' to track leads attempted to post to eCash blackbox campaigns/customers
		// @see [#12329] BBx- eCash Post [DY]
		if (OLPBlackbox_Config::getInstance()->blackbox_mode == OLPBlackbox_Config::MODE_BROKER)
		{
			$stat_name = 'lead_sent_' . strtolower($this->state_data->campaign_name); //@see method OLP::Post_To_Winner()
			OLPBlackbox_Config::getInstance()->hitSiteStat($stat_name, $this->state_data);
		}
		
		return parent::getWinner($data);
	}

	/**
	 * Setup the state for this Target object.
	 *
	 * @param Blackbox_IStateData $state_data StateData to add to the state
	 * @return void
	 */
	protected function initState(Blackbox_IStateData $state_data = NULL)
	{
		$this->state_data = new OLPBlackbox_Enterprise_TargetStateData($this->getInitialStateData());

		if (!is_null($state_data))
		{
			$this->state_data->addStateData($state_data);
		}
	}
	
	/**
	 * @return array Dictionary of seed key/values for the state data.
	 */
	protected function getInitialStateData()
	{
		$data = parent::getInitialStateData();
		$data['loan_actions']  = new OLPBlackbox_Enterprise_LoanActions();

		return $data;
	}
}
?>
