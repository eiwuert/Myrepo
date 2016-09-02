<?php
/**
 * Automatic blackbox campaign shutoff
 *
 * @author Eric Johney <eric.johney@sellingsource.com>
 */
class OLPBlackbox_Rule_CampaignShutoff extends OLPBlackbox_Rule
{
	/**
	 * The key used for memcache.
	 *
	 * @var string
	 */
	protected $cache_key;
	
	/**
	 * We have to have a non-enterprise campaign name 
	 *
	 * @param Blackbox_Data $data the data to do validation against
	 * @param Blackbox_IStateData $state_data the state data to do validation against
	 * @return bool
	 */
	protected function canRun(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if (!empty($state_data->campaign_name))
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Checks to see if this campaign is currently marked as disabled in the campaign_shutoff table
	 *
	 * @param Blackbox_Data $data the data used to use
	 * @param Blackbox_IStateData $state_data state data to use
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$is_campaign_shutoff = $this->checkCache($state_data->campaign_name);
		if (is_null($is_campaign_shutoff))
		{
			$is_campaign_shutoff = $this->checkDb($state_data->campaign_name);
			$this->updateCache($is_campaign_shutoff);
		}
				
		return !$is_campaign_shutoff;
	}
	
	/**
	 * checks memcache to see if the shutoff count is currently stored for this campaign
	 *
	 * @param string $campaign_name
	 * @return bool|NULL
	 */
	protected function checkCache($campaign_name)
	{
		$this->cache_key = 'CampaignShutoff:' . md5($campaign_name);
		
		$result = $this->getMemcacheInstance()->get($this->cache_key);
		if ($result !== FALSE)
		{
			return unserialize($result);
		}
		return NULL;
	}
	
	/**
	 * Updates memcache with the shutoff value
	 *
	 * @param bool $value
	 * @return void
	 */
	protected function updateCache($value)
	{
		if (is_bool($value))
		{
			$this->getMemcacheInstance()->set($this->cache_key, serialize($value), 900);
		}
	}
	
	/**
	 * get current mysql datetime, this method only exists for mocking/tests
	 *
	 * @return string
	 */
	protected function getQueryDate()
	{
		return date('Y-m-d H:i:s');
	}
	
	/**
	 * generate the query to be used below in the db check
	 *
	 * @param string $campaign_name
	 * @return string
	 */
	protected function getQueryForDbCheck($campaign_name)
	{
		$current_time = $this->getQueryDate();
		return sprintf("
			SELECT count(*) AS shutoff_count
			FROM campaign_shutoff 
			WHERE property_short = '%s'
			AND shutoff_at <= '%s'
			AND (activated_at > '%s' OR activated_at IS NULL)",
			$campaign_name,
			$current_time,
			$current_time
		);
	}
	
	/**
	 * Checks to see if this campaign is currently marked as disabled in the campaign_shutoff table
	 *
	 * @param string $campaign_name
	 * @return bool|NULL
	 */
	protected function checkDb($campaign_name)
	{		
		try
		{
			$olp_db = $this->getDbInstance();
			$result = $olp_db->Query($this->getDbName(), $this->getQueryForDbCheck($campaign_name));
			if (($row = $olp_db->Fetch_Object_Row($result)))
			{
				return (bool)$row->shutoff_count;
			}
		}
		catch (Exception $e)
		{
			$this->getConfig()->applog->Write(
				sprintf("%s:%s - Campaign shutoff query failed", __CLASS__, __METHOD__)
			);
		}
		return NULL;
	}
	
	/**
	 * returns the memcache instance to be used in this rule
	 *
	 * @return void
	 */
	protected function getMemcacheInstance()
	{
		return $this->getConfig()->memcache;
	}
	
	/**
	 * Setups the database connection for this rule.
	 *
	 * @return void
	 */
	protected function getDbInstance()
	{
		return $this->getConfig()->olp_db;
	}
	
	/**
	 * Returns the database name.
	 *
	 * @return string
	 */
	protected function getDbName()
	{
		return $this->getConfig()->olp_db->db_info['db'];
	}
}
?>
