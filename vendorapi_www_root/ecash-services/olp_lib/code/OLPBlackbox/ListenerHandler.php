<?php
class OLPBlackbox_ListenerHandler
{
	protected $children = array();
	protected $listeners = array();
	/**
	 * @var OLP_IEventBus
	 */
	protected $eventbus;
	
	/**
	 * Add an event bus to this object
	 * @param OLP_IEventBus $eventbus
	 */
	public function __construct(OLP_IEventBus $eventbus)
	{
		$this->eventbus = $eventbus;
	}
	
	/**
	 * Registers a child that may potentially accept a listener
	 * at a later date.
	 * @param string $key
	 * @param string $type
	 * @param mixed $child
	 * @return void
	 */
	public function registerChild($key, $type, $child)
	{
		$key = $this->normalize($key);
		$type = $this->normalize($type);
		if (!is_array($this->children[$type])) 
		{
			$this->children[$type] = array();
		}
		$this->children[$type][$key] = $child;
	}
	
	/**
	 * Register a listener to be attached at a later
	 * time.
	 * @param string $key
	 * @param string $type
	 * @param OLPBlackbox_IListener $listener
	 * @return void
	 */
	public function registerListenerAttachment($key, $type, OLPBlackbox_IListener $listener)
	{
		$key = $this->normalize($key);
		$type = $this->normalize($type);
		if (!is_array($this->listeners[$type]))
		{
			$this->listeners[$type] = array();
		}
		if (!is_array($this->listeners[$type][$key]))
		{
			$this->listeners[$type][$key] = array();
		}
		$this->listeners[$type][$key][] = $listener;
	}
	
	/**
	 * Attach listeners to the apprioate children. If type is NULL, 
	 * it'll attach all registered listeners. Otherwise, only those of
	 * the particular type.
	 * @param string|NULL $type
	 * @return unknown_type
	 */
	public function attachListeners($type = NULL)
	{
		if (empty($type))
		{
			$types = array_keys($this->listeners);
			foreach ($types as $type)
			{
				$this->attachListenerType($type);
			}
		}
		else
		{
			$type = $this->normalize($type);
			$this->attachListenerType($type);
		}
	}
	
	/**
	 * Attaches a listener of one specific type.
	 * @param string $type
	 * @return void
	 */
	protected function attachListenerType($type)
	{
		$type = $this->normalize($type);
		if (is_array($this->listeners[$type]))
		{
			foreach ($this->listeners[$type] as $key => $listeners)
			{
				foreach ($listeners as $listener)
				{
					if (isset($this->children[$type]) && isset($this->children[$type][$key]))
					{
						$listener->setChild($this->children[$type][$key]);
						$listener->subscribeToEvents($this->eventbus);
					}
				}
			}
		}
	}
	
	/**
	 * normalizes a string
	 * @param string $str
	 * @return string
	 */
	protected function normalize($str)
	{
		return trim(strtolower($str));
	}
}