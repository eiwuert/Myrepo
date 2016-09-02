<?php

/**
 * Implementation of OLP_IEventBus, which can handle passing event objects to 
 * subscribers.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLP
 * @subpackage EventBus
 */
class OLP_EventBus implements OLP_IEventBus
{
	/**
	 * List of OLP_ISubscribers, waiting for notifications.
	 *
	 * @var array
	 */
	protected $subscribers = array();
	
	/**
	 * List of subscriptions... that is a list pointing from event types to the
	 * OLP_ISubscriber objects in {@see OLP_EventBus::subscribers} waiting for
	 * events.
	 *
	 * @var array
	 */
	protected $event_subscriptions = array();
	
	/**
	 * Implmenetation from OLP_IEventBus for when a subscriber wants to listen to
	 * a particular event type.
	 *
	 * @param string $event_type The type of event the subscriber would like to
	 * receive.
	 * @param OLP_ISubscriber $subscriber The subscriber which wants to listen.
	 * @return void
	 */
	public function subscribeTo($event_type, OLP_ISubscriber $subscriber)
	{
		$hash = $this->registerSubscriber($subscriber);
		$event_type = $this->normalizeEventType($event_type);
		
		if (empty($this->event_subscriptions[$event_type]))
		{
			$this->event_subscriptions[$event_type] = array();
		}
		
		if (!in_array($hash, $this->event_subscriptions[$event_type]))
		{
			$this->event_subscriptions[$event_type][] = $hash;
		}
	}
	
	/**
	 * Add a subscriber to the list of $this->subscribers and return the hash
	 * used to index it.
	 *
	 * @param OLP_ISubscriber $subscriber The subscriber to store in
	 * $this->subscribers
	 * @return string Hash used to store the subscriber.
	 */
	protected function registerSubscriber(OLP_ISubscriber $subscriber)
	{
		$hash = $this->getSubscriberHash($subscriber);
		
		if (!array_key_exists($hash, $this->subscribers))
		{
			$this->subscribers[$hash] = $subscriber;
		}
		elseif (!$this->subscribers[$hash] instanceof OLP_ISubscriber)
		{
			// we've detached this object before but now we're reattaching...
			// remove any extraneous event subscriptions
			$this->cleanEventSubscriptionsFor($hash);
			$this->subscribers[$hash] = $subscriber;
		}
		
		return $hash;
	}
	
	/**
	 * Iterate through our list of subscriptions and remove any subscriptions for
	 * the OLP_ISubscriber identified via $hash.
	 *
	 * @see OLP_EventBus::getSubscriberHash()
	 * @param string $hash The hash used to identify the OLP_ISubscriber
	 * @return void
	 */
	protected function cleanEventSubscriptionsFor($hash)
	{
		foreach (array_keys($this->event_subscriptions) as $event_type)
		{
			$hash_location = array_search($hash, $this->event_subscriptions[$event_type]);
			
			if (is_int($hash_location))
			{
				unset($this->event_subscriptions[$event_type][$hash_location]);
			}
		}
	}
	
	/**
	 * Obtain a hash, unique to the OLP_ISubscriber.
	 * 
	 * Used to store a reference to the subscriber.
	 *
	 * @param OLP_ISubscriber $subscriber The subscriber to build the hash from.
	 * @return string
	 */
	protected function getSubscriberHash(OLP_ISubscriber $subscriber)
	{
		return spl_object_hash($subscriber);
	}
	
	/**
	 * Indicate to $this object that the subscriber no longer wishes to receive
	 * events from it. 
	 *
	 * @param OLP_ISubscriber $subscriber The OLP_ISubscriber to cease sending
	 * events to.
	 * @return void
	 */
	public function unsubscribe(OLP_ISubscriber $subscriber)
	{
		$hash = $this->getSubscriberHash($subscriber);
		
		if (array_key_exists($hash, $this->subscribers))
		{
			// simply remove the subscribing object, leaves subscriptions, see registerSubscriber.
			$this->subscribers[$hash] = NULL;
		}
	}
	
	/**
	 * Indicate to $this object that the subscriber wishes to no longer receive 
	 * events of event type $event_type
	 *
	 * @param string $event_type The event type $subscriber is no longer interested in.
	 * @param OLP_ISubscriber $subscriber The object to no longer receive 
	 * $event_type events.
	 * @return void
	 */
	public function unsubscribeFrom($event_type, OLP_ISubscriber $subscriber)
	{
		$hash = $this->getSubscriberHash($subscriber);
		$event_type = $this->normalizeEventType($event_type);
		
		$hash_index = array_search($hash, $this->event_subscriptions[$event_type]);
		
		if (is_int($hash_index))
		{
			unset($this->event_subscriptions[$event_type][$hash_index]);
		}
	}
	
	/**
	 * Notify $this (bus) that an event should be propogated to subscribers
	 * interested in it.
	 *
	 * @param OLP_IEvent $event The event which should be sent to the subscribers.
	 * @return void
	 */
	public function notify(OLP_IEvent $event)
	{
		$event_type = $this->normalizeEventType($event->getType());
		
		if (empty($this->event_subscriptions[$event_type])) return;
		
		foreach ($this->event_subscriptions[$event_type] as $subscriber_hash)
		{
			if ($this->subscribers[$subscriber_hash] instanceof OLP_ISubscriber)
			{
				$this->notifySubscriber($event, $this->subscribers[$subscriber_hash]);
			}
		}
	}
	
	/**
	 * Overridable method to notify a subscriber that an event has happened.
	 *
	 * @param OLP_IEvent $event The event which should be sent to the $subscriber.
	 * @param OLP_ISubscriber $subscriber The subscriber who should receive
	 * the event. (notify() will be called on this object.)
	 * @return void
	 */
	protected function notifySubscriber(OLP_IEvent $event, OLP_ISubscriber $subscriber)
	{
		$subscriber->notify($event);
	}
	
	/**
	 * Normalize the event type.
	 *
	 * @param string $event_type
	 * @return string
	 */
	protected function normalizeEventType($event_type)
	{
		return strtoupper($event_type);
	}
}

?>
