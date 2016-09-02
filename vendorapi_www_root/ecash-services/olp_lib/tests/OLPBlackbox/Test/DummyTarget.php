<?php

/**
 * Target "mock" which can be commanded (:D) to perform different behaviors.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Test_DummyTarget extends Object_1 implements Blackbox_ITarget
{
	/**
	 * Attributes to control the behavior of this dummy target.
	 *
	 * @var array
	 */
	public $attrs = array();
	
	/**
	 * Whether isValid has been run on this.
	 *
	 * @var bool
	 */
	protected $isvalid_was_run = FALSE;
	
	/**
	 * Create a dummy object with behavior that is controllable.
	 * @param array List of attributes to start this test target with.
	 * @return void
	 */
	public function __construct(array $attrs = array())
	{
		if (!array_key_exists('isvalid_result', $attrs))
		{
			$attrs['isvalid_result'] = TRUE;
		}

		foreach (array('weight', 'current_leads') as $attr)
		{
			if (!array_key_exists($attr, $attrs))
			{
				$attrs[$attr] = 0;
			}
		}
		
		$this->attrs = $attrs;
	}
	
	/**
	 * 
	 * @return Blackbox_StateData 
	 * @see Blackbox_ITarget::getStateData()
	 */
	public function getStateData()
	{
		return new OLPBlackbox_StateData();
	}
	
	/**
	 * 
	 * @param Blackbox_Data $data Data to run validation checks on 
	 * @param Blackbox_StateData $state_data state data to do validation on 
	 * @return bool 
	 * @see Blackbox_ITarget::isValid()
	 */
	public function isValid(Blackbox_Data $data, Blackbox_IStateData $state_data)
	{
		if ($this->get('isvalid_timeout'))
		{
			$this->sendEvent($this->getTimeoutEvent());
		}
		
		$this->isvalid_was_run = TRUE;
		
		return $this->get('isvalid_result');
	}
	
	/**
	 * Reports whether isValid() has been run on this object.
	 *
	 * @return bool
	 */
	public function isValidWasRun()
	{
		return $this->isvalid_was_run;
	}
	
	/**
	 * @param Blackbox_Data $data data to run any additional checks on 
	 * @return Blackbox_IWinner|bool 
	 * @see Blackbox_ITarget::pickTarget()
	 */
	public function pickTarget(Blackbox_Data $data)
	{
		if ($this->get('picktarget_timeout'))
		{
			$this->sendEvent($this->getTimeoutEvent());
		}
		return $this->get('pick_fail') ? FALSE : new OLPBlackbox_Winner($this);
	}
	
	/**
	 * @param Blackbox_IRule $rules the rules to run on the target 
	 * @return void 
	 * @see Blackbox_ITarget::setRules()
	 */
	public function setRules(Blackbox_IRule $rules)
	{
		// pass, not used, currently.
	}
	
	/**
	 * needed by pickers.
	 *
	 * @return string The "name" of this object.
	 */
	public function getName()
	{
		return substr(spl_object_hash($this), 0, 5);
	}
	
	/**
	 * Required for being picked by pickers.
	 *
	 * @return int
	 */
	public function getWeight()
	{
		return $this->get('weight');
	}
	
	/**
	 * Required for being picked by pickers.
	 *
	 * @return int
	 */
	public function getCurrentLeads()
	{
		return $this->get('current_leads');
	}
	
	/**
	 * Used by collections to print this object out, mostly.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return sprintf('[%s %s]', get_class($this), spl_object_hash($this));
	}
	
	/**
	 * Convenience getter function to not send notices when getting a property.
	 *
	 * @param string $key The property (held in publicly accessible attrs) to 
	 * retrieve
	 * @return mixed NULL if the key is not defined, otherwise the content of
	 * $this->attrs[$key]
	 */
	protected function get($key)
	{
		return array_key_exists($key, $this->attrs) ? $this->attrs[$key] : NULL;
	}
	
	/**
	 * Returns an OLP EventBus blackbox timeout event.
	 *
	 * @return OLPBlackbox_Event
	 */
	protected function getTimeoutEvent()
	{
		return new OLPBlackbox_Event(
			OLPBlackbox_Event::EVENT_BLACKBOX_TIMEOUT,
			array(OLPBlackbox_Event::ATTR_SENDER_HASH => spl_object_hash($this))
		);
	}
	
	/**
	 * Send an event if it's possible given our configuration.
	 *
	 * @param OLP_IEvent $event The event to send along the bus. (An OLP_IEventBus)
	 * @return void
	 */
	protected function sendEvent(OLP_IEvent $event)
	{
		if ($this->get('config') instanceof OLPBlackbox_Config
			&& $this->get('config')->event_bus instanceof OLP_IEventBus)
		{
			$this->get('config')->event_bus->notify($event);
		}
	}
}

?>