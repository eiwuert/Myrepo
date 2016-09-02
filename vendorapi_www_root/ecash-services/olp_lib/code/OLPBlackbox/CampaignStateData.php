<?php
/**
 * Class definition for OLPBlackbox_CampaignStateData.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */

/**
 * Class for holding state information for OLPBlackbox_Campaign classes.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_CampaignStateData extends Blackbox_StateData
{
	/**
	 * Constructs a OLPBlackbox_CampaignStateData object, mostly concerned with setting allowed_keys.
	 *
	 * Note: Data set in constructor ignores mutable/immutable status.
	 *
	 * @param array $data assoc array of data to initialize the state object with.
	 *
	 * @return void
	 */
	function __construct($data = NULL)
	{
		/* initialize allowed_keys for things that make sense for OLPBlackbox_Campaigns
		 * using immutable_keys or mutable_keys depending if rules should be able to change them.
		 */
		$this->immutable_keys[] = 'campaign_name';
		$this->immutable_keys[] = 'campaign_id';
		$this->mutable_keys[] = 'current_leads';

		// For the CashNet/CLK preferred tier, we need to identify these
		// leads so that when they're accepted, we can hit special stats.
		$this->mutable_keys[] = 'preferred_lead';

		// For storing the frequency score of when the lead was attempted
		$this->mutable_keys[] = 'frequency_score';

		// TODO: This needs to be moved. Entirely.
		// list_mgmt_nosell has NOTHING to do with blackbox or picking targets
		// get it out of here when possible!
		$this->mutable_keys[] = 'list_mgmt_nosell';

		/* {@see OLPBlackbox_Rule_LenderPost} */
		$this->mutable_keys[] = 'lender_post_result';

		// This could really be immutable, but the campaign
		// currently doesn't have a way to pass state data in,
		// so we have the modify it after its been created
		$this->mutable_keys[] = 'price_point';
		
		// if a target passes the brick and mortar rule, this will contain data about the winning store
		$this->mutable_keys[] = 'brick_and_mortar_store';

		// configuration option to tell rules to log passes or not
		$this->mutable_keys[] = 'eventlog_show_rule_passes';
		
		// Different from price_point in that this value comes from
		// the olp_blackbox database instead of from CPanel 
		$this->mutable_keys[] = 'lead_cost';

		parent::__construct($data);
	}
}
?>
