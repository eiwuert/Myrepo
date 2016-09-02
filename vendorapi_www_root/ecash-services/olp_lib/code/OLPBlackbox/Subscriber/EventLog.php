<?php

/**
 * Receives any OLP_IEventBus events and logs them to the OLP event log.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 * @subpackage EventBu
 */
class OLPBlackbox_Subscriber_EventLog extends Object_1 implements OLP_ISubscriber
{
	/**
	 * The name of the result to log to the event log.
	 *
	 * Normally, the result is like "PASS" or "FAIL" but in this case, 
	 * anything we log is because we received a notification.
	 * 
	 * @var string
	 */
	const EVENT_RESULT = 'RECEIVED';
	
	/**
	 * The event log to log bus events to.
	 *
	 * @var Event_Log
	 */
	protected $event_log;
	
	/**
	 * The application ID to log events for.
	 *
	 * @var int
	 */
	protected $application_id;
	
	/**
	 * The mode that blackbox is running in (BROKER, etc.)
	 *
	 * @var string
	 */
	protected $mode;
	
	/**
	 * @param Event_Log $event_log OLP event log object.
	 * @param int $application_id The application ID to use logging events.
	 * @param string $mode The mode (such as BROKER or PREQUAL) to use when
	 * logging events.
	 */
	public function __construct($event_log, $application_id, $mode)
	{
		if (!method_exists($event_log, 'Log_Event'))
		{
			throw new InvalidArgumentException(
				get_class($this) . ' requires event log object with "Log_Event" method.'
			);
		}
		
		$this->event_log = $event_log;
		$this->application_id = $application_id;
		$this->mode = $mode;
	}
	
	/**
	 * @param OLP_IEvent $event The event that "happened." 
	 * @return void 
	 * @see OLP_ISubscriber::notify()
	 */
	public function notify(OLP_IEvent $event)
	{
		$this->event_log->Log_Event(
			$event->getType(),
			self::EVENT_RESULT,
			NULL,	// target_id
			$this->application_id,
			$this->mode
		);
	}
}

?>