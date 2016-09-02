<?php

/**
 * Base class for OLP stats hitting, the simple wrapper around Stats_StatPro
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
abstract class Stats_OLP_Base implements Stats_IClient
{
	const DATE_FORMAT = 'Y-m-d H:i:s';
	
	/**
	 * @var Stats_StatPro
	 */
	protected $statpro;
	
	/**
	 * @var int
	 */
	protected $application_id;
	
	/**
	 * @var string
	 */
	protected $mode;
	
	/**
	 * Instance of the OLP_Factory
	 *
	 * @var OLP_Factory
	 */
	protected $olp_factory;
	
	/**
	 * @var array
	 */
	protected $observers = array();
	
	/**
	 * Initialize statpro instance;
	 *
	 * @param string $mode The mode statpro is running under.
	 * @param array $property_data Stat client's properties.
	 * @param OLP_Factory $olp_factory
	 * @param int $application_id
	 */
	protected function __construct($mode, array $property_data, OLP_Factory $olp_factory, $application_id = NULL)
	{
		$this->statpro = Stats_StatPro::getInstance($mode, $property_data);
		$this->application_id = (int)$application_id;
		$this->mode = $mode;
		$this->olp_factory = $olp_factory;
	}
	
	/**
	 * Hits a stat.
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
		$this->statpro->hitStat($event_type_key, $date_occurred, $event_amount, $track_key, $space_key);
		
		$this->notifyObservers($event_type_key, $date_occurred, $event_amount, $track_key, $space_key);
		
		return TRUE;
	}
	
	/**
	 * Return the track key.
	 *
	 * @return string
	 */
	public function getTrackKey()
	{
		return $this->statpro->getTrackKey();
	}
	
	/**
	 * Create (or retrieve) a track key.
	 *
	 * @param string $track_key
	 * @return string
	 */
	public function setupTrackKey($track_key = NULL)
	{
		if (!$track_key || $this->statpro->setTrackKey($track_key) != $track_key)
		{
			$track_key = $this->statpro->createTrackKey();
			
			$this->logNewTrackKey();
		}
		
		return $track_key;
	}
	
	/**
	 * Returns the space key.
	 *
	 * @return string
	 */
	public function getSpaceKey()
	{
		return $this->statpro->getSpaceKey();
	}
	
	/**
	 * Returns the mode that this instance was created with.
	 *
	 * @return string
	 */
	public function getMode()
	{
		return $this->mode;
	}
	
	/**
	 * Returns the application ID that internal actions use.
	 *
	 * @return int
	 */
	public function getApplicationID()
	{
		return $this->application_id;
	}
	
	/**
	 * Sets the application id.
	 *
	 * @param int $application_id
	 * @return void
	 */
	public function setApplicationID($application_id)
	{
		if ($this->application_id && $this->application_id !== $application_id)
		{
			//throw new Exception("Attempting to set a new application id ({$application_id}) over one that already exists ({$this->application_id})");
		}
		
		$this->application_id = (int)$application_id;
	}
	
	/**
	 * Returns the instance of Stats_StatPro that we are wrapping around.
	 *
	 * @return Stats_StatPro
	 */
	public function getStatPro()
	{
		return $this->statpro;
	}
	
	/**
	 * Attach all observers here.
	 *
	 * @return void
	 */
	public function attachAllHitStatObservers()
	{
		$event_log = new Stats_OLP_Observe_Eventlog();
		$event_log->observeHitStat($this);
	}
	
	/**
	 * Attach an observer to hitStat.
	 *
	 * @param Delegate_1 $d
	 * @return void
	 */
	public function attachObserver(Delegate_1 $d)
	{
		// get a unique hash for this delegate
		$hash = spl_object_hash($d);
		
		// add to our list of observers
		$this->observers[$hash] = $d;
	}
	
	/**
	 * Disattach an observer to hitStat.
	 *
	 * @param Delegate_1 $d
	 * @return void
	 */
	public function detachObserver(Delegate_1 $d)
	{
		// detach this observer
		$hash = spl_object_hash($d);
		unset($this->observers[$hash]);
	}
	
	/**
	 * Notify all observers of hitStat.
	 *
	 * @param string $event_type_key
	 * @param int $date_occurred
	 * @param int $event_amount
	 * @param string $track_key
	 * @param string $space_key
	 * @return void
	 */
	protected function notifyObservers($event_type_key, $date_occurred, $event_amount, $track_key, $space_key)
	{
		foreach ($this->observers as $d)
		{
			$d->invoke($this, $event_type_key, $date_occurred, $event_amount, $track_key, $space_key);
		}
	}
}

?>
