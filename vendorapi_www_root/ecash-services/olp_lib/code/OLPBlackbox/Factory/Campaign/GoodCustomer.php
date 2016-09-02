<?php
/**
 * Factory to create OLPBlackbox_Factory_Campaign_GoodCustomer objects.
 *
 * @see [#20657] GRV SuperTier React Good Customer Program [DY]
 * @author Demin Yin <Demin Yin@SellingSource.com>
 * @package Blackbox
 * @subpackage Blackbox_Factory
 */
class OLPBlackbox_Factory_Campaign_GoodCustomer extends OLPBlackbox_Factory_Campaign
{
	/**
	 * List of good customer campaigns
	 *
	 * @var array
	 */
	protected static $gc_campaigns = array(
		'GRV_GC',
		'WP_GC',
		'CS_GC',
		'NTL_GC'
	);
	
	/**
	 * Return a OLPBlackbox Campaign built from a Blackbox_Models_Target.
	 *
	 * @param Blackbox_Models_Target $target_model The target to build with.
	 * @param int $weight The weight of the campaign for the collection it's destined for.
	 * @param OLPBlackbox_Factory_TargetStateData $state_data_factory Used to 
	 * produce state data key/values for the campaign.
	 * @return OLPBlackbox_Campaign
	 */
	public function getCampaign(
		Blackbox_Models_View_TargetCollectionChild $target_model,
		OLPBlackbox_Factory_TargetStateData $state_data_factory)
	{
		$campaign = parent::getCampaign($target_model, $state_data_factory);

		if (($campaign->getRules() instanceof OLPBlackbox_RuleCollection)
			&& !$this->getDebug()->debugSkipRule(OLPBlackbox_DebugConf::RULES))
		{
			$campaign->getRules()->addRule($this->getClientVerificationRule($target_model));
		}

		return $campaign;
	}

	/**
	 * Gets the special ClientVerification rule for good customer campaigns
	 * @param Blackbox_Models_IReadableTarget $target_model The model to build
	 * the rule for.
	 * @return OLPBlackbox_Rule_LenderPost_ClientVerification
	 */
	protected function getClientVerificationRule(Blackbox_Models_IReadableTarget $target_model)
	{
		$rule = $this->getRuleFactory($target_model->property_short)
			->createLenderPostRule(LenderAPI_Generic_Client::POST_TYPE_VERIFY);
		$rule->setEventName('CLIENT_VERIFICATION_'.strtoupper($target_model->property_short));

		return $rule;
	}
	
	public static function isGoodCustomer($property_short)
	{
		return in_array(strtoupper($property_short), self::$gc_campaigns);
	}
}
