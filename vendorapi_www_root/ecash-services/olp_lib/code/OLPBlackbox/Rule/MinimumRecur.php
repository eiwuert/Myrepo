<?php
/**
 * Abstract minimum recur rule.
 *
 * This class implements the common functionality for the minimum recur rules.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
abstract class OLPBlackbox_Rule_MinimumRecur extends OLPBlackbox_Rule implements OLPBlackbox_Factory_Legacy_IReusableRule
{
	/**
	 * The key used for memcache.
	 *
	 * @var string
	 */
	protected $cache_key;
	
	/**
	 * OLP database connection object.
	 *
	 * @var MySQL_4
	 */
	protected $olp_db;
	
	/**
	 * The name of the OLP database.
	 *
	 * @var string
	 */
	protected $olp_db_name;
	
	/**
	 * The result of the rule.
	 *
	 * @var bool|string
	 */
	protected $result;
		
	/**
	 * Run the mimimum recur rule.
	 *
	 * @param Blackbox_Data $data the data used to use
	 * @param Blackbox_IStateData $state_data state data to use
	 * @return bool
	 */
	protected function runRule(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		// Setup the db
		$this->olp_db = $this->getDbInstance();
		$this->olp_db_name = $this->getDbName();
		
		$query_date = $this->getQueryDate($this->getRuleValue());
		
		$total = $this->checkCache($data, $state_data);
		
		if ($total === FALSE || $total == 0)
		{
			$total = $this->runRecurCheck($data, $query_date, $this->getProperties($state_data));
			
			if ($total > 0)
			{
				$this->updateCache($total);
			}
		}
		
		$this->result = ($total == 0);
		
		return $this->result;
	}
	
	/**
	 * Checks to see if any of the properties are Enterprise campaigns.
	 *
	 * @param array $properties an array of property shorts
	 * @return bool
	 */
	protected function isEnterprise(array $properties)
	{
		foreach ($properties as $property)
		{
			if (EnterpriseData::isEnterprise($property)) return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Run when the rule returns as valid.
	 *
	 * @param Blackbox_Data $data the data used to validate the rule
	 * @param Blackbox_IStateData $state_data the target state data
	 * @return void
	 */
	protected function onValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$event_result = $this->result === TRUE ? OLPBlackbox_Config::EVENT_RESULT_PASS : $this->result;
		
		if ($this->isConfiguredToLogPasses($state_data))
		{
			$this->hitRuleEvent($event_result, $data, $state_data);
		}
		
		$this->triggerEvents(__FUNCTION__, $state_data);
	}
	
	/**
	 * Returns the memcache key.
	 *
	 * @return string
	 */
	public function getCacheKey()
	{
		return $this->cache_key;
	}
	
	/**
	 * Returns an array with the property and all aliases for given target.
	 *
	 * @return array
	 */
	public function getProperties($state_data)
	{
		return EnterpriseData::getPropertyAndAliases($state_data->campaign_name);
	}
	
	/**
	 * Takes a campaign name and finds all the campaign ids that belong to the same company
	 * 
	 * @param string $campaign_name
	 * @return array
	 */
	protected function getCompanyCampaignIdsByCampaign($campaign_name)
	{
		$campaigns = EnterpriseData::getCompanyProperties(EnterpriseData::getCompany($campaign_name));
		$query = "
			SELECT
				target_id
			FROM
				olp_blackbox.target t
			JOIN 
				olp_blackbox.blackbox_type bt ON t.blackbox_type_id = bt.blackbox_type_id
			WHERE
				bt.name = 'CAMPAIGN'
				AND property_short IN ('".implode("','", $campaigns)."')";
	
		$campaign_ids = array();
		$result = $this->olp_db->Query($this->olp_db_name, $query);
		while ($row = $this->olp_db->Fetch_Object_Row($result))
		{
			$campaign_ids[] = $row->target_id;
		}
		
		return $campaign_ids;
	}
	
	/**
	 * Checks memcache for a total value we've already checked today and returns that count or
	 * FALSE if it's not found.
	 *
	 * @param Blackbox_Data $data
	 * @param Blackbox_IStateData $state_data
	 * @return int
	 */
	protected function checkCache(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$key_data = $this->getDataValue($data);
		$query_date = $this->getQueryDate($this->getRuleValue())->format('Y-m-d');

		// We want to key off of a resolved alias.
		$property = EnterpriseData::resolveAlias($state_data->campaign_name);
		
		$this->cache_key = 'MinRecur:' . md5($key_data . ':' . $query_date . ':' . $property);
		
		$total = $this->getConfig()->memcache->get($this->cache_key);
		
		return (int)$total;
	}
	
	/**
	 * Updates memcache with the total result.
	 *
	 * @param int $total
	 * @return void
	 */
	protected function updateCache($total)
	{
		// We want to expire at midnight
		$time = getdate();
		$expire = mktime(0, 0, 0, $time['mon'], $time['mday'] + 1, $time['year']);
		
		$this->getConfig()->memcache->add($this->cache_key, $total, $expire);
	}
	
	/**
	 * Return the date that we'll run our recur queries on.
	 *
	 * @throws Blackbox_Exception
	 * @param int $days the number of days this recur rule checks
	 * @return DateTime
	 */
	protected function getQueryDate($days)
	{
		$date = date_create("-$days days");
		
		if ($date === FALSE)
		{
			throw new Blackbox_Exception(sprintf(
				'rule %s misconfigured, %s invalid number of days',
				get_class($this),
				var_export($days, TRUE))
			);
		}
		
		return $date;
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
		
	/**
	 * Runs the recur check.
	 *
	 * @param string $data the data the check will use
	 * @param DateTime $date the date the check will use
	 * @param array $properties the targets we're checking
	 * @return int
	 */
	abstract protected function runRecurCheck($data, DateTime $date, $properties);
}
?>
