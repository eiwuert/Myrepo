<?php

/**
 * The interface for event timers.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
interface OLP_IEventTimer
{
	/**
	 * Starts a timer for an event.
	 *
	 * @param string $event
	 * @param string $environment
	 * @param int $timestamp
	 * @return bool
	 */
	public function startEvent($event, $environment, $timestamp = NULL);
	
	/**
	 * Ends a timer for an event
	 *
	 * @param string $event
	 * @param string $environment
	 * @param int $timestamp
	 * @return bool
	 */
	public function endEvent($event, $environment, $timestamp = NULL);
	
	/**
	 * Returns information about an event.
	 *
	 * @param string $event
	 * @param string $environment
	 * @return array
	 */
	public function getEventInformation($event, $environment);
}

?>
