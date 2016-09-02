<?php

/** Hits stats. A simple wrapper to allow you to open multiple connects for
 * statpro without duplicating your connections nor passing the object around.
 *
 * Queued stats will not be committed until you request them to be processed.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Stats_StatPro
{
	const MODE_LIVE = 'live';
	const MODE_TEST = 'test';
	
	protected static $clients = array(); /**< @var array of Stats_StatPro */
	
	protected $statpro; /**< @var Stats_StatPro_Client_1 */
	protected $statpro_user; /**< @var string */
	protected $statpro_pass; /**< @var string */
	protected $statpro_key; /**< @var string */
	
	protected $track_key = ''; /**< @var string */
	protected $space_key = ''; /**< @var string */
	
	protected $batching = FALSE; /**< @var bool */
	
	protected $cache_space_keys = array(); /**< @var array */
	
	/** Initialize libolution statpro client.
	 *
	 * @param string $mode The mode statpro is running under.
	 * @param int $property_id Account stats will hit to.
	 */
	protected function __construct($mode, $property_id)
	{
		$this->mode = self::getInternalMode($mode);
		$this->statpro_key = self::getKey($mode, $property_id);
		
		$property_data = self::getPropertyData($property_id);
		$this->statpro_user = $property_data['statpro_user'];
		$this->statpro_pass = $property_data['statpro_pass'];
		
		$this->statpro = new Stats_StatPro_Client_1($this->statpro_key, $this->statpro_user, $this->statpro_pass);
	}
	
	/** Grab an instance of a statpro client. There should only be one
	 * statpro client per database connection.
	 *
	 * @param string $mode The mode statpro is running under.
	 * @param int $property_id Account stats will hit to.
	 * @return Stats_StatPro_OLP
	 */
	public static function getInstance($mode, $property_id = NULL)
	{
		$hash = self::getKey($mode, $property_id);
		
		if (!isset(self::$clients[$hash]))
		{
			self::$clients[$hash] = new self($mode, $property_id);
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
			$stats_processed += $client->flushBatch();
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
	 * @return string The space key.
	 */
	public function createSpaceKey(array $space_definition, $save = TRUE)
	{
		$hash = md5(serialize($space_definition));
		
		if (!isset($this->cache_space_keys[$hash]))
		{
			$this->cache_space_keys[$hash] = $this->statpro->getSpaceKey($space_definition);
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
	
	/** Sets or retrieves the space key for this instance.
	 *
	 * @param string $space_key If a string, sets as space key.
	 * @return string The current space key.
	 */
	public function setSpaceKey($space_key = NULL)
	{
		if ($space_key && is_string($space_key)) $this->space_key = $space_key;
		
		return $space_key;
	}
	
	/** Sets or retrieves the track key for this instance.
	 *
	 * @param string $track_key If a string, sets as track key.
	 * @return string The current track key.
	 */
	public function setTrackKey($track_key = NULL)
	{
		if ($track_key && is_string($space_key)) $this->track_key = $track_key;
		
		return $track_key;
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
	
	/** Retrieves the username and password for a specific property id.
	 *
	 * @param int $property_id Account to load.
	 * @return array Contains statpro_user and statpro_pass.
	 */
	protected static function getPropertyData($property_id)
	{
		$data = NULL;
		
		switch ($property_id)
		{
			case 9278:
				$data = array(
					'statpro_user' => 'equityone',
					'statpro_pass' => '3337b7d5b3321b075c8582540',
				);
				break;
			
			case 37676:
				$data = array(
					'statpro_user' => 'emv',
					'statpro_pass' => 'a51a5c87c5f2c030de8dee2da',
				);
				break;
			
			case 28400:
				$data = array(
					'statpro_user' => 'leadgen',
					'statpro_pass' => '04b650f6350a863089a015164',
				);
				break;
			
			case 4967:
				$data = array(
					'statpro_user' => 'ge',
					'statpro_pass' => '3818ca3aab5960549fb32d4c5',
				);
				break;
			
			case 35459:
				$data = array(
					'statpro_user' => 'pwsites',
					'statpro_pass' => 'bfa657d3633',
				);
				break;
			
			case 1571:
			case 44024:
				$data = array(
					'statpro_user' => 'cubis',
					'statpro_pass' => 'FtT7CYMFMyrC0',
				);
				break;
			
			case 48204:
			case 48206:
				$data = array(
					'statpro_user' => 'imp',
					'statpro_pass' => 'h0l3iny0urp4nts',
				);
				break;
			
			case 57458:
				$data = array(
					'statpro_user' => 'ocp',
					'statpro_pass' => 'raic9Cei',
				);
				break;
			
			case 64656:
				// GForge #9888 - Added new Enterprise Client LCS [AE]
				$data = array(
					'statpro_user' => 'lcs',
					'statpro_pass' => 'F7eu5Kr1',
				);
				break;
			
			case 31631:
			case 3018:
			case 9751:
			case 1583:
			case 1581:
			case 1579:
			case 1720:
			case 17208:
			case 10985:
				$data = array(
					'statpro_user' => 'clk',
					'statpro_pass' => 'dfbb7d578d6ca1c136304c845',
				);
				break;
			
			case -889275714:
				$data = array(
					'statpro_user' => 'bbrule',
					'statpro_pass' => 'greybox',
				);
				break;
			
			default:
				$data = array(
					'statpro_user' => 'catch',
					'statpro_pass' => 'bd27d44eb515d550d43150b9b',
				);
				break;
		}
		
		return $data;
	}
	
	/** Builds the key for a specific account/mode.
	 *
	 * @param string $mode Internal mode.
	 * @param int $property_id Which account to use.
	 * @return string Stat key.
	 */
	protected static function getKey($mode, $property_id)
	{
		$property_data = self::getPropertyData($property_id);
		
		$key = 'spc_' . $property_data['statpro_user'] . '_' . self::getInternalMode($mode);
		
		return $key;
	}
}

?>
