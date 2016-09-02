<?php

/** Store certain stats into the session to make them unique. Once we get
 * database accessiblity, we then convert it into the database for uniqueness.
 *
 * Once we have an application id, this class does nothing and relies on
 * Stats_OLP_UniqueAppID to handle uniqueness like other unique stats.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
abstract class Stats_OLP_UniqueSession extends Stats_OLP_UniqueAppID
{
	/** These are stats that need to be unique before we have an application
	 * ID.
	 *
	 * @var array
	 */
	protected $unique_stats_session = array(
		'visitors',
		'cs_login',
		'cs_login_link',
		
		// This is currently hit through a cronjob, so as of writing this,
		// it will never enter this process. But if it does, it is here
		// anyways.
		'react_start',
	);
	
	/**
	 * @var array
	 */
	protected $new_stat_history_session = array();
	
	/** Do uniqueness via session.
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
		
		if (!$this->application_id
			&& in_array($event_type_key, $this->unique_stats_session)
			&& ($track_key === NULL || $this->track_key == $track_key))
		{
			if (!isset($_SESSION['stats']['stat_unique']))
			{
				$_SESSION['stats']['stat_unique'] = array();
			}
			
			if (isset($_SESSION['stats']['stat_unique'][$event_type_key]))
			{
				$hit_stat = FALSE;
			}
			else
			{
				$_SESSION['stats']['stat_unique'][$event_type_key] = date(self::DATE_FORMAT);
				
				// Only hit unique stats once
				if ($event_amount > 1)
				{
					$event_amount = 1;
				}
			}
		}
		
		if ($hit_stat)
		{
			$hit_stat = parent::hitStat($event_type_key, $date_occurred, $event_amount, $track_key, $space_key);
		}
		
		return $hit_stat;
	}
	
	/** Once we set an application id, if we have any session stats stored,
	 * update it to the database.
	 *
	 * @param int $application_id
	 * @return void
	 */
	public function setApplicationID($application_id)
	{
		parent::setApplicationID($application_id);
		
		if (is_array($_SESSION['stats']['stat_unique']) && $this->getApplicationID())
		{
			// If we have unique stats stored in the session AND
			// an application id, convert them into the DB format.
			
			foreach ($_SESSION['stats']['stat_unique'] AS $stat_name => $time_occurred)
			{
				$this->insertUniqueStat($stat_name);
			}
			
			unset($_SESSION['stats']['stat_unique']);
		}
	}
	
	/** Returns an array of unique stats already hit.
	 *
	 * @return array
	 */
	public function getUniqueStatHistory()
	{
		$stats_hit = parent::getUniqueStatHistory();
		
		if (is_array($_SESSION['stats']['stat_unique']))
		{
			$stats_hit = array_merge($stats_hit, array_keys($_SESSION['stats']['stat_unique']));
		}
		
		return $stats_hit;
	}
	
	/** Determine if this specific stat has been hit yet.
	 *
	 * @param string $stat_name
	 * @return bool
	 */
	public function wasStatHitYet($stat_name)
	{
		$was_hit = FALSE;
		
		if (isset($_SESSION['stats']['stat_unique'][strtolower($stat_name)]))
		{
			$was_hit = TRUE;
		}
		elseif (parent::wasStatHitYet($stat_name))
		{
			$was_hit = TRUE;
		}

		return $was_hit;
	}
	
	/** Returns an array of unique stats hit during this load so far.
	 *
	 * @return array
	 */
	public function getNewUniqueStatHistory()
	{
		$stats_hit = parent::getNewUniqueStatHistory();
		
		if (is_array($_SESSION['stats']['stat_unique']))
		{
			$stats_hit = array_merge($stats_hit, $this->new_stat_history_session);
		}
		
		return $stats_hit;
	}
}

?>
