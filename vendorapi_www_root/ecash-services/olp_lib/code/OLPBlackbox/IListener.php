<?php
/**
 * Interface for listeners that need to be 
 * registered for later acceptance of a child.
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
interface OLPBlackbox_IListener 
{
	/**
	 * Sets the child this listener is acting upon.
	 * @param mixed $child
	 * @return void
	 */
	public function setChild($child);
	
	/**
	 * Pass this listener and eventbus it can subscribe
	 * to events on.
	 * @param OLP_IEventBus $eventbus
	 * @return void
	 */
	public function subscribeToEvents(OLP_IEventBus $eventbus);
}