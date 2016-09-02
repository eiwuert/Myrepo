<?php

/** 
 * Adds cleanup for pickWinner in Blackbox.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLPBlackbox_Root extends Blackbox_Root implements OLPBlackbox_IRestorable
{
	/**
	 * Timeout object.
	 *
	 * @var OLP_Subscriber_Timeout
	 */
	protected $timeout_object;
	
	/**
	 * Logs bus events.
	 *
	 * @var OLPBlackbox_Subscriber_EventLog
	 */
	protected $event_log_subscriber;
	
	/**
	 * Create a new OLPBlackbox_Root object.
	 *
	 * @param Blackbox_IStateData $state_data The state to seed the blackbox with.
	 */
	public function __construct(Blackbox_IStateData $state_data = NULL)
	{
		$this->setupEventBus();
		parent::__construct($state_data);
	}
	
	/**
	 * Set a timeout object.
	 *
	 * @param float $timeout_seconds 
	 * @return void
	 */
	public function setTimeout($timeout_seconds)
	{
		if (!$this->getConfig()->event_bus instanceof OLP_IEventBus) return;
		
		
		$all_events = OLPBlackbox_Event::getConstantEventTypes();
		
		$this->timeout_object = new OLP_Subscriber_Timeout(
			$this->getTimeoutEvent(), $timeout_seconds
		);
		$this->timeout_object->listenTo($this->getConfig()->event_bus);
		$this->timeout_object->listenFor($all_events);
		$this->timeout_object->start();
	}
	
	/**
	 * Returns the Event Bus that this blackbox object is using, if any.
	 * 
	 * @return OLP_IEventBus|NULL
	 */
	public function getEventBus()
	{
		return $this->getConfig()->event_bus;
	}
	
	/**
	 * Returns a timeout event to send from a timeout subscriber.
	 *
	 * @see OLPBlackbox_Root::setTimeout()
	 * @return OLPBlackbox_Event
	 */
	protected function getTimeoutEvent()
	{
		return new OLPBlackbox_Event(OLPBlackbox_Event::EVENT_BLACKBOX_TIMEOUT);
	}
	
	/**
	 * Creates an event bus and puts it in the config.
	 *
	 * @return void
	 */
	protected function setupEventBus()
	{
		$this->getConfig()->event_bus = $this->getConfig()->event_bus_log_file 
			? $this->newLoggingEventBus($this->getConfig()->event_bus_log_file)
			: new OLP_EventBus();
	}
	
	/**
	 * Returns a new Logging event bus with a FileEventLogger as well.
	 *
	 * @param string $filename The file name prefix to log to (in /tmp)
	 * @return OLP_LoggingEventbus
	 */
	protected function newLoggingEventBus($filename)
	{
		return new OLP_LoggingEventBus(
			new OLP_EventBus_FileEventLogger(
				tempnam('tmp', $filename)
		));
	}
	
	/**
	 * Return the common config object that blackbox uses.
	 *
	 * @return OLPBlackbox_Config
	 */
	protected function getConfig()
	{
		return OLPBlackbox_Config::getInstance();
	}
	
	/** 
	 * Call observers when pickWinner returns.
	 *
	 * @param Blackbox_Data $data data to run Blackbox validation against
	 * @return Blackbox_IWinner|bool
	 */
	public function pickWinner(Blackbox_Data $data)
	{
		$this->setupEventLogSubscriber($data);
		
		try
		{
			$winner = parent::pickWinner($data);
		}
		catch (Exception $e)
		{
			// We want to rethrow any exceptions that occurred, but still run cleanup.
			$this->cleanUp($e);
			throw $e;
		}

		$this->cleanUp();

		return $winner;
	}
	
	/**
	 * Sets up an event log subscriber to log some bus events.
	 *
	 * @param OLPBlackbox_Data $data
	 * @return void
	 */
	protected function setupEventLogSubscriber(OLPBlackbox_Data $data)
	{
		$this->event_log_subscriber = new OLPBlackbox_Subscriber_EventLog(
			$this->getConfig()->event_log,
			$data->application_id,
			$this->getConfig()->blackbox_mode
		);
		$this->getConfig()->event_bus->subscribeTo(
			OLPBlackbox_Event::EVENT_BLACKBOX_TIMEOUT,
			$this->event_log_subscriber
		);
		$this->getConfig()->event_bus->subscribeTo(
			OLPBlackbox_Event::EVENT_GLOBAL_MILITARY_FAILURE,
			$this->event_log_subscriber
		);
	}

	/** 
	 * Clean up blackbox after we finish running.
	 *
	 * @param Exception $e An exception that occurred, if any.
	 * @return void
	 */
	protected function cleanUp(Exception $e = NULL)
	{
		$config = OLPBlackbox_Config::getInstance();
		
		if (!$e)
		{
			$queue = $this->state_data->deferred;
			$queue->executeAll();
		}
		
		$this->getConfig()->event_bus->unsubscribe($this->event_log_subscriber);
		unset($this->event_log_subscriber);
	}


	/**
	 * Unset a target from the target collection by property_short
	 *
	 * @param string $property_short
	 * @return TRUE/FALSE FALSE if the target was unable to be located, TRUE otherwise
	 */
	public function unsetTarget($property_short)
	{
		$target_location = $this->getTargetLocation($property_short);

		if (!$target_location)
		{
			return FALSE;
		}
		$target_location['collection']->unsetTargetIndex($target_location['index']);
		return TRUE;
	}

	/**
	 * Get the location of a target relative to its parent collection
	 *
	 * @param string $property_short
	 * @return array/FALSE ('collection','index') or FALSE if unable to locate
	 */
	public function getTargetLocation($property_short)
	{
		return $this->target_collection->getTargetLocation($property_short);
	}

	/**
	 * Returns a Blackbox_Target from the rule collection
	 *
	 * @param string $property_short
	 * @return Blackbox_Target/FALSE FALSE if unable to locate
	 */
	public function getTargetObject($property_short)
	{
		return $this->target_collection->getTargetObject($property_short);
	}

	/**
	 * Add a target to the main collection in the first position.
	 * @see OLPBlackbox_TargetCollection::prependTarget()
	 * @param Blackbox_ITarget $target The target to prepend.
	 * @return void
	 */
	public function prependTargetCollection(Blackbox_ITarget $target)
	{
		return $this->target_collection->prependTarget($target);
	}
	
	/**
	 * Get the current runtime state in an array
	 *
	 * @return array
	 */
	public function sleep()
	{
		$data = array(
			'root_collection' => $this->target_collection->sleep(),
			'state_data' => $this->state_data
		);
		return $data;
	}

	/**
	 * Restore the runtime state from a previous sleep 
	 *
	 * @param array $data Data to restore the object's state
	 * @return void
	 */
	public function wakeup(array $data)
	{
		$this->state_data = $data['state_data'];
		$this->target_collection->wakeup($data['root_collection']);
	}
}

?>
