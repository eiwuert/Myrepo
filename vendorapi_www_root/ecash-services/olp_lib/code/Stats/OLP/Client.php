<?php

/** Central section for hitting stats in OLP. This is the main client to
 * initialize. hitStat() goes down the chain to the base class and does fancy
 * OLP related actions, such as uniqueness of stats.
 *
 * The reason the functions that exist in here is because they deal with the
 * session. When setting up the track key, if there is one in the session,
 * this class will force to use that one over the one passed in.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Stats_OLP_Client extends Stats_OLP_Mapper
{
	/**
	 * @var Stats_OLP_Client
	 */
	static protected $instance;
	
	/** Initialize statpro instance;
	 *
	 * @param string $mode The mode statpro is running under.
	 * @param array $property_data Stat client's properties.
	 * @param OLP_Factory $olp_factory
	 * @param int $application_id
	 */
	protected function __construct($mode, array $property_data, OLP_Factory $olp_factory, $application_id = NULL)
	{
		if (!isset($_SESSION['stats'])) $_SESSION['stats'] = array();
		
		parent::__construct($mode, $property_data, $olp_factory, $application_id);
	}
	
	/** Grab an instance of a statpro client.
	 *
	 * @return Stats_OLP_Client
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			throw new Exception('Cannot get an instance of ' . __CLASS__ . ' without an instance of it being initialized');
		}
		
		return self::$instance;
	}
	
	/** Sets up the OLP statpro client.
	 *
	 * @param string $mode The mode statpro is running under
	 * @param array $property_data Stat client's properties
	 * @param OLP_Factory $olp_factory
	 * @param int $application_id
	 * @return Stats_OLP_Client
	 */
	public static function setupInstance($mode, $property_data, OLP_Factory $olp_factory, $application_id = NULL, $force = FALSE)
	{
		if (!isset(self::$instance) || $force)
		{
			self::$instance = new self($mode, $property_data, $olp_factory, $application_id);
		}
		else
		{
			throw new Exception('Cannot reinitialize ' . __CLASS__ . '.');
		}
		
		return self::$instance;
	}
	
	/** Hit an OLP stat. What makes a stat an OLP stat? It has to go through
	 * the chain of actions to verify if the stat should be hit, or any other
	 * actions should take place because of this stat being hit.
	 * 
	 * To see the actions that will take place, look at the chain of extends
	 * on this class down to its base class, Stats_OLP_Base.
	 *
	 * $event_type_key supports old OLP formats for stats. Specifically, it
	 * can be called as an array of stats, or it can be called as a comma
	 * delimited string of stat names.
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
		$stat_hit = FALSE;
		
		if (!is_array($_SESSION['stats']['stat_log'])) $_SESSION['stats']['stat_log'] = array();
		
		// Handle old formats of stats to be hit
		if (!is_array($event_type_key))
		{
			$event_type_key = explode(',', $event_type_key);
		}
		$event_type_key = array_map('trim', $event_type_key);
		$event_type_key = array_map('strtolower', $event_type_key);
		
		// Hit each stat
		foreach ($event_type_key AS $stat_name)
		{
			if ($stat_name && parent::hitStat($stat_name, $date_occurred, $event_amount, $track_key, $space_key))
			{
				$stat_hit = TRUE;
				$_SESSION['stats']['stat_log'][] = $stat_name;
			}
		}
		
		return $stat_hit;
	}
	
	/** Store or load track key from session.
	 *
	 * @param string $track_key
	 * @return string
	 */
	public function setupTrackKey($track_key = NULL)
	{
		// If we have one in session, use it.
		if ($_SESSION['stats']['track_key'])
		{
			$new_track_key = $this->switchTrackKey($_SESSION['stats']['track_key']);
			
			if ($track_key && $track_key != $new_track_key)
			{
				throw new Exception("Attempting to set a track key that isn't the same as session: {$track_key} != {$_SESSION['stats']['track_key']}");
			}
		}
		else
		{
			$new_track_key = $this->switchTrackKey($track_key);
		}
		
		return $new_track_key;
	}
	
	/** Switches track key. Use this lightly.
	 *
	 * @param string $track_key
	 * @return string
	 */
	public function switchTrackKey($track_key)
	{
		$track_key = parent::setupTrackKey($track_key);
		
		$_SESSION['stats']['track_key'] = $track_key;
		
		$this->logNewTrackKey();
		
		return $track_key;
	}
	
	/** Sets up the space key for OLP. If we already have this space key
	 * cached, don't recreate it to lessen the load on the stats servers.
	 *
	 * @param int $page_id
	 * @param int $promo_id
	 * @param string $promo_sub_code
	 * @return void
	 */
	public function setupSpaceKey($page_id, $promo_id, $promo_sub_code)
	{
		$space_key_def = array(
			'page_id' => $page_id,
			'promo_id' => $promo_id,
			'promo_sub_code' => $promo_sub_code,
		);
		
		// If space key def is the same, continue using space key
		if ($_SESSION['stats']['space_key_def'] == $space_key_def)
		{
			$this->statpro->setSpaceKey($_SESSION['stats']['space_key']);
		}
		else
		{
			$_SESSION['stats']['space_key'] = $this->statpro->createSpaceKey($space_key_def);
			$_SESSION['stats']['space_key_def'] = $space_key_def;
		}
	}
	
	/** Log track key switching/creation into session for debugging purposes
	 * for now. This should be removed at some point, or stored elsewhere.
	 *
	 * @return void
	 */
	protected function logNewTrackKey()
	{
		// Log track key history to session
		if (!is_array($_SESSION['stats']['track_key_history'])) $_SESSION['stats']['track_key_history'] = array();
		
		$old_history = end($_SESSION['stats']['track_key_history']);
		$track_key = $this->getTrackKey();
		
		if ($old_history['track_key'] !== $track_key)
		{
			$_SESSION['stats']['track_key_history'][] = array(
				'date' => date(self::DATE_FORMAT),
				'track_key' => $track_key,
			);
		}
	}
}

?>
