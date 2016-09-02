<?php

/**
 * OLPBlackbox specific event, with constants for event types and stuff.
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package OLPBlackbox
 * @subpackage EventBus
 */
class OLPBlackbox_Event implements OLP_IEvent
{
	/**
	 * Events which can be sent.
	 * @var string
	 */
	const EVENT_VALIDATION_START = 'VALIDATION_START';
	const EVENT_VALIDATION_END = 'VALIDATION_END';
	const EVENT_PICK_START = 'PICK_START';
	const EVENT_PICK_END = 'PICK_END';
	const EVENT_BLACKBOX_TIMEOUT = 'BLACKBOX_TIMEOUT';
	const EVENT_NEXT_RULE = 'NEXT_RULE';
	const EVENT_GLOBAL_MILITARY_FAILURE = 'GLOBAL_MILITARY_FAILURE';
	
	/**
	 * Sender types. {@see OLPBlackbox_Event::SENDER_TYPE}
	 * @var string
	 */
	const TYPE_TARGET = 'TYPE_TARGET';
	const TYPE_RULE = 'TYPE_RULE';
	const TYPE_CAMPAIGN = 'TYPE_CAMPAIGN';
	const TYPE_RULE_COLLECTION = 'TYPE_RULE_COLLECTION';
	const TYPE_TARGET_COLLECTION = 'TYPE_TARGET_COLLECTION';
	const TYPE_LENDERAPI_RESPONSE = 'TYPE_LENDERAPI_RESPONSE';

	/**
	 * Various event attributes which can be sent in events.
	 * @var string
	 */
	const ATTR_SENDER = 'SENDER_TYPE';
	const ATTR_SENDER_HASH = 'SENDER_HASH';
	
	/**
	 * The attributes of this event.
	 *
	 * @var array
	 */
	protected $attrs;
	
	/**
	 * Used to get different types of constants dynamically.
	 * 
	 * @var ReflectionClass
	 */
	static protected $reflection;
	
	/**
	 * @see OLP_IEvent
	 * @param string $event_type The type of even this is.
	 * @param array $attrs List of extra items for the event.
	 */
	public function __construct($event_type, $attrs = array())
	{
		$attrs['type'] = $event_type;
		$this->attrs = $attrs;
	}
	
	/**
	 * Required for the OLP_IEvent interface.
	 * @return string|int Identifier for this event type. 
	 * @see OLP_IEvent::getType()
	 */
	public function getType()
	{
		return $this->type;
	}
	
	/**
	 * Gets an attribute of this event.
	 * 
	 * @param string $name The attribute name to get.
	 * @return mixed The value of the attribute.
	 */
	public function __get($name)
	{
		if (array_key_exists($name, $this->attrs))
		{
			return $this->attrs[$name];
		}
		
		return NULL;
	}

	/**
	 * Sets an attribute {@see OLPBlackbox_Event::attrs}.
	 *
	 * @throws InvalidArgumentException
	 * @param string $name The name of the attr to set.
	 * @param mixed $value The value to set the attr to.
	 * @return void
	 */
	public function __set($name, $value)
	{
		if ($name == 'type')
		{
			throw new InvalidArgumentException('the type of this event is immutable.');
		}
		
		$this->attrs[$name] = $value;
	}
	
	/**
	 * Checks to see if an attr is set.
	 *
	 * @see OLPBlackbox_Event::attrs
	 * @param string $name
	 * @return mixed Whether the attr is set or not.
	 */
	public function __isset($name)
	{
		return isset($this->attrs[$name]);
	}
	
	/**
	 * Returns all attrs set on this object. (Including Type!)
	 *
	 * @return array
	 */
	public function getAttrs()
	{
		return $this->attrs;
	}
	
	/**
	 * @return array
	 */
	public static function getConstantEventTypes()
	{
		if (!self::$reflection instanceof ReflectionClass)
		{
			self::$reflection = new ReflectionClass(__CLASS__);
		}
		
		$event_types = array();
		
		foreach (self::$reflection->getConstants() as $name => $value)
		{
			if (preg_match('/EVENT_/i', $name))
			{
				$event_types[] = $value;
			}
		}
		
		return $event_types;
	}
	
	/**
	 * Human readable version of this event.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$attrs = array();
		foreach ($this->attrs as $key => $val)
		{
			// type is required and will be printed regardless
			if (strcasecmp($key, 'type') == 0) continue;
			if (is_array($val) || is_object($val))
			{
				$val = serialize($val);
			}
			$attrs[] = sprintf('%s: %s', $key, $val);
		}
		
		$attrs = count($attrs) ? ' ' . implode(' ', $attrs) : '';
		
		return sprintf('[%s %s%s]', get_class($this), $this->getType(), $attrs);
	}
}

?>
