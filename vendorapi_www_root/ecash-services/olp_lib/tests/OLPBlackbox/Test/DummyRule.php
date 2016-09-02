<?php

/**
 * Testing rule which will exhibit some particular behaviors.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Test_DummyRule implements Blackbox_IRule
{
	/**
	 * The settings which determine how this rule behaves.
	 *
	 * @var array
	 */
	public $settings;
	
	/**
	 * Whether isValid() was called on this rule.
	 *
	 * @var bool
	 */
	protected $was_run = FALSE;
	
	/**
	 * Create a "mock" rule class which will exhibit particular behaviors based
	 * on it's settings.
	 * @param array $settings Configuration array which determines how the fake
	 * rule behaves.
	 * @return void
	 */
	function __construct(array $settings = array())
	{
		if (!array_key_exists('result', $settings))
		{
			$settings['result'] = TRUE;
		}
		
		$this->settings = $settings;
	}
	
	/**
	 * 
	 * @param mixed $data The data used to validate the rule. 
	 * @param obj $state_data The mutable state data object for the ITarget running the rule. 
	 * @return bool 
	 * @see Blackbox_IRule::isValid()
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		$this->was_run = TRUE;
		
		if ($this->send_timeout && $this->eventBus())
		{
			$this->sendEvent($this->getTimeoutEvent());
		}
		return $this->result;
	}
	
	/**
	 * Whether or not this rule was run at all (i.e. whether isValid() was called.)
	 *
	 * @return bool TRUE indicates that isValid was called
	 */
	public function wasRun()
	{
		return $this->was_run;
	}
	
	/**
	 * Returns the configured event bus for this rule.
	 *
	 * @return OLP_IEventBus|NULL
	 */
	protected function eventBus()
	{
		if ( $this->blackbox_config instanceof OLPBlackbox_Config
			&& $this->blackbox_config->event_bus instanceof OLP_IEventBus)
		{
			return $this->blackbox_config->event_bus;
		}
		
		return NULL;
	}
	
	/**
	 * Send an eventbus event if we have a bus.
	 *
	 * @param OLPBlackbox_Event $event The event to send.
	 * @return void
	 */
	protected function sendEvent(OLPBlackbox_Event $event)
	{
		if ($this->eventBus()) $this->eventBus()->notify($event);
	}
	
	/**
	 * Returns a newly created timeout event for the bus.
	 *
	 * @return OLPBlackbox_Event
	 */
	protected function getTimeoutEvent()
	{
		return new OLPBlackbox_Event(OLPBlackbox_Event::EVENT_BLACKBOX_TIMEOUT);
	}
	
	/**
	 * Setter which will set "settings" variables, so this can be used to modify
	 * the behavior of the object.
	 *
	 * @param string $name The property to alter.
	 * @param string $value The value to set for the property.
	 * @return void
	 */
	public function __set($name, $value)
	{
		$this->settings[$name] = $value;
	}
	
	/**
	 * Gets a settings item. (Which are what defines the behavior of this mock.)
	 *
	 * @param string $name The property to get.
	 * @return mixed
	 */
	public function __get($name)
	{
		if (array_key_exists($name, $this->settings))
		{
			return $this->settings[$name];
		}
		
		return NULL;
	}
	
	/**
	 * Print out a string representation of this object with an emphasis on
	 * identification and readability.
	 * 
	 * Contains the class name and an spl_object_hash().
	 *
	 * @return string
	 */
	public function __toString()
	{
		return sprintf('[%s %s]', get_class($this), spl_object_hash($this));
	}
}

?>