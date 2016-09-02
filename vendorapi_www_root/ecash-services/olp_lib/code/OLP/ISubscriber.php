<?php

interface OLP_ISubscriber
{
	/**
	 * Notify the subscriber of an event which took place.
	 *
	 * @param OLP_IEvent $event The event that "happened."
	 * @return void
	 */
	public function notify(OLP_IEvent $event);
}

?>