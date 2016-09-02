<?php

/** Hits stats. A simple wrapper to allow you to open multiple connects for
 * statpro without duplicating your connections nor passing the object around.
 *
 * Queued stats will not be committed until you request them to be processed.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Stats_StatPro implements Stats_IClient
{
	const MODE_LIVE = 'live';
	const MODE_TEST = 'test';
	
	/**
	 * @var array of Stats_StatPro
	 */
	protected static $clients = array();
	
	/**
	 * @var Stats_StatPro_Client_1
	 */
	protected $statpro;
	
	/**
	 * @var string
	 */
	protected $statpro_user;
	
	/**
	 * @var string
	 */
	protected $statpro_pass;
	
	/**
	 * @var string
	 */
	protected $statpro_key;
	
	/**
	 * @var string
	 */
	protected $track_key = '';
	
	/**
	 * @var string
	 */
	protected $space_key = '';
	
	/**
	 * @var bool
	 */
	protected $batching = FALSE;
	
	/**
	 * @var array
	 */
	protected $cache_space_keys = array();
	
	/** Initialize libolution statpro client.
	 *
	 * @param string $mode The mode statpro is running under.
	 * @param array $property_data Stat client's properties.
	 */
	protected function __construct($mode, array $property_data)
	{
		if (!isset($property_data['username']) || !isset($property_data['password']) || empty($property_data['username']))
		{
			throw new InvalidArgumentException('StatPro requires username and password inside of property data.');
		}
		
		$this->mode = self::getInternalMode($mode);
		$this->statpro_key = self::getKey($mode, $property_data);
		$this->statpro_user = $property_data['username'];
		$this->statpro_pass = $property_data['password'];
		
		$this->statpro = new Stats_StatPro_Client_1($this->statpro_key, $this->statpro_user, $this->statpro_pass);
	}
	
	/** Grab an instance of a statpro client. There should only be one
	 * statpro client per database connection.
	 *
	 * @param string $mode The mode statpro is running under.
	 * @param array $property_data Stat client's properties.
	 * @return Stats_StatPro
	 */
	public static function getInstance($mode, array $property_data)
	{
		$hash = self::getKey($mode, $property_data);
		
		if (!isset(self::$clients[$hash]))
		{
			self::$clients[$hash] = new self($mode, $property_data);
		}
		
		return self::$clients[$hash];
	}
	
	/** Processes all instance's batches.
	 *
	 * @return void
	 */
	public static function flushAllInstances()
	{
		foreach (self::$clients AS $client)
		{
			$client->flushBatch();
		}
	}
	
	/** Hits a stat. Stat is written immediately.
	 *
	 * @param string $event_type_key The stat name to be hit (all lowercase).
	 * @param int $date_occurred Date stat will be recorded under (unix timestamp).
	 * @param int $event_amount Duplicate the stat this many times.
	 * @param string $track_key The track key the stat will be registered for.
	 * @param string $space_key The space key the stat will be registered for.
	 * @return void
	 */
	public function hitStat($event_type_key, $date_occurred = NULL, $event_amount = NULL, $track_key = NULL, $space_key = NULL)
	{
		if (!$track_key) $track_key = $this->track_key;
		if (!$space_key) $space_key = $this->space_key;
		
		$this->statpro->recordEvent($track_key, $space_key, $event_type_key, $date_occurred, $event_amount);
	}
	
	/** Records a pixel URL to the journal to be hit upon scrubbing.
	 *
	 * @param string $url
	 * @return void
	 */
	public function hitURL($url)
	{
		$this->statpro->insert(
			6,
			array(
				$url,
				time(),
			)
		);
	}
	
	/** Enable batching.
	 *
	 * @return void
	 */
	public function enableBatch()
	{
		if (!$this->batching)
		{
			$this->batching = TRUE;
			
			$this->statpro->beginBatch();
		}
	}
	
	/** Ends the batch and processes all items queued up.
	 *
	 * @return void
	 */
	public function flushBatch()
	{
		if ($this->batching)
		{
			$this->batching = FALSE;
			
			$this->statpro->endBatch();
		}
	}
	
	/** Create a space key from a list of definitions. Contains a simple
	 * optimization to prevent multiple space key insertions for one page
	 * load.
	 *
	 * @param array $space_definition A list of terms used to generate a space key.
	 * @param bool $save If true, will call setSpaceKey() with this key.
	 * @param int $date_occurred The date the space key was created (for post-hitting stats).
	 * @return string The space key.
	 */
	public function createSpaceKey(array $space_definition, $save = TRUE, $date_occurred = NULL)
	{
		$hash = md5(serialize($space_definition));
		
		if (!isset($this->cache_space_keys[$hash]))
		{
			$this->cache_space_keys[$hash] = $this->statpro->getSpaceKey($space_definition, $date_occurred);
		}
		
		if ($save) $this->space_key = $this->cache_space_keys[$hash];
		
		return $this->cache_space_keys[$hash];
	}
	
	/** Create a track key.
	 *
	 * @param bool $save If true, will call setTrackKey() with this key.
	 * @return string The track key.
	 */
	public function createTrackKey($save = TRUE)
	{
		$track_key = $this->statpro->createTrackKey();
		
		if ($save) $this->track_key = $track_key;
		
		return $track_key;
	}
	
	/** Gets the space key for this instance.
	 *
	 * @return string The current space key.
	 */
	public function getSpaceKey()
	{
		return $this->space_key;
	}
	
	/** Sets the space key for this instance.
	 *
	 * @param string $space_key If a string, sets as space key.
	 * @return string The current space key.
	 */
	public function setSpaceKey($space_key = NULL)
	{
		if ($space_key && is_string($space_key)) $this->space_key = $space_key;
		
		return $this->space_key;
	}
	
	/** Gets the track key for this instance.
	 *
	 * @return string The current track key.
	 */
	public function getTrackKey()
	{
		return $this->track_key;
	}
	
	/** Sets the track key for this instance.
	 *
	 * @param string $track_key If a string, sets as track key.
	 * @return string The current track key.
	 */
	public function setTrackKey($track_key = NULL)
	{
		if ($track_key && is_string($track_key))
		{
			if (preg_match('/^[a-z0-9,-]{27}$/i', $track_key))
			{
				$this->track_key = $track_key;
			}
			else
			{
				throw new InvalidArgumentException('Track key is not valid. Track key must contain only alphanumeric characters, commas, or hyphens, and be exactly 27 characters long.');
			}
		}
		
		return $this->track_key;
	}
	
	/** Retrieves the running mode.
	 *
	 * @return string The mode.
	 */
	public function getMode()
	{
		return $this->mode;
	}
	
	/** Retrieves the current statpro key we write stats to.
	 *
	 * @return string Statpro key.
	 */
	public function getStatproKey()
	{
		return $this->statpro_key;
	}
	
	/** Translates an external mode into an internal mode.
	 *
	 * @param string $mode External mode.
	 * @return string Internal mode.
	 */
	protected static function getInternalMode($mode)
	{
		switch (strtoupper($mode))
		{
			case 'LIVE':
				$mode = self::MODE_LIVE;
				break;
			
			case 'RC':
			case 'LOCAL':
			case 'DEMO':
			default:
				$mode = self::MODE_TEST;
				break;
		}
		
		return $mode;
	}
	
	/** Builds the key for a specific account/mode.
	 *
	 * @param string $mode Internal mode.
	 * @param array $property_data Stat client's properties.
	 * @return string Stat key.
	 */
	protected static function getKey($mode, array $property_data)
	{
		$key = 'spc_' . $property_data['username'] . '_' . self::getInternalMode($mode);
		
		return $key;
	}
	
	/**
	 * Make sure we create spaces for hitting stats in alternate buckets.
	 *
	 * @param string $property_short Property short of bucket to hit in
	 * @return string Space key
	 */
	public function setupBucket($property_short)
	{
		$space_def = self::getSpaceDefinition($property_short, TRUE);
		return $this->createSpaceKey($space_def, FALSE);
	}
	
	/**
	 * Returns the current space definition
	 *
	 * @param string $property_short
	 * @param bool $use_enterprise TRUE to hit the stat on the enterprise property's page_id
	 * @return array
	 */
	public static function getSpaceDefinition($property_short, $use_enterprise = FALSE)
	{
		$page_id = SiteConfig::getInstance()->page_id;
		
		if ($use_enterprise && EnterpriseData::isEnterprise($property_short))
		{
			$page_id = EnterpriseData::getEnterpriseOption($property_short, 'page_id');
		}

		return array(
			'page_id' => $page_id,
			'promo_id' => SiteConfig::getInstance()->promo_id,
			'promo_sub_code' => SiteConfig::getInstance()->promo_sub_code
		);
	}
}

?>
