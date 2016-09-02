<?php

/**
 * Class to do some potentially common interactions with OLPBlackbox stuff.
 *
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLPBlackbox_Util
{
	/**
	 * Database connection to Blackbox
	 *
	 * @var DB_IConnection_1
	 */
	protected $db;
	
	/**
	 * Memcache instance
	 *
	 * @var Cache_Memcache
	 */
	protected $memcache;
	
	/**
	 * Amount of time to keep data in memcache (seconds)
	 *
	 * @var int
	 */
	protected $memcache_expire = 600;
	
	/**
	 * Model factory
	 *
	 * @var Blackbox_ModelFactory
	 */
	protected $factory;
	
	/**
	 * Constructor
	 *
	 * @param DB_IConnection_1 $db
	 * @param Cache_Memcache $memcache
	 */
	public function __construct(DB_IConnection_1 $db, Cache_Memcache $memcache)
	{
		$this->db = $db;
		$this->memcache = $memcache;
	}
	
	/**
	 * Gets a common memcache key name for blackbox utils.
	 * 
	 * @param string $name
	 * @return string
	 */
	public function getMemcacheKey($name)
	{
		return '/blackbox/util/' . $name;
	}
	
	/**
	 * Given a target collection name, will check all children to determine
	 * if they are over their daily limits. Only returns TRUE if all children
	 * are over their limits.
	 *
	 * @param string $collection_name
	 * @param Stats_Limits $stats_limits
	 * @return bool
	 */
	public function hasCollectionHitCap($collection_name, Stats_Limits $stats_limits)
	{
		$memcache_key = $this->getMemcacheKey("cap/collection/{$collection_name}");
		
		$hit_cap = $this->memcache->get($memcache_key);
		if ($hit_cap === FALSE)
		{
			$children = $this->getChildren($collection_name);
			
			$hit_cap = 0;
			foreach ($children as $child)
			{
				if ($this->hasCampaignHitCap($child->property_short, $stats_limits))
				{
					$hit_cap = 1;
					break;
				}
			}
			
			$this->memcache->set($memcache_key, $hit_cap, $this->memcache_expire);
		}
		
		return ($hit_cap === 1);
	}
	
	/**
	 * Returns an array of children for this collection.
	 *
	 * @param string $collection_name
	 * @return array
	 */
	protected function getChildren($collection_name)
	{
		$children = array();

		$factory = $this->getModelFactory();
		$collection = $factory->getModel('Target');
		$collection_type_id = $factory->getReferenceTable('BlackboxType', TRUE)->toID('COLLECTION');
		
		if ($collection->loadByPropertyShort($collection_name, $collection_type_id))
		{
			$targets = $factory->getViewModel('TargetCollectionChild')
				->getCollectionTargets($collection->target_id);
			
			foreach ($targets as $target)
			{
				$children[] = $target->property_short;
			}
		}
		
		return $children;
	}
	
	/**
	 * Checks to see if this campaign has hit their daily limit yet.
	 *
	 * @param string $campaign_name
	 * @param Stats_Limits $stats_limits
	 * @return bool
	 */
	public function hasCampaignHitCap($campaign_name, Stats_Limits $stats_limits)
	{
		$memcache_key = $this->getMemcacheKey("cap/campaign/{$campaign_name}");
		
		$hit_cap = $this->memcache->get($memcache_key);
		if ($hit_cap === FALSE)
		{
			$daily_limit = $this->getDailyLimit($campaign_name);
			$stat_count = $stats_limits->count("bb_{$campaign_name}");
			
			$hit_cap = ($daily_limit > 0 && $stat_count >= $daily_limit) ? 1 : 0;
			
			$this->memcache->set($memcache_key, $hit_cap, $this->memcache_expire);
		}
		
		return ($hit_cap === 1);
	}
	
	/**
	 * Returns the daily limits for this campaign for today.
	 *
	 * @param string $campaign_name
	 * @return int
	 */
	protected function getDailyLimit($campaign_name)
	{
		$daily_limit = 0;
		$day_index = date('N', Blackbox_Utils::getToday()) - 1;
		
		$query = "SELECT rule_value
			FROM target t
			INNER JOIN blackbox_type bt ON t.blackbox_type_id = bt.blackbox_type_id
			INNER JOIN rule_revision rrev ON t.rule_id = rrev.rule_id
				AND rrev.active = 1
			INNER JOIN rule_relation rr ON t.rule_id = rr.rule_id
				AND rrev.rule_revision_id = rr.rule_revision_id
			INNER JOIN rule r ON rr.child_id = r.rule_id
			INNER JOIN rule_definition rdef ON r.rule_definition_id = rdef.rule_definition_id
				AND rdef.name_short = 'daily_limit'
			WHERE t.property_short = ?
				AND bt.name = 'CAMPAIGN'";
		
		$limit_rule = DB_Util_1::querySingleValue(
			$this->db,
			$query,
			array($campaign_name)
		);
		
		if ($limit_rule !== NULL)
		{
			$limits = @unserialize($limit_rule);
			if (is_array($limits) && isset($limits[$day_index]))
			{
				$daily_limit = $limits[$day_index];
			}
		}
		
		return $daily_limit;
	}
	
	/**
	 * Sets up the blackbox model factory.
	 *
	 * @return Blackbox_ModelFactory
	 */
	protected function getModelFactory()
	{
		if (!$this->factory instanceof Blackbox_ModelFactory)
		{
			$this->factory = new Blackbox_ModelFactory($this->db);
		}
		
		return $this->factory;
	}
}

?>
