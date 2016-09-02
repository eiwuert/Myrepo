<?php

/**
 * Only hit unique stats if we have not hit them yet.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
abstract class Stats_OLP_UniqueAppID extends Stats_OLP_Base implements Stats_IUnique
{
	const MEMCACHE_EXPIRE = 300; // 5 minutes
	
	/**
	 * A local copy of all stats that we have already hit that need to be unique.
	 *
	 * @var array
	 */
	protected $stat_history;
	
	/**
	 * A copy of all stats that were hit during this run through so far that
	 * got added to the uniqueness history list.
	 *
	 * @var array
	 */
	protected $new_stat_history = array();
	
	/**
	 * A list of all stats that need to be unique. This array is currently
	 * NOT used. Instead, all stats are unique unless in the
	 * $unique_stats_appid_ununique list.
	 *
	 * @var array
	 */
	protected $unique_stats_appid = array(
		'visitors',
		
		'popconfirm',
		'redirect',
		'popagree',
		'confirmed',
		'agree',
		'popty',
		'react_confirmed',
		'react_agree',
		'react_optout',
		
		// Impact agree
		'bb_ic_agree',
		
		// Customer Motivation Stat
		'__context',
		
		// Direct Mail stat
		'dm_no_market',
		
		// New Loans
		'bb_ic_new_app',
		
		// Universal redirect stat
		'uniredirect',
		
		// Hit if we hit nms_prequal, but CLK did not get a look at the app.
		'clk_no_look',
	);
	
	/**
	 * These stats are NOT unique and should be hit everytime they are called.
	 *
	 * @var array
	 */
	protected $unique_stats_appid_ununique = array(
		'leaving_application',
		'try_again',
		'reset_site_config',

		// Stats for unsigned apps and drop emails
		'unsigned_app_raw',
		'drop_link_raw',
		'drop_link_react_raw',
		'ecash_sign_doc_raw',
	
		'ent_auth_pass',
		'ent_auth_fail',
		'ent_auth_locked',
		'ent_auth_error'
	);
	
	/**
	 * Hit unique stats only if unique.
	 *
	 * @param string $event_type_key
	 * @param int $date_occurred
	 * @param int $event_amount
	 * @param string $track_key
	 * @param string $space_key
	 * @return bool
	 */
	public function hitStat($event_type_key, $date_occurred = NULL, $event_amount = NULL, $track_key = NULL, $space_key = NULL)
	{
		$hit_stat = TRUE;
		
		$is_unique = FALSE;
		
		// All stats are unique except these
		if (!$this->isNotUnique($event_type_key)) $is_unique = TRUE;
		
		// All stats are not unique unless listed here
		//if ($this->isUnique($event_type_key)) $is_unique = TRUE;
		
		// If we pass in a track key different than the one stored, don't do uniqueness tests
		if ($track_key !== NULL && $this->track_key != $track_key) $is_unique = FALSE;
		
		if ($is_unique)
		{
			if ($this->isAlreadyHit($event_type_key))
			{
				$hit_stat = FALSE;
			}
			elseif (!$this->insertUniqueStat($event_type_key))
			{
				$hit_stat = FALSE;
			}
			else
			{
				// If unique, do not hit it more than once.
				if ($event_amount > 1)
					$event_amount = 1;
				
				// Record as a new unique stat hit during this load
				$this->new_stat_history[] = $event_type_key;
			}
		}
		
		if ($hit_stat)
		{
			$hit_stat = parent::hitStat($event_type_key, $date_occurred, $event_amount, $track_key, $space_key);
		}
		
		return $hit_stat;
	}
	
	/** Determine if stat is unique
	 *
	 * @param string $stat_name
	 * @return bool
	 */
	protected function isUnique($stat_name)
	{
		return (in_array($stat_name, $this->unique_stats_appid) || $this->isUniqueCLK($stat_name));
	}

	/**
	 * Checks if the stat is a unique *_agree or bb_*_confirm for CLK
	 *
	 * @param string $stat
	 * @return bool
	 */
	protected function isUniqueCLK($stat_name)
	{
		// First check the end of the stat string to make sure it is _confirm or _agree
		if (!substr_compare($stat_name, '_agree', strrpos($stat_name,'_'))
			|| $confirm = !substr_compare($stat_name, '_confirm', strrpos($stat_name,'_')))
		{
			// Get rid of bb_ at the start if it is a confirm stat
			if ($confirm)
			{
				$stat_name = substr($stat_name, 3);
			}
	
			// The property short should be anything left between the start and the _agree/_confirm
			$property_short = substr($stat_name, 0, strrpos($stat_name,'_'));
			if (EnterpriseData::isCompanyProperty(EnterpriseData::COMPANY_CLK, $property_short))
			{
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Determine if stat is in the non-uniqueness list.
	 *
	 * @param string $stat_name
	 * @return bool
	 */
	protected function isNotUnique($stat_name)
	{
		return in_array($stat_name, $this->unique_stats_appid_ununique);
	}
	
	/**
	 * Determines if this stat has been hit before.
	 *
	 * @param string $stat_name
	 * @return bool
	 */
	protected function isAlreadyHit($stat_name)
	{
		$this->loadUniqueStatHistory();

		if ($this->isUniqueStatHit($stat_name))
		{
			$already_hit = TRUE;
		}
		else
		{
			$already_hit = FALSE;
		}

		return $already_hit;
	}
	
	/**
	 * Create a hash to be stored for our memcache key.
	 *
	 * @return string
	 */
	protected function hashMemcache()
	{
		return self::generateHash($this->getMode(), $this->getTrackKey(), $this->getApplicationID());
	}
	
	/**
	 * Generates a hash key for storing stats
	 * 
	 * @param string $mode
	 * @param string $track
	 * @param int $application_id
	 * @return string
	 */
	public static function generateHash($mode, $track, $application_id)
	{
		return md5($mode . ':' . $track . ':' . $application_id);
	}
	
	/**
	 * Load the unique stats list for this track key. Attempts to grab
	 * from memcache first, database second.
	 *
	 * @return void
	 */
	protected function loadUniqueStatHistory()
	{
		if (!$this->stat_history && $this->getApplicationID())
		{
			$this->stat_history = $this->findUniqueStatHistoryByTrackApp(
				$this->getTrackKey(),
				$this->getApplicationID()
			);
		}
	}
	
	/**
	 * Load the unique stats list for this track key. Attempts to grab
	 * from memcache first, database second.
	 *
	 * @param string $track_key
	 * @param int $application_id
	 * @return void
	 */
	public function findUniqueStatHistoryByTrackApp($track_key, $application_id)
	{
		$memcache_key = self::generateHash($this->getMode(), $track_key, $application_id);
		$memcache = Cache_Memcache::getInstance();
		
		$stat_history = $memcache->get($memcache_key);

		if (!is_array($stat_history))
		{
			$stat_history = array();
			
			$stat_loadby_array = array(
				'track_key' => $track_key,
				'application_id' => $application_id,
			);
			
			$stat_list = $this->olp_factory->getReferencedModel('StatUnique')->loadAllBy($stat_loadby_array);
			if ($stat_list)
			{
				foreach ($stat_list AS $stat_model)
				{
					$stat_history[$stat_model->stat_name] = $this->formatStatDate($stat_model->date_created);
				}
			}
			
			$memcache->set($memcache_key, $stat_history, self::MEMCACHE_EXPIRE);
		}
		
		return $stat_history;
	}
	
	/**
	 * Records this stat hit, and returns if it is unique or not.
	 * This uniqueness test should be the last test.
	 *
	 * @param string $stat_name
	 * @return bool
	 */
	protected function insertUniqueStat($stat_name)
	{
		$unique = TRUE;
		
		if ($this->getApplicationID())
		{
			$this->loadUniqueStatHistory();
			
			$stat_model = $this->olp_factory->getReferencedModel('StatUnique');
			$stat_model->track_key = $this->getTrackKey();
			$stat_model->application_id = $this->getApplicationID();
			$stat_model->stat_name = $stat_name;
			
			$unique = (bool)$stat_model->save();
			
			if ($unique)
			{
				$this->recordUniqueStatHit($stat_name);
				
				Cache_Memcache::getInstance()->set($this->hashMemcache(), $this->stat_history, self::MEMCACHE_EXPIRE);
			}
		}
		
		return $unique;
	}
	
	/**
	 * Adds a stat to the unique history list.
	 *
	 * @param string $stat_name
	 * @param string $date_created
	 * @return void
	 */
	protected function recordUniqueStatHit($stat_name, $date_created = NULL)
	{
		$this->stat_history[$stat_name] = $this->formatStatDate($date_created);
	}
	
	protected function formatStatDate($date = NULL)
	{
		if (is_int($date))
		{
			$date = date(self::DATE_FORMAT, $date);
		}
		elseif (strtotime($date) === FALSE)
		{
			$date = date(self::DATE_FORMAT);
		}
		
		return $date;
	}
	
	/**
	 * Returns the date occurred for a specific stat
	 *
	 * @param string $stat_name
	 * @return string Date usable in strtotime()
	 */
	public function getDateOccurred($stat_name)
	{
		$this->loadUniqueStatHistory();
		return (!empty($this->stat_history[$stat_name])) ? $this->stat_history[$stat_name] : NULL;
	}
	
	/**
	 * Determines if the stat has been hit before.
	 *
	 * @param string $stat_name
	 * @return bool
	 */
	protected function isUniqueStatHit($stat_name)
	{
		return isset($this->stat_history[$stat_name]);
	}
	
	/**
	 * Returns an array of unique stats already hit.
	 *
	 * @return array
	 */
	public function getUniqueStatHistory()
	{
		$this->loadUniqueStatHistory();
		
		if (is_array($this->stat_history))
		{
			$stats_hit = array_keys($this->stat_history);
		}
		else
		{
			$stats_hit = array();
		}
		
		return $stats_hit;
	}
	
	/**
	 * Determine if this specific stat has been hit yet.
	 *
	 * @param string $stat_name
	 * @return bool
	 */
	public function wasStatHitYet($stat_name)
	{
		return $this->isAlreadyHit(strtolower($stat_name));
	}
	
	/**
	 * Returns an array of unique stats hit during this load so far.
	 *
	 * @return array
	 */
	public function getNewUniqueStatHistory()
	{
		return $this->new_stat_history;
	}
}

?>
