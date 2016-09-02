<?php

/**
 * Shared fixture factory class for EventBus subpackage.
 * 
 * @package OLPBlackbox
 * @subpackage EventBus
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Test_EventFixtureFactory 
{
	/**
	 * Returns a new OLPBlackbox_Config object with an OLP_EventBus in it, 
	 * accesible as the "event_bus" property.
	 *
	 * @return OLPBlackbox_Config
	 */
	public function freshConfigWithEventBus()
	{
		$config = new OLPBlackbox_Config();
		$config->event_bus = new OLP_EventBus();
		return $config;
	}
	
	/**
	 * Returns a config with an event bus which will log to $filename.
	 *
	 * @param string $filename The file to write the event bus log to.
	 * @return OLPBlackbox_Config
	 */
	public function freshConfigWithLoggingEventBus($filename)
	{
		$config = new OLPBlackbox_Config();
		$config->event_bus = new OLP_LoggingEventBus(
			new OLP_EventBus_FileEventLogger($filename)
		);
		return $config;
	}
	
	/**
	 * Creates a new Subscriber which will subscribe to all OLPBlackbox_Event 
	 * events sent to it and retain those events to be examined later.
	 *
	 * @param OLP_IEventBus $bus
	 * @return OLP_Test_CollectingSubscriber
	 */
	public function freshCollectorForEventsOn(OLP_IEventBus $bus)
	{
		$collector = new OLP_Test_CollectingSubscriber();
		$collector->subscribeToClassEvents('OLPBlackbox_Event', $bus);
		return $collector;
	}
}

?>