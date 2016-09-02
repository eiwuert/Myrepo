<?php

/**
 * An object which keeps an internal timer and then, when that timeout is reached
 * and it receives a notification, sends out a timeout event.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLP
 * @subpackage EventBus
 */
class OLP_Subscriber_Timeout implements OLP_ISubscriber
{
	/**
	 * The bus to send/receive things on 
	 *
	 * @var OLP_IEventBus
	 */
	protected $bus;
	
	/**
	 * The event to send when the timeout is reached (upon receiving a notify).
	 *
	 * @var OLP_IEvent
	 */
	protected $event;
	
	/**
	 * Length of time, in seconds, from start until this object "times out."
	 *
	 * @var float
	 */
	protected $timeout;
	
	/**
	 * List of events to subscribe to.
	 *
	 * @var array
	 */
	protected $events = array();
	
	/**
	 * Time this object's start() method was called.
	 *
	 * @var float
	 */
	protected $start_time;
		
	/**
	 * Create a timeout object which subscribes to a bus and sends a timeout event
	 * when the $timeout amount of seconds has been reached after calling start().
	 * 
	 * @param OLP_IEvent $event The event to send when the timeout is reached
	 * (and a notify is sent to us.)
	 * @param float $timeout Amount of seconds (after calling start()) that will
	 * be waited (at least) until the timeout event is sent.
	 */
	public function __construct(OLP_IEvent $event, $timeout = 1)
	{
		$this->event = $event;
		$this->timeout = floatval($timeout);
	}
	
	/**
	 * Listen for a list of events on the event bus.
	 *
	 * @param array $events List of event types to listen to (strings)
	 * @return OLP_Subscriber_Timeout $this object.
	 */
	public function listenFor(array $events)
	{
		$this->events = $events;
		return $this;
	}
	
	/**
	 * Set the EventBus to listen to.
	 *
	 * This will replace any existing bus this object is listening to.
	 * 
	 * @param OLP_IEventBus $bus The bus to listen to.
	 * @return OLP_Subscriber_Timeout $this object
	 */
	public function listenTo(OLP_IEventBus $bus)
	{
		if ($this->bus instanceof OLP_IEventBus)
		{
			$this->bus->unsubscribe($this);
		}
		
		$this->bus = $bus;
		
		return $this;
	}
	
	/**
	 * Begin the timer which will eventually trigger the timeout event.
	 *
	 * @return void
	 */
	public function start()
	{
		$this->start_time = microtime(TRUE);
		
		if ($this->bus instanceof OLP_IEventBus)
		{
			foreach ($this->events as $event_type)
			{
				$this->bus->subscribeTo($event_type, $this);
			}
		}
	}
	
	/**
	 * 
	 * @param OLP_IEvent $event The event that "happened." 
	 * @return void 
	 * @see OLP_ISubscriber::notify()
	 */
	public function notify(OLP_IEvent $event)
	{
		if ($event->getType() == $this->event->getType()) return;
		
		$now = microtime(TRUE);
		
		if (($now - $this->start_time) > $this->timeout)
		{
			$this->bus->unsubscribe($this);
			$this->sendTimeoutEvent();
		}
	}
	
	/**
	 * Actually notifies the bus with a timeout event.
	 *
	 * @return void
	 */
	protected function sendTimeoutEvent()
	{
		$this->bus->notify($this->event);
	}
	
	/**
	 * String representation of this object with an emphasis on readability.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return sprintf('[%s timeout:%s listening for %s events]', 
			get_class($this),
			$this->timeout,
			count($this->events)
		);
	}
}

?>