<?php
/**
 * Implements storage and retrieval of cpanel events in local memory, memcache, and database
 *
 * @author Eric Johney <eric.johney@ericjohney.com>
 */
class EventMap
{
	/**
	 * stores reference to singleton instance of self
	 *
	 * @var EventMap
	 */
	private static $instance;

	/**
	 * OLP database connection
	 *
	 * @var DB_Database_1
	 */
	protected $olp_db;

	/**
	 * stores memcache connection object
	 *
	 * @var Cache_Memcache
	 */
	protected $memcache;

	/**
	 * stores event map values in local memory
	 *
	 * @var array
	 */
	protected $events = array();

	/**
	 * private so can only be instantiated from the getInstance method
	 *
	 * @param DB_Database_1 $db
	 */
	private function __construct(DB_Database_1 $db)
	{
		$this->init($db);
	}

	/**
	 * returns a singleton instance of EventMap
	 *
	 * @param DB_Database_1 $db
	 * @return EventMap
	 */
	public static function getInstance(DB_Database_1 $db)
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self($db);
		}

		return self::$instance;
	}

	/**
	 * sets database and memcache connections
	 *
	 * @param DB_Database_1 $db
	 * @return void
	 */
	protected function init(DB_Database_1 $db)
	{
		$this->olp_db = $db;
		$this->memcache = Cache_Memcache::getInstance();
	}

	/**
	 * Checks to see if the event exists in the database and memcache and adds them if they don't
	 *
	 * @param string $event
	 * @return void
	 */
	public function saveEvent($event)
	{
		$event = trim($event);
		
		// checks that this event does not exist in the database already
		if (!$this->eventExistsInDatabase($event))
		{
			$this->saveToDatabase($event);
		}
		
		if (!$this->eventExistsInMemcache($event))
		{
			$this->saveToMemcache($event);
		}
		
		$this->saveToLocal($event);
	}

	/**
	 * stores an event or array of events in local memory
	 *
	 * @param string|array $event
	 * @param bool $overwrite
	 * @return void
	 */
	protected function saveToLocal($event, $overwrite = FALSE)
	{
		if ($overwrite)
		{
			$this->events = array();
		}
		
		if (is_array($event))
		{
			$this->events = array_merge($this->events, $event);
		}
		else if (!in_array($event, $this->events))
		{
			$this->events[] = $event;
		}
		natcasesort($this->events);
	}

	/**
	 * Returns the appropriate memcache key
	 *
	 * @return string
	 */
	public function getCacheKey()
	{
		return 'EventMap';
	}
	
	/**
	 * stores an event or array of events in memcache
	 *
	 * @param string|array $event
	 * @param bool $overwrite
	 * @return void
	 */
	public function saveToMemcache($event, $overwrite = FALSE)
	{
		if (!$overwrite)
		{
			$events = $this->getEventsFromMemcache();
		}
		else
		{
			$events = array();
		}
		
		if (!is_array($event))
		{
			$event = array($event);
		}
		$events = array_merge($events, $event);
		natcasesort($events);
		
		// Store forever.  It will only be modified if an event is deleted
		$this->memcache->set($this->getCacheKey(), serialize($events), 0);
	}

	/**
	 * stores an event in database
	 *
	 * @param string $event
	 * @return void
	 */
	public function saveToDatabase($event)
	{
		DB_Util_1::queryPrepared(
			$this->olp_db,
			'INSERT INTO cpanel_eventmap (event, date_created) VALUES (?, NOW())',
			array($event)
		);
	}

	/**
	 * User callable function that will return the event if it is found
	 *
	 * returns TRUE if found or FALSE if event not found
	 * 
	 * @param string $event
	 * @return bool
	 */
	public function eventExists($event)
	{
		// initialize return value
		$result = FALSE;

		// try to find event in local memory first
		if (in_array($event, $this->events))
		{
			$result = TRUE;
		}
		// if event hasn't been found in local memory, try searching memcache
		else
		{
			// if the event was not found in memcache, but exists in the database, store it for future use
			if ($this->eventExistsInMemcache($event))
			{
				$result = TRUE;
			}
			else if ($this->eventExistsInDatabase($event))
			{
				$this->saveToMemcache($event);
				$result = TRUE;
			}
			
			// if the event was found in the database or memcache, save for future use
			if ($result)
			{
				$this->saveToLocal($event);
			}
		}

		return $result;
	}

	/**
	 * searches memcache to see if the event is set
	 *
	 * returns TRUE if found or FALSE if event not found
	 *
	 * @param string $event
	 * @return bool
	 */
	protected function eventExistsInMemcache($event)
	{
		$memcache = $this->memcache->get($this->getCacheKey());
		if (!empty($memcache))
		{
			$memcache = unserialize($memcache);
		}
		else
		{
			//nothing currently exists in memcache, call the refresh function to repopulate the array
			$memcache = array();
			$this->refreshFromDatabase();
		}
		
		return in_array($event, $memcache);
	}

	/**
	 * searches olp database to see if event currently exists
	 *
	 * returns TRUE if found or FALSE if event not found
	 * 
	 * @param string $event
	 * @return bool
	 */
	protected function eventExistsInDatabase($event)
	{
		$result = FALSE;
		
		try
		{
			$result = DB_Util_1::querySingleValue(
				$this->olp_db,
				'SELECT count(id) FROM cpanel_eventmap WHERE event = ?',
				array($event)
			);
			
			if ($result > 0)
			{
				$result = TRUE;
			}
			else
			{
				$result = FALSE;
			}
		}
		catch (Exception $e)
		{
			$result = FALSE;
		}

		return $result;
	}
	
	/**
	 * Reload memcache/local from the database.
	 * 
	 * @return void
	 */
	public function refreshFromDatabase()
	{
		try
		{
			$events = DB_Util_1::querySingleColumn($this->olp_db, 'SELECT event FROM cpanel_eventmap ORDER BY event');
			$this->saveToMemcache($events, TRUE);
			$this->saveToLocal($events, TRUE);
		}
		catch (Exception $e)
		{
		}
	}
	
	public function getEvents()
	{
		$return = $this->events;
		if (empty($return))
		{
			$return = $this->getEventsFromMemcache();
			if (empty($return))
			{
				$this->refreshFromDatabase();
				$return = $this->events;
			}
		}
		
		return $return;
	}
	
	protected function getEventsFromMemcache()
	{
		$memcache = $this->memcache->get($this->getCacheKey());
		if (!empty($memcache))
		{
			$memcache = unserialize($memcache);
		}
		else
		{
			$memcache = array();
		}
		
		return $memcache;
	}
	
/**
	 * Checks to see if the event exists in the database and memcache and deletes them if they do
	 *
	 * @param string $event
	 * @return void
	 */
	public function deleteEvent($event)
	{
		$event = trim($event);
		
		if ($this->eventExistsInDatabase($event))
		{
			$this->deleteFromDatabase($event);
		}
		
		if ($this->eventExistsInMemcache($event))
		{
			$this->deleteFromMemcache($event);
		}
		
		$this->deleteFromLocal($event);
	}

	/**
	 * deletes an event or array of events from local memory
	 *
	 * @param string|array $event
	 * @return void
	 */
	protected function deleteFromLocal($event)
	{
		if (!is_array($event))
		{
			$event = array($event);
		}
		$this->events = array_diff($this->events, $event);
	}
	
	/**
	 * deletes an event or array of events from memcache
	 *
	 * @param string|array $event
	 * @return void
	 */
	public function deleteFromMemcache($event)
	{		
		$events = $this->getEventsFromMemcache();
		
		if (!is_array($event))
		{
			$event = array($event);
		}
		$events = array_diff($events, $event);
		
		// Store forever.  It will only be modified if an event is deleted
		$this->memcache->set($this->getCacheKey(), serialize($events), 0);
	}

	/**
	 * deletes an event or array of events from database
	 *
	 * @param string|array $event
	 * @return void
	 */
	public function deleteFromDatabase($event)
	{
		if (!is_array($event))
		{
			$event = array($event);
		}
		
		DB_Util_1::queryPrepared(
			$this->olp_db,
			'DELETE FROM cpanel_eventmap WHERE event IN (' . implode(', ', array_fill(0, count($event), '?')) . ')',
			$event
		);
	}
}
?>
