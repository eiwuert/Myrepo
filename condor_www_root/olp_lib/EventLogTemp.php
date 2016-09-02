<?php

/**
 * Event_Log class for writing to the application's event log.
 * 
 * PHP version 5
 * 
 * @author Kevin Kragenbrink
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 * @copyright 2007 Selling Source, Inc.
 */

/**
 * Event_Log class for writing to the application's event log.
 * 
 * The Event_Log class provides an interface for writing to the OLP event_log. The
 * event_log tables are currently partitioned by date and a modulus 10 of the
 * application ID.
 * 
 * @author Kevin Kragenbrink
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 * @copyright 2007 Selling Source, Inc.
 */
class EventLogTemp
{
	/**
	 * Connection to the OLP database.
	 *
	 * @var object
	 */
	private $sql;

	/**
	 * Application ID to use.
	 *
	 * @var int
	 */
	private $application_id;

	/**
	 * I don't know what this is for...
	 *
	 * @var string
	 */
	private $event;
	
	/**
	 * Again, why are we saving this???
	 *
	 * @var string
	 */
	private $response;
	
	/**
	 * Target that the event is related to.
	 * 
	 * Why are we saving this???
	 *
	 * @var string
	 */
	private $target;
	
	/**
	 * The database table the event writes to.
	 *
	 * @var string
	 */
	private $table;
	
	/**
	 * The name of the database we're writing to.
	 *
	 * @var string
	 */
	private $database;
	
	/**
	 * Cached list of events.
	 *
	 * @var array
	 */
	private $events;
	
	/**
	 * Cached list of responses.
	 *
	 * @var array
	 */
	private $responses;
	
	/**
	 * Cached list of targets.
	 *
	 * @var array
	 */
	private $targets;
	
	/**
	 * Fetch_Event constants for how to group the results.
	 */
	const GROUP_BY_TARGET = 'target';
	const GROUP_BY_EVENT = 'event';
	const GROUP_BY_DYNAMIC = 'dynamic';
	
	/**
	 * The event_log base table name.
	 */
	const EVENT_LOG_REFERENCE_TABLE = 'event_log';
	
	/**
	 * The number of table partitions based on application ID.
	 */
	const TABLE_PARTITIONS = 10;
	
	/**
	 * Event_Log constructor.
	 *
	 * @param object    $sql            an object for database connection
	 * @param string    $database       a string of the database name
	 * @param int       $application_id an integer for the application ID
	 * @param string    $table          a string with the table name
	 */
	public function __construct(&$sql, $database, $application_id = null, $table = null)
	{
		$this->sql = &$sql;
		$this->database = $database;
		$this->application_id = $application_id;
	
		// initialize our caches
		$this->events = array();
		$this->responses = array();
		$this->targets = array();
	
		if ($table === null && is_numeric($application_id))
		{
			$this->tableName($application_id);
		}
		else
		{
			$this->table = $table;
		}
	}

	/**
     * Event_Log destructor.
     * 
     * Unsets the database connection object.
     */
	public function __destruct()
	{
		unset($this->sql);
	}

	/**
	 * Determines the table name based on the date and the application ID.
	 * 
	 * Determines the table name based on the year and month the event was hit
	 * and the application ID that the event is being hit on.
	 *
	 * @param int $application_id the application ID to hit the event on
	 * @return string a string of the table name
	 */
	public function tableName($application_id = null)
	{
		/*
		This ensures that we don't put it in next month's table when the tables
		roll over for the next month. The session stores what table it uses. If
		we've set that, we don't want to override it.
		*/
		if(!empty($this->table)) return $this->table;

		$tableSuffix = '_' . date('Ym');
		if (is_null($application_id) && is_numeric($this->application_id))
		{
			// Use the class application ID
			$tableSuffix .= '_' . $this->application_id % self::TABLE_PARTITIONS;
		}
		elseif (is_numeric($application_id))
		{
			// Use the passed in application ID
			$tableSuffix .= '_' . $application_id % self::TABLE_PARTITIONS;
		}
		else
		{
			// We don't have a defined table and no application ID to base it off of
			throw new Event_Log_Exception('Application ID not provided.');
		}

		$this->table = self::EVENT_LOG_REFERENCE_TABLE . $tableSuffix;
		return $this->table;
	}
		
	/**
	 * Logs an event in the event_log table.
	 * 
	 * Logs an event, its response, its target (if applicable), and the mode (if
	 * applicable) into the event_log for an application.
	 *
	 * @param string $event         the string of the event
	 * @param string $response      the string with the event's response
	 * @param string $target        a string with the target's name
	 * @param int $application_id   an integer with the application ID
	 * @param string $mode          a string with the mode
	 * @return bool a boolean if the write was successful
	 */
	public function Log_Event($event, $response, $target = null, $application_id = null, $mode = null)
	{
		$result = false;
		
		// For the time being make ECASH REACT mode a PREQUAL mode [RL}
		if (strcasecmp($mode, "ECASH_REACT") == 0)
		{
			$mode = "BROKER";
		}
		
		// Why the application ID changed, we don't know, but let's save it
		if (is_numeric($application_id))
		{
			$this->application_id = $application_id;
		}
		elseif(!is_numeric($this->application_id))
		{
			throw new Event_Log_Exception('Application ID not provided.');
		}
		
		// Check that we've set the table name
		$this->tableName();
		
		// Get the IDs for the event and the response
		$event_id = $this->Find_Event_ID(strtoupper($event));
		$response_id = $this->Find_Response_ID(strtoupper($response));
		
		if (!is_null($target))
		{
			$target_id = $this->Find_Target_ID(strtoupper($target));
		}
		else
		{
			$target_id = 'NULL';
		}
		
		if (($event_id !== false) && ($response_id !== false) && ($target_id !== false))
		{
			try
			{
				$query = "
					INSERT INTO `{$this->table}`
					(
						application_id,
						event_id,
						response_id,
						target_id,
						mode,
						date_created
					)
					VALUES
					(
						{$this->application_id},
						{$event_id},
						{$response_id},
						{$target_id},
						".(is_null($mode) ? 'NULL' : "'{$mode}'").",
						NOW()
					)";
				$this->sql->Query($this->database, $query);
				
				$result = TRUE;
			}
			catch (Exception $e)
			{
				if (preg_match('/exist/', $e->getMessage()))
				{
					// create a new table and log this event
					$this->Create_Table();
					$result = $this->Log_Event($event, $response);
				}
				else
				{
					throw($e);
				}
			}
		}

		return $result;
	}

	/**
	 * Checks if an event has been hit for a given application ID.
	 *
	 * @param int       $application_id an int with the application ID
	 * @param string    $event          a string with the event name
	 * @param string    $target         a string with the target name
	 * @param string    $mode           a string with the mode value
	 * @return bool a boolean value if the event was hit. TRUE if the event has been
	 *              hit for the given application ID. FALSE if not.
	 */
	public function Check_Event($application_id, $event, $target = null, $mode = null)
	{
		$query = "
			SELECT
				event_log.id
			FROM
				{$this->table} event_log
				INNER JOIN events
					ON event_log.event_id = events.event_id
			WHERE";

		if (!is_null($target))
		{
			$target_id = $this->Find_Target_ID(strtoupper($target));
			if ($target_id)
			{
				$query .= " event_log.target_id = {$target_id} AND";
			}
		}

		if (!is_null($mode))
		{
			$query .= " mode = '{$mode}' AND";
		}

		$query .= " event_log.application_id = {$application_id}
			AND events.event = '{$event}'
			ORDER BY event_log.id DESC
			LIMIT 1";

		$result = $this->sql->Query($this->database, $query);
		$return = is_numeric($this->sql->Fetch_Column($result, 'id'));

		return $return;
	}
		
		/**
			
			@desc Fetch events by any combination of the
				event, response, and/or target.
				
			@todo Currently, events, responses, and targets
				are returned upper case; we may want to return
				them in the same case they were provided in
			
		*/
		public function Fetch_Events($application_id = NULL, $event = NULL, $response = NULL, $target = NULL, $mode = NULL, $group_by = self::GROUP_BY_EVENT)
		{
			
			// translate names into IDs from the lookup tables
			
			$good = TRUE;
			$null_target = FALSE;
			
			if (is_null($application_id))
			{
				$application_id = $this->application_id;
			}
			
			if (is_string($event) || is_array($event))
			{
				$event = $this->Find_Event_ID($event, FALSE);
				$good = ($good && ((is_array($event) && (!in_array(FALSE, $event))) || is_numeric($event)));
			}
			
			if (is_string($response) || is_array($response))
			{
				$response = $this->Find_Response_ID($response, FALSE);
				$good = ($good && ((is_array($response) && (!in_array(FALSE, $response))) || is_numeric($response)));
			}
			
			if (is_string($target) || is_array($target))
			{
				
				if (is_array($target) && in_array(NULL, $target))
				{
					$null_target = TRUE;
					unset($target[array_search(NULL, $target)]);
				}
				
				$target = $this->Find_Target_ID($target);
				$good = ($good && ((is_array($target) && (!in_array(FALSE, $target))) || is_numeric($target)));
				
			}
			
			// Check that we've set the table name
			$this->tableName($application_id);
			
			// assume we fail
			$events = FALSE;
			
			if (is_numeric($application_id) && $good)
			{
				
				$query = "
					SELECT
						event_log.id  as id,
						UPPER(events.event) AS event,
						UPPER(responses.response) AS response,
						UPPER(target.property_short) AS target
					FROM
						{$this->table} AS event_log
						LEFT JOIN target ON target.target_id=event_log.target_id,
						events,
						event_responses AS responses
					WHERE
						events.event_id=event_log.event_id AND
						responses.response_id=event_log.response_id AND
						event_log.application_id={$application_id}";
					
				if (!is_null($event))
				{
					$query .= ' AND event_log.event_id '. (is_numeric($event) ? '= '.$event : 'IN ('.implode(',', $event).')');
				}
				
				if (!is_null($response))
				{
					$query .= ' AND event_log.response_id '. (is_numeric($response) ? '= '.$response : 'IN ('.implode(',', $response).')');
				}
				
				if (!is_null($target))
				{
					$query .= ' AND (event_log.target_id '. (is_numeric($target) ? '= '.$target : 'IN ('.implode(',', $target).')');
					$query .= ($null_target ? ' OR event_log.target_id IS NULL)' : ')');
				}
				
				if (!is_null($mode))
				{
					$query .= " AND mode ".(is_array($mode) ? "IN ('".implode("','", $mode)."')" : "= '".$mode."'");
				}
				
				$query .= ' ORDER BY event_log.id';
				
				try
				{
					
					// run the query
					$result = $this->sql->Query($this->database, $query);
					
					// hold the results
					$events = array();
					
					while ($rec = $this->sql->Fetch_Array_Row($result))
					{
						
						
						$target = $rec['target'];
						$log_id = $rec['id'];
						$event = $rec['event'];
						$response = $rec['response'];
						
						// events without a target go into $events['']
						if (is_null($target)) $target = '';
						
						switch ($group_by)
						{
							
							case (self::GROUP_BY_DYNAMIC):
								// Dynamic Listing of Events by Event ID (Returns mutiple rows) [RL]
								if (!isset($events[$event])) $events[$event] = array();
								$events[$event][$target][$log_id] = $response;
								break;
								
							case (self::GROUP_BY_EVENT):
								// add this event/response to our results
								if (!isset($events[$event])) $events[$event] = array();
								$events[$event][$target] = $response;
								break;
								
							case (self::GROUP_BY_TARGET):
							default:
								// add this event/response to our results
								if (!isset($events[$target])) $events[$target] = array();
								$events[$target][$event] = $response;
								break;
								
						}
						
					}
					
				}
				catch (Exception $e)
				{
					$events = FALSE;
				}
				
			}
			
			return($events);
			
		}

	 /**
		* @publicsection
		* @public
		* @fn void Get_Response( $application_id, $event, $target );
		* @brief
		*		 Returns the stored response for a previous event.
		* @param		$application_id		 int:				The OLP application ID to be checked.
		* @param		$event							string:		 The name of the event to be checked.
		* @param		$target						 string:		 The property short of the target being logged for (OPTIONAL)
		* @return	 BOOL
		* @todo
		*/
		public function Get_Response( $application_id, $event, $target = NULL )
		{
			
			$query = "
					SELECT
							event_responses.response
					FROM
							" . $this->table . " event_log
					LEFT JOIN
							events ON event_log.event_id = events.event_id
					LEFT JOIN
							event_responses ON event_log.response_id = event_responses.response_id
			";
			
			if( $target !== NULL )
			{
					$query .= "
					JOIN
							target ON event_log.target_id = target.target_id
					WHERE
							target.property_short = '" . $target . "'
							AND target.status='ACTIVE'
							AND target.deleted='FALSE'
					AND
					";
			}
			else
			{
					$query .= "
					WHERE
					";
					
			}
			
			$query .= "
					event_log.application_id = " . $application_id . "
				AND
					events.event = '" . $event . "'
				ORDER BY
					event_log.id DESC
				LIMIT 1,1
			";
			
			$result = $this->sql->Query($this->database, $query);
			$return = $this->sql->Fetch_Column($result, 'response');
			
			return $return;
			
		}
		
	 /**
		* @private
		* @private
		* @fn void Find_Event_ID();
		* @brief
		*		 Finds the event_id in the events table, or calls Create_Event if there is none.
		* @return 
		*		 event_id.
		* @todo
		*/
		public function Find_Event_ID($event, $auto_create = TRUE)
		{
			
			// hold our results
			$event_id = array();
			
			if (is_array($event))
			{
				
				// transform this into (event => '') pairs
				$event_id = array_combine(array_unique($event), array_fill(0, count($event), ''));
				
				// pull what we can from our cache, if anything: this only
				// works because of the 1:1 relationship between event names and IDs
				$cache = array_flip(array_intersect(array_flip($this->events), $event));
				$event_id = array_merge($event_id, $cache);
				
				if (count($cache) != count($event_id))
				{
					// search for whatever we couldn't get from our cache
					$find = array_keys($event_id, '');
				}
				
			}
			elseif (!isset($this->events[strtolower($event)]))
			{
				// gotta find it
				$find = array($event);
			}
			else
			{
				// pull directly from our cache
				$event_id[$event] = $this->events[strtolower($event)];
			}
			
			// do we have to find any?
			if (isset($find) && count($find))
			{
				
				// map what they gave us to lowercase values
				$map = array_change_key_case(array_combine($find, $find));
				
				try
				{
					
					// build our query
					$query = 'SELECT LOWER(event) AS event, event_id FROM events WHERE
						event '. ((count($find) > 1) ? "IN ('".implode("', '", $find)."')" : "='".reset($find)."'");
					$result = $this->sql->Query($this->database, $query);
					
				}
				catch (Exception $e)
				{
					$result = FALSE;
				}
				
				if ($result !== FALSE)
				{
					
					while ($rec = $this->sql->Fetch_Array_Row($result))
					{
						
						// save the ID
						$event_id[$map[$rec['event']]] = (int)$rec['event_id'];
						$this->events[$rec['event']] = (int)$rec['event_id'];
						
						// remove this from our list of things to find
						unset($find[array_search($map[$rec['event']], $find)]);
						
					}
					
				}
				
				// do we still have things we couldn't find?
				if (count($find))
				{
					
					foreach ($find as $name)
					{
						
						$new = FALSE;
						
						// we only try to create events if our query didn't
						// fail above: otherwise, we could end up w/ duplicates
						if ($result && $auto_create)
						{
							
							try
							{
								// create the event
								$new = $this->Create_Event($name);
							}
							catch (Exception $e)
							{
							}
							
						}
						
						// save the new ID
						$event_id[$name] = $new;
						
						if ($new !== FALSE)
						{
							// save it in our cache
							$this->events[strtolower($name)] = $new;
						}
						
					}
					
				}
				
			}
			
			// return it in the same way we got it
			if (!is_array($event)) $event_id = reset($event_id);
			return($event_id);
			
		}
		
	 /**
		* @private
		* @private
		* @fn void Find_Response_ID();
		* @brief
		*		 Finds the response_id in the event_responses table, or calls Create_Response if there is none.
		* @return 
		*		 response_id.
		* @todo
		*/
		public function Find_Response_ID($response, $auto_create = TRUE)
		{
			
			// hold our results
			$response_id = array();
			
			if (is_array($response))
			{
				
				// transform this into (event => '') pairs
				$response_id = array_combine($response, array_fill(0, count($response), ''));
				
				// pull what we can from our cache, if anything: this only
				// works because of the 1:1 relationship between event names and IDs
				$cache = array_flip(array_intersect(array_flip($this->responses), $response));
				$response_id = array_merge($response_id, $cache);
				
				if (count($cache) != count($response_id))
				{
					// search for whatever we couldn't get from our cache
					$find = array_keys($response_id, '');
				}
				
			}
			elseif (!isset($this->events[strtolower($response)]))
			{
				// gotta find it
				$find = array($response);
			}
			else
			{
				// pull directly from our cache
				$response_id[$response] = $this->events[strtolower($response)];
			}
			
			// do we have to find any?
			if (isset($find) && count($find))
			{
				
				// map what they gave us to lowercase values
				$map = array_change_key_case(array_combine($find, $find));
				
				try
				{
					// build our query
					$query = 'SELECT LOWER(response) AS response, response_id FROM event_responses WHERE
						response '. ((count($find) > 1) ? "IN ('".implode("', '", $find)."')" : "='".reset($find)."'");
					$result = $this->sql->Query($this->database, $query);
				}
				catch (Exception $e)
				{
					$result = FALSE;
				}
				
				if ($result !== FALSE)
				{
					
					while ($rec = $this->sql->Fetch_Array_Row($result))
					{
						
						// save the ID
						$response_id[$map[$rec['response']]] = (int)$rec['response_id'];
						$this->responses[$rec['response']] = (int)$rec['response_id'];
						
						// remove this from our list of things to find
						unset($find[array_search($map[$rec['response']], $find)]);
						
					}
					
				}
				
				// do we still have things we couldn't find?
				if (count($find))
				{
					
					foreach ($find as $name)
					{
						
						$new = FALSE;
						
						// we only try to create events if our query didn't
						// fail above: otherwise, we could end up w/ duplicates
						if ($result && $auto_create)
						{
							
							try
							{
								// create the event
								$new = $this->Create_Response($name);
							}
							catch (Exception $e)
							{
							}
							
						}
						
						// save the new ID
						$response_id[$name] = $new;
						
						if ($new !== FALSE)
						{
							// save it in our cache
							$this->responses[strtolower($name)] = $new;
						}
						
					}
					
				}
				
			}
			
			// return it in the same way we got it
			if (!is_array($response)) $response_id = reset($response_id);
			return($response_id);
			
		}
		
	 /**
		* @private
		* @private
		* @fn void Find_Target_ID();
		* @brief
		*		 Finds the target_id in the target table, or returns 0 if there is none.
		* @return	 int	 target_id.
		* @todo
		*/		
		public function Find_Target_ID($target)
		{
			
			// hold our results
			$target_id = array();
			
			if (is_array($target))
			{
				
				// transform this into (event => '') pairs
				$target_id = array_combine($target, array_fill(0, count($target), ''));
				
				// pull what we can from our cache, if anything: this only
				// works because of the 1:1 relationship between event names and IDs
				$cache = array_flip(array_intersect(array_flip($this->targets), $target));
				$target_id = array_merge($target_id, $cache);
				
				if (count($cache) != count($target_id))
				{
					// search for whatever we couldn't get from our cache
					$find = array_keys($target_id, '');
				}
				
			}
			elseif (!isset($this->targets[strtolower($target)]))
			{
				// gotta find it
				$find = array($target);
			}
			else
			{
				// pull directly from our cache
				$target_id[$target] = $this->targets[strtolower($target)];
			}
			
			// do we have to find any?
			if (isset($find) && count($find))
			{
				
				// map what they gave us to lowercase values
				$map = array_change_key_case(array_combine($find, $find));
				
				try
				{
					// build our query
					$query = "SELECT LOWER(property_short) AS target, target_id FROM target WHERE
						target.status = 'ACTIVE' AND target.deleted = 'FALSE' AND
						property_short ". ((count($find) > 1) ? "IN ('".implode("', '", $find)."')" : "='".reset($find)."'");
					$result = $this->sql->Query($this->database, $query);
				}
				catch (Exception $e)
				{
					$result = FALSE;
				}
				
				if ($result !== FALSE)
				{
					
					while ($rec = $this->sql->Fetch_Array_Row($result))
					{
						
						// save the ID
						$target_id[$map[$rec['target']]] = (int)$rec['target_id'];
						$this->targets[$rec['target']] = (int)$rec['target_id'];
						
						// remove this from our list of things to find
						unset($find[array_search($map[$rec['target']], $find)]);
						
					}
					
				}
				
				// do we still have things we couldn't find?
				if (count($find))
				{
					$find = array_combine($find, array_fill(0, count($find), FALSE));
					$target_id = array_merge($target_id, $find);
				}
				
			}
			
			// return it in the same way we got it
			if (!is_array($target)) $target_id = reset($target_id);
			return($target_id);
			
		}

	 /**
		* @private
		* @private
		* @fn void Create_Event();
		* @brief
		*		 Writes a new event to the events table.
		* @return 
		*		 event_id.
		* @todo
		*/		
		private function Create_Event($event)
		{
			
			$query = "
				INSERT INTO
						events
				(
						event,
						date_created
				)
				VALUES
				(
						'" . $event . "',
						CURRENT_TIMESTAMP()
				)";
			
			try
			{
				$result = $this->sql->Query( $this->database, $query );
				$event_id = $this->sql->Insert_ID();
			}
			catch ( MySQL_Exception $e )
			{
				throw $e;
			}
			
			return $event_id;
			
		}

	 /**
		* @private
		* @private
		* @fn void Create_Response();
		* @brief
		*		 Writes a new response to the event_responses table.
		* @return 
		*		 response_id.
		* @todo
		*/		
		private function Create_Response($response)
		{
			
			$query = "
				INSERT INTO
						event_responses
				(
						response,
						date_created
				)
				VALUES
				(
						'" . $response . "',
						CURRENT_TIMESTAMP()
				)";
			
			try
			{
				$result = $this->sql->Query( $this->database, $query );
				$response_id = $this->sql->Insert_ID();
			}
			catch( MySQL_Exception $e )
			{
				throw $e;
			}
			
			return $response_id;
			
		}
		
		/**
		 * Creates the event_log_YYYYmm table when the month rolls over.
		 */
		private function Create_Table()
		{
			try
			{
				$query = "CREATE TABLE {$this->table} LIKE ";
				$query .= self::EVENT_LOG_REFERENCE_TABLE;
				
				$this->sql->Query($this->database, $query);
			}
			catch(MySQL_Exception $e)
			{
				throw $e;
			}
		}
		
		private function Analyze_Table()
		{
			
			$result = TRUE;
			
			try
			{
				$query = 'OPTIMIZE TABLE `'.$this->table.'`';
				$this->sql->Query($this->database, $query);
			}
			catch (Exception $e)
			{
				$result = FALSE;
			}
			
			return($result);
			
		}
		
		/**
		 * Retrieves a list of events in the event log that are stats
		 * for the given application ID.
		 *
		 * @param int $application_id
		 */
		public function Get_Stat_Events($application_id)
		{
			// Check that we've set the table name
			$this->tableName($application_id);
			
			$ret_val = array();
			
			$query = "
				SELECT
					el.event_id,
					e.event
				FROM
					$this->table el
					JOIN `events` e ON el.event_id = e.event_id
				WHERE
					el.application_id = $application_id
					AND e.event LIKE 'STAT_%'";
			
			try
			{
				$result = $this->sql->Query($this->database, $query);
				
				while(($row = $this->sql->Fetch_Object_Row($result)))
				{
					$ret_val[$row->event_id] = $row->event;
				}
			}
			catch(Exception $e)
			{
				$ret_val = false;
			}
			
			return $ret_val;
			
		}
}

?>
