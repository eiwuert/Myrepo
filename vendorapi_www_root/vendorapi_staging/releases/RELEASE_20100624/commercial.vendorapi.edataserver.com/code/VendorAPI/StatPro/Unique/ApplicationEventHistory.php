<?php
/**
 * Event history provider for application uniqueness
 * @author Adam Englander <adam.englander@sellingsource.com>
 * @author Alex Rosekrans <alex.rosekrans@partnerweekly.com>
 */
class VendorAPI_StatPro_Unique_ApplicationEventHistory
		implements VendorAPI_StatPro_Unique_IHistory
{
	/**
	 * @var DB_Database_1
	 */
	protected $db;
	
	/**
	 * @var array
	 */
	protected static $event_name_id_cache = array();
	
	/**
	 * @var array
	 */
	protected $loaded_application_id = array();
	
	/**
	 * @var array
	 */
	protected $event_history = array();

	/**
	 * Provider requires a database
	 * @param DB_Database_1 $db
	 */
	public function __construct(DB_IConnection_1 $db)
	{
		$this->db = $db;
	}

	/**
	 * Add the application/event combination to the history
	 * @param string $stat_name
	 * @param integer $application_id
	 */
	public function addEvent($event_name, $application_id)
	{
		$event_name = $this->normalizeEventName($event_name);
		$model = $this->getEventHistoryModel();
		$now = $this->getNowString();
		$model->application_id = $application_id;
		$model->stat_name_id = $this->getEventIdFromName($event_name);
		$model->date_created = $now;
		$model->date_modified = $now;
		$model->save();
		if ($application_id == $this->loaded_application_id)
		{
			$this->event_history[] = $event_name;
		}
	}

	/**
	 * Does the application/event combination exist in the history
	 * @param string $stat_name
	 * @param integer $application_id
	 */
	public function containsEvent($event_name, $application_id)
	{
		$event_name = $this->normalizeEventName($event_name);
		if ($application_id != $this->loaded_application_id)
		{
			$model = $this->getEventHistoryModel();
			$events = $model->loadAllBy(array("application_id" => $application_id));
			foreach ($events as $event)
			{
				$this->event_history[] = $this->getEventNameFromId($event->stat_name_id);
			}
			$this->loaded_application_id = $application_id;
		}
		$found = in_array($event_name, $this->event_history);
		return $found;
	}

	/**
	 * Get the event name id for the given event name
	 * @param string
	 * @return integer
	 */
	protected function getEventIdFromName($event_name)
	{
		$key = array_search($event_name, self::$event_name_id_cache);
		if ($key === FALSE)
		{
			$model = $this->getEventNameModel();
			$events = $model->loadAllBy(array("name" => $event_name));

			if ($events->count() < 1)
			{
				$model->name = $event_name;
				$model->active_status = TRUE;
				$date = $this->getNowString();
				$model->date_created = $date;
				$model->date_modified = $date;
				$model->save();
			}
			else
			{
				$model = $events->next();
			}
			$key = $model->stat_name_id;
			self::$event_name_id_cache[$key] = $event_name;
		}

		return $key;
	}
	
	/**
	 * Get the event name for the given event id
	 * @param integer
	 * @return string
	 */
	protected function getEventNameFromId($id)
	{
		if (!isset(self::$event_name_id_cache[$id]))
		{
			$model = $this->getEventNameModel();
			$events = $model->loadAllBy(array("stat_name_id" => $id));
			if ($events->count() < 1)
			{
				throw new RuntimeException("Unknown EventNameId: " . $id);
			}
			else
			{
				$model = $events->next();
			}
			self::$event_name_id_cache[$id] = $model->name;
		}
		return self::$event_name_id_cache[$id];
	}

	/**
	 * Normalize the event name
	 * @param string $event_name
	 * @return string
	 */
	private function normalizeEventName($event_name)
	{
		return strtolower($event_name);
	}

	/**
	 * Get a SQL compatible string for data created/modified
	 * @return unknown_type
	 */
	protected function getNowString()
	{
		return date("Y-m-d H:i:s");
	}
	
	protected function getEventHistoryModel()
	{
		return new VendorAPI_StatPro_Unique_ApplicationEventHistoryModel($this->db);
	}
	
	protected function getEventNameModel()
	{
		return new VendorAPI_StatPro_Unique_EventNameModel($this->db);
	}
}