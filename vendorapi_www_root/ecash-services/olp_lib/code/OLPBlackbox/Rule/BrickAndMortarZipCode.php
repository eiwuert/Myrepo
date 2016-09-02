<?php
/**
 * Checks the customer zip code against the list of accepted zips for this campaign
 *
 * @package OLPBlackbox
 * @author Eric Johney <eric.johney@sellingsource.com>
 */
class OLPBlackbox_Rule_BrickAndMortarZipCode extends OLPBlackbox_Rule
{
	/**
	 * stores a key to keep track of memcached cached stores 
	 *
	 * @var string
	 */
	protected $cache_key;
	
	/**
	 * Runs the brick and mortar zipcode check
	 *
	 * @param Blackbox_Data $data 
	 * @param Blackbox_IStateData $state_data
	 * @return bool
	 */
	public function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$home_zip = $this->getDataValue($data);
		
		$stores = $this->checkCache($home_zip, $state_data->campaign_name);
		if ($stores === FALSE)
		{
			$stores = $this->getLocalStores($home_zip, $state_data->campaign_name);
			$this->updateCache($stores);
		}
		
		// pick a random store out of the stores array to support multiple stores per zip
		if (is_array($stores) && !empty($stores))
		{
			$store = $stores[array_rand($stores)];
		}
		
		$this->setStoreInStateData($store, $state_data);
				
		return is_array($store);
	}
	
	/**
	 * Find all the stores that match a given zip code and campaign name
	 *
	 * @param string $home_zip
	 * @return SimpleXmlElement|FALSE
	 */
	protected function getLocalStores($home_zip, $campaign_name)
	{
		$olp_db = $this->getDbInstance();
		$query = sprintf("
			SELECT
				zip_code, property_short, dm_email, store_id, store_email, dm_name, region_name,
				address1, address2, city, state, phone1, fax, active
			FROM
				ace_stores s
			WHERE	
				s.zip_code = '%d'
				AND s.property_short = '%s'
				AND s.active = 1", 
			$home_zip, $campaign_name
		);
	
		$stores = array();
		$result = $olp_db->Query($this->getDbName(), $query);
		while ($store = $olp_db->Fetch_Array_Row($result))
		{
			$stores[] = $store;
		}
		
		return $stores;
	}
	
	/**
	 * Look in the cache first to see if we already have store data for this zip and campaign
	 * 
	 * @return int
	 */
	protected function checkCache($home_zip, $campaign_name)
	{
		$this->cache_key = 'BrickAndMortarStore:' . md5($home_zip . ':' . $campaign_name);
		
		$stores = $this->getMemcacheInstance()->get($this->cache_key);
		if (!empty($stores))
		{
			$stores = unserialize($stores);
		}
		
		return $stores;
	}
	
	/**
	 * Updates memcache with the stores
	 *
	 * @param array $store
	 * @return void
	 */
	protected function updateCache($stores)
	{
		if (is_array($stores))
		{
			// Cache this up to 24 hours
			$this->getMemcacheInstance()->add($this->cache_key, serialize($stores), 86400);
		}
	}
	
	/**
	 * If the store is an array, save it to a state data key to be used by the "post"
	 *
	 * @param array|FALSE $store
	 * @param Blackbox_IStateData $state_data
	 * @return void
	 */
	protected function setStoreInStateData($store, Blackbox_IStateData $state_data)
	{
		if (is_array($store))
		{
			$state_data->brick_and_mortar_store = $store;
		}
	}
	
	/**
	 * Setup the memcache connection for this rule.
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
