<?php

/**
 * Class to perform groups of tasks for hitting certain stats.
 *
 * @author Bryan Geraghty <bryan.geraghty@sellingsource.com>
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Stats_Helper
{
	/**
	 * @var Stats_IClient
	 */
	protected $stats_client;
	
	/**
	 * Create an instance of the helper using this stat client.
	 *
	 * @param Stats_IClient $stats_client
	 */
	public function __construct(Stats_IClient $stats_client)
	{
		$this->stats_client = $stats_client;
	}
	
	/**
	 * Hits the confirmed, confirmed_{campaign}, {target}_confirmed, and
	 * react_confirmed stats as specified.
	 *
	 * @param string $campaign_name
	 * @param bool $is_react
	 * @return void
	 */
	public function hitConfirmedStats($campaign_name, $is_react)
	{
		$target_name = EnterpriseData::resolveAlias($campaign_name);
		
		$this->stats_client->hitStat('confirmed');
		$this->stats_client->hitStat("confirmed_{$campaign_name}");
		$this->stats_client->hitStat("{$target_name}_confirm");
		
		if ($is_react)
		{
			$this->stats_client->hitStat('react_confirmed');
		}
	}
	
	/**
	 * Hits the agree/accepted, agree_{campaign}, bb_{target}_agree, and
	 * react_agree stats as specified.
	 *
	 * @param string $campaign_name
	 * @param bool $is_react
	 * @return void
	 */
	public function hitAgreeStats($campaign_name, $is_react)
	{
		$target_name = EnterpriseData::resolveAlias($campaign_name);
		
		$this->stats_client->hitStat('agree');
		$this->stats_client->hitStat("agree_{$campaign_name}");
		$this->stats_client->hitStat("bb_{$target_name}_agree");
		
		if ($is_react)
		{
			$this->stats_client->hitStat('react_agree');
		}
	}
	
	/**
	 * Hits the vendor_post_timeout, vendor_post_timeout_campaign_{campaign},
	 * and vendor_post_timeout_target_{target} stats.
	 *
	 * @param string $campaign_name
	 * @return void
	 */
	public function hitVendorPostTimeoutStats($campaign_name)
	{
		$target_name = EnterpriseData::resolveAlias($campaign_name);
		
		$this->stats_client->hitStat('vendor_post_timeout');
		$this->stats_client->hitStat("vendor_post_timeout_campaign_{$campaign_name}");
		$this->stats_client->hitStat("vendor_post_timeout_target_{$target_name}");
	}
}

?>
