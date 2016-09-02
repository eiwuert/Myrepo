<?php

/**
 * A OLP_ISubscriber which collects all events it's sent.
 * 
 * Also has a convenience method for subscribing to all events offered by a class
 * via constants {@see OLP_Test_CollectingSubscriber::subscribeToClassEvents()}
 *
 * @package OLP
 * @subpackage EventBus
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLP_Test_CollectingSubscriber implements OLP_ISubscriber
{
	/**
	 * Event object containing all events this subscriber has received.
	 *
	 * @var ArrayObject
	 */
	protected $events = array();
	
	/**
	 * Accepts a class with CONSTANT event names defined (e.g. OLPBlackbox_Event)
	 * and then subscribes to each of these event types.
	 * 
	 * Granted, this means this subscriber might be "subscribed" to a lot of
	 * events which will never actually fire depending on the constants of the 
	 * class... but that's probably not a big deal. If it becomes one, refactor.
	 *
	 * @param string $class Must be the name of a class which exists.
	 * @param OLP_IEventBus $bus The bus to subscribe to.
	 * @return void
	 */
	public function subscribeToClassEvents($class, OLP_IEventBus $bus)
	{
		try
		{
			$ref = new ReflectionClass($class);
		}
		catch (ReflectionException $e)
		{
			throw new InvalidArgumentException(
				"unable to reflect class " . var_export($class, TRUE) 
				. ": " . $e->getMessage()
			);
		}
		
		foreach ($ref->getConstants() as $const_value)
		{
			$bus->subscribeTo($const_value, $this);
		}
	}
	
	/**
	 * Notifies this object of an event, which this object will then store.
	 * 
	 * @param OLP_IEvent $event The event that "happened." 
	 * @return void 
	 * @see OLP_ISubscriber::notify()
	 */
	public function notify(OLP_IEvent $event)
	{
		$this->events[] = $event;
	}
	
	/**
	 * Returns the events this object has collected.
	 *
	 * @return array
	 */
	public function getEvents()
	{
		return $this->events;
	}
	
	/**
	 * Finds all events this object has received which have the attributes specified.
	 *
	 * @param array $attrs The attributes we're searching for each event to have.
	 * @param bool $strict FALSE if we'd like to find events which match the 
	 * $attrs parameter and don't care about extra attributes on the events, or
	 * TRUE if the ONLY attributes the events found may have are those specified
	 * in $attrs.
	 * @return array List of events found.
	 */
	public function findEventsByAttributes(array $attrs, $strict = FALSE)
	{
		$found = array();
		
		foreach ($this->events as $event)
		{
			if ($strict && count($attrs) != count($event->getAttrs())) continue;
			
			$same_entries = array_intersect_assoc($attrs, $event->getAttrs());
			
			if (count($same_entries) == count($attrs))
			{
				$found[] = $event;
			}
		}
		return $found;
	}
}

?>