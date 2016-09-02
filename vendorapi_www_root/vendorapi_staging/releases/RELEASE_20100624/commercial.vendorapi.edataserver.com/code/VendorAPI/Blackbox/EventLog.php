<?php

/**
 * Log events that occur during Blackbox processing
 *
 * This implements a long-overdue logging level; events over the
 * current level are not written to the database. This allows us
 * to increase verbosity while debugging, but keep things relatively
 * quiet for normal production use.
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
class VendorAPI_Blackbox_EventLog
{
	const FAIL = 1;
	const PASS = 2;
	const DEBUG = 3;
	const ALL = 0;

	/**
	 * Logging level; everything above this is not logged
	 * @var int
	 */
	protected $level = self::FAIL;

	/**
	 * @var VendorAPI_StateObject
	 */
	protected $state;

	/**
	 * @var int
	 */
	protected $application_id;

	/**
	 * @var string
	 */
	protected $target;

	/**
	 *
	 * @var array
	 */
	protected $eventlog_events = array();

	/**
	 * @param VendorAPI_StateObject $state
	 * @param int $application_id
	 * @param string $target
	 * @param int $level
	 */
	public function __construct(VendorAPI_StateObject $state, $application_id, $target, $level = NULL)
	{
		$this->state = $state;
		$this->application_id = $application_id;
		$this->target = $target;

		if ($level !== NULL) $this->setLevel($level);
	}

	/**
	 * Changes the logging level
	 * @param int $level
	 * @return void
	 */
	public function setLevel($level)
	{
		switch ($level)
		{
			case self::FAIL:
			case self::PASS:
			case self::DEBUG:
			case self::ALL:
				$this->level = $level;
		}
	}

	/**
	 * Logs an event
	 *
	 * @param string $event
	 * @param string $response
	 * @param int $level
	 * @return void
	 */
	public function logEvent($event, $response, $level = self::DEBUG)
	{
		if ($this->level === self::ALL
		 || $level <= $this->level)
		{
			$this->recordEvent($event, $response);
		}
	}

	/**
	 * Retrieves all of the eventlog events
	 */
	public function getEventlogEvents()
	{
		return $this->eventlog_events;
	}

	/**
	 * Retrieves eventlog target
	 */
	public function getTarget()
	{
		return $this->target;
	}

	/**
	 * Saves an event to the local storage variable
	 * @param array $data Event data
	 */
	protected function addEventlogEvent(array $data)
	{
		$this->eventlog_events[] = $data;
	}

	/**
	 * Adds the event to the state object
	 * @param string $mode
	 * @param string $event
	 * @param string $result
	 * @return void
	 */
	protected function recordEvent($event, $response)
	{
		$data = array(
			'date_created' => time(),
			'application_id' => $this->application_id,
			'target' => $this->target,
			'event' => $event,
			'response' => $response,
		);
		if (!$this->state->isMultiPart('eventlog'))
		{
			$this->state->createMultiPart('eventlog');
		}
		$this->state->eventlog[] = $data;
		$this->addEventlogEvent($data);
	}
}

?>
