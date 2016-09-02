<?php

/**
 * Returns whether or not this lead has been sent to a campaign or not.
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 */
class OLPBlackbox_Rule_LeadSentToCampaign extends OLPBlackbox_Rule_MultiCampaignRecur
{
	/**
	 * Runs the parent recur rule, and returns the opposite.. Since thats what
	 * we're shooting for.
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return <type>
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return !(parent::runRule($data, $state_data));
	}
}