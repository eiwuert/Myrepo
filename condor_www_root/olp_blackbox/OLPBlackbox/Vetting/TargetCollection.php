<?php

/**
 * Collection for use with vetting process described in gforge issue 9922.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Vetting_TargetCollection extends OLPBlackbox_TargetCollection
{
	/**
	 * Hit stats when the rules for this collection pass (are valid).
	 *
	 * @param Blackbox_Data $data Information about the application.
	 * @param Blackbox_IStateData $state_data Info about the calling ITarget
	 * 
	 * @return void
	 */
	protected function onRulesValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->hitStat(
			OLPBlackbox_Config::STAT_VETTING_DATA_QUALITY_PASS, 
			$state_data
		);
	}
	
	/**
	 * Hit stats when the rules for this collection fails.
	 *
	 * @param Blackbox_Data $data Information about the application.
	 * @param Blackbox_IStateData $state_data Info about the calling ITarget.
	 * 
	 * @return void
	 */
	protected function onRulesInvalid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->hitStat(
			OLPBlackbox_Config::STAT_VETTING_DATA_QUALITY_FAIL, 
			$state_data
		);
	}
	
	/**
	 * If there are no valid targets left, we must hit a stat for gforge 9922.
	 *
	 * @param Blackbox_Data $data info about the application being run
	 * @param Blackbox_IStateData $state_data info about the calling ITarget
	 * @return void
	 */
	protected function onTargetsRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (empty($this->valid_list))
		{
			$this->hitStat(
				OLPBlackbox_Config::STAT_VETTING_LEAD_FAILED, 
				$state_data
			);
		}
	}
}

?>
