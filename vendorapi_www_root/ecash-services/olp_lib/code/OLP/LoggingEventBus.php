<?php

/**
 * A version of the OLP_EventBus which logs interesting actions to a logger object.
 * 
 * "Interesting" being... subscribers subscribe, events are sent, subscribers 
 * unsubscribe, that kind of thing. Essentially everything on the public interface
 * for OLP_IEventBus should be logged.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLP
 * @subpackage EventBus
 */
class OLP_LoggingEventBus extends OLP_EventBus
{
	/**
	 * The object we'll log out to.
	 *
	 * @var Log_ILog_1
	 */
	protected $logger;
	
	/**
	 * Create a logging event bus which will record it's major actions.
	 * @param Log_ILog_1 $logger 
	 * @return void
	 */
	function __construct(Log_ILog_1 $logger)
	{
		$this->logger = $logger;
	}
	
	/**
	 * @param OLP_IEvent $event The event to tell subscribers about. 
	 * @see OLP_IEventBus::notify()
	 * @return void
	 */
	public function notify(OLP_IEvent $event)
	{
		$this->logger->write("RECEIVED " . $this->eventToString($event));
		parent::notify($event);
	}
	
	/**
	 * @see OLP_EventBus::notify()
	 * @param OLP_IEvent $event
	 * @param OLP_ISubscriber $subscriber
	 * @return void
	 */
	protected function notifySubscriber(OLP_IEvent $event, OLP_ISubscriber $subscriber)
	{
		$this->logger->write("\tNOTIFYIING " 
			. $this->subscriberToString($subscriber) . " OF " 
			. $this->eventToString($event));
		parent::notifySubscriber($event, $subscriber);
	}
	
	/**
	 * @param string $event_type The getType() value of any OLP_IEvent objects
	 * which, when sent via notify() on the bus, will propogate to the subscriber. 
	 * @param OLP_ISubscriber $subscriber The object (implementing OLP_ISubscriber)
	 * which will be made aware of events with type $event_type. 
	 * @return void 
	 * @see OLP_IEventBus::subscribeTo()
	 */
	public function subscribeTo($event_type, OLP_ISubscriber $subscriber)
	{
		$this->logger->write($this->subscriberToString($subscriber) 
			. " SUBSCRIBES TO $event_type EVENTS");
		parent::subscribeTo($event_type, $subscriber);
	}
	
	/**
	 * @param OLP_ISubscriber $subscriber The subscriber object which is no
	 * longer interested in any events. 
	 * @return void 
	 * @see OLP_IEventBus::unsubscribe()
	 */
	public function unsubscribe(OLP_ISubscriber $subscriber)
	{
		$this->logger->write($this->subscriberToString($subscriber) . " UNSUBSCRIBES");
		parent::unsubscribe($subscriber);
	}
	
	/**
	 * @param string $event_type The type of event the $subscriber is no longer
	 * interested in receiving (gotten from the OLP_IEvent::getType() method). 
	 * @param OLP_ISubscriber $subscriber The subscriber who is no longer
	 * interested in OLP_IEvent objects with the type $event_type. 
	 * @return void 
	 * @see OLP_IEventBus::unsubscribeFrom()
	 */
	public function unsubscribeFrom($event_type, OLP_ISubscriber $subscriber)
	{
		$this->logger->write($this->subscriberToString($subscriber) 
			. " UNSUBSCRIBES FROM $event_type EVENTS");
		parent::unsubscribeFrom($event_type, $subscriber);
	}
	
	/**
	 * Takes an event and gets a string representation for it for logging purposes.
	 *
	 * @param OLP_IEvent $event The event we want to represent as a string.
	 * @return string
	 */
	protected function eventToString(OLP_IEvent $event)
	{
		if (method_exists($event, '__toString'))
		{
			return $event->__toString();
		}
		else
		{
			return sprintf('[%s %s]', get_class($event), $event->getType());
		}
	}
	
	/**
	 * Take an OLP_ISubscriber and describe it as a string.
	 *
	 * @param OLP_ISubscriber $subscriber The subscriber to turn into a string.
	 * @return string
	 */
	protected function subscriberToString(OLP_ISubscriber $subscriber)
	{
		if (method_exists($subscriber, '__toString'))
		{
			$str = strval($subscriber);
			if (strlen($str) < 255)
			{
				return $str;
			}
		}
		
		return sprintf('[Subscriber (Class %s) (ID %s)]', 
			get_class($subscriber), 
			spl_object_hash($subscriber)
		);
	}
}

?>