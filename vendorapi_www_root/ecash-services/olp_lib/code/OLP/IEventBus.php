<?php

/**
 * Interface which describes an event bus for OLP.
 * 
 * @package OLP
 * @subpackage EventBus
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
interface OLP_IEventBus
{
	/**
	 * Subscribes a target object ($subscriber) to a particular event type on the bus.
	 *
	 * @param string $event_type The getType() value of any OLP_IEvent objects
	 * which, when sent via notify() on the bus, will propogate to the subscriber.
	 * @param OLP_ISubscriber $subscriber The object (implementing OLP_ISubscriber)
	 * which will be made aware of events with type $event_type.
	 * @return void
	 */
	public function subscribeTo($event_type, OLP_ISubscriber $subscriber);
	
	/**
	 * Stops $subscriber from receiving OLP_IEvent notifications of type $event_type
	 *
	 * @param string $event_type The type of event the $subscriber is no longer
	 * interested in receiving (gotten from the OLP_IEvent::getType() method).
	 * @param OLP_ISubscriber $subscriber The subscriber who is no longer
	 * interested in OLP_IEvent objects with the type $event_type.
	 * @return void
	 */
	public function unsubscribeFrom($event_type, OLP_ISubscriber $subscriber);
	
	/**
	 * No longer propogate ANY events to this subscriber object.
	 *
	 * @param OLP_ISubscriber $subscriber The subscriber object which is no
	 * longer interested in any events.
	 * @return void
	 */
	public function unsubscribe(OLP_ISubscriber $subscriber);
	
	/**
	 * Send this event to all subscribers of the bus interested in the type of 
	 * event that $event is. (gotten via getType())
	 *
	 * @param OLP_IEvent $event The event to tell subscribers about.
	 * @return void
	 */
	public function notify(OLP_IEvent $event);
}

?>