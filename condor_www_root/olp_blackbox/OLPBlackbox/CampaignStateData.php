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
		$this->mutable_keys[] = 'partner_weekly_vetting_lead';

		// spec for gforge issue 9922 demands stat hit (vetting_react_sold)
		// when reacts are sold, but we can't determine that from in blackbox
		$this->mutable_keys[] = 'is_vetting_react';
		
		parent::__construct($data);
	}
}
?>
