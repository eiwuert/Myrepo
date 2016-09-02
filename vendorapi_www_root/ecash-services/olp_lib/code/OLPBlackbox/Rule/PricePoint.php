<?php
/**
 * The price point lender post rule
 * 
 * This rule needs to be set up on the campaign, and the appropriate xsl added to the lender post
 * 
 * @author Eric Johney <eric.johney@sellingsource.com>
 */
class OLPBlackbox_Rule_PricePoint extends OLPBlackbox_Rule implements OLPBlackbox_Factory_Legacy_IReusableRule
{
	/**
	 * Event for this rule
	 *
	 * @var string
	 */
	protected $event_name = OLPBlackbox_Config::EVENT_PRICE_POINT;
	
	/**
	 * Always run the check if the rule is enabled
	 *
	 * @param Blackbox_Data $data the data the rule is running against
	 * @param Blackbox_IStateData $state_data information about the state of the Blackbox_ITarget which desires to run the rule.
	 *
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		return TRUE;
	}
	
	/**
	 * Runs the price point check
	 *
	 * @param Blackbox_Data $data the data we run the rule on
	 * @param Blackbox_IStateData $state_data state data passed to us
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$valid = FALSE;
		
		$rule_values = $this->getRuleValue();
		$offer = $this->getOffer($state_data->lender_post_persistent_data);
				
		if (!empty($rule_values['group']) && isset($offer[$rule_values['group']]))
		{
			$offered_at = (float)$offer[$rule_values['group']]['price'];
			if ($this->getCampaignPricePoint($state_data->campaign_name) <= $offered_at)
			{
				$valid = TRUE;
			}
		}
		else
		{
			$valid = ($rule_values['default'] == 1) ? TRUE : FALSE;
		}
		
		return $valid;
	}
	
	/**
	 * Returns the offer from persistent data
	 *
	 * @param mixed $persistent_data
	 * @return array
	 */
	protected function getOffer($persistent_data)
	{
		$offer = array();
		if (is_array($persistent_data) && isset($persistent_data['offer']))
		{
			$offer = $persistent_data['offer'];
		}
		
		return $offer;
	}
	
	/**
	 * Returns the price point for a campaign
	 *
	 * @param string $campaign
	 * @return float
	 */
	protected function getCampaignPricePoint($campaign)
	{
		$model = Blackbox_ModelFactory::getInstance()->getModel('Target');
		$model->loadByPropertyShort($campaign, 3);
		
		return (float)$model->lead_cost;
	}
}
?>
