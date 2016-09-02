<?php
/**
 * Blackbox_StateData class definition.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */

/**
 * Base class for Blackbox StateData subclasses with basic functionality for storing key/values.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com> 
 */
class Blackbox_StateData implements Blackbox_IStateData
{
	/**
	 * Actual information for the state object as key/values.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Keys that are allowed to be set at any time, can be extended by children.
	 *
	 * @var array list of strings representing keys
	 */
	protected $mutable_keys = array();

	/**
	 * Keys that are not allowed to be changed other than in the constructor, can be extended by children.
	 *
	 * @var array list of strings representing keys
	 */
	protected $immutable_keys = array();

	/**
	 * List of StateData objects that should be checked for data after this object.
	 * 
	 * @var array list of Blackbox_IStateData objects.
	 */
	protected $data_objects = array();

	/**
	 * Construct a Blackbox_StateData object, accepting parameters to initialize the object's data.
	 * 
	 * @param array $data associative array of information to be available through the state object
	 */
	public function __construct($data = NULL)
	{
		$this->immutable_keys[] = 'name';
		$this->initData($data);
	}

	/**
	 * Initializes data, this function should only be called by the constructor as it's the only place immutable data is set.
	 *
	 * This function pays no heed to immutable/mutable state, but it does check that the
	 * keys being passed in are valid to be set on this object.
	 *
	 * @param array $data associative array of keys/values to set for the StateData object.
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return void
	 */
	protected function initData($data = NULL)
	{
		if (is_null($data)) return;

		if (!is_array($data) && !$data instanceof Iterator)
		{
			throw new InvalidArgumentException("Data must be an associative array or iterable.");
		}

		// this is the only place immutable data can be set
		foreach ($data as $key => $value)
		{
			if (!is_string($key) || $key == '')
			{
				throw new InvalidArgumentException("key must be string");
			}

			if (!in_array($key, $this->mutable_keys) && !in_array($key, $this->immutable_keys))
			{
				throw new InvalidArgumentException("key $key is not allowed to be added.");
			}

			$this->data[$key] = $value;
		}
	}

	/**
	 * Public setter for data properties.
	 *
	 * @param string $key property name
	 * @param mixed $value what to set the property to, should most likely be a string
	 * @throws InvalidArgumentException
	 * 
	 * @return void
	 */
	public function __set($key, $value)
	{
		if (!in_array($key, $this->mutable_keys))
		{
			foreach ($this->data_objects as $state_data)
			{
				try
				{
					$state_data->$key = $value;
					return;
				}
				catch (InvalidArgumentException $e)
				{
					/**
					 * Don't do anything. We'll throw the exception below if we go through all of
					 * our state data objects and don't find one that we can set.
					 */
				}
			}
			throw new InvalidArgumentException("$key is not allowed or not mutable.");
		}

		$this->data[$key] = $value;
	}

	/**
	 * Get a publicly accessible property of this state object.
	 * 
	 * @param string $key property to get
	 * 
	 * @return mixed value of the property, probably string
	 */
	public function __get($key)
	{
		if (isset($this->data[$key])) return $this->data[$key];

		foreach ($this->data_objects as $state_data)
		{
			if (isset($state_data->$key))
			{
				return $state_data->$key;
			}
		}
		
		return NULL;
	}

	/**
	 * Returns a combined version of the key passed in.
	 * 
	 * Normally, state data is accessed via __get() which finds the first version
	 * of the key asked for in the 'stack' of state data. For loan_actions,
	 * however, the end result was required to be all loan actions throughout
	 * the state data. (See GForge#20444)
	 * 
	 * @param string $key The key to retrieve from this state data object.
	 * @return Blackbox_StateData_ICombineKey item.
	 */
	public function getCombined($key)
	{
		$combine_key = NULL;

		if (isset($this->data[$key]))
		{
			if ($this->data[$key] instanceof Blackbox_StateData_ICombineKey)
			{
				$combine_key = $this->data[$key];
			}
			else 
			{
				throw new Blackbox_Exception(sprintf(
					'cannot combine key %s',
					strval($key))
				);
			}
		}

		foreach ($this->data_objects as $state_data)
		{
			if ($combine_key)
			{
				$other = $state_data->getCombined($key);
				if ($other instanceof Blackbox_StateData_ICombineKey)
				{
					$combine_key = $combine_key->combine($other);
					unset($other);
				}
			}
			else 
			{
				return $state_data->getCombined($key);
			}
		}

		return $combine_key;
	}

	/**
	 * Allow isset($this->key) to be called by everyone.
	 * 
	 * @param string $key key to look for.
	 * 
	 * @return bool item is set or not within this data object
	 */
	public function __isset($key)
	{
		if (isset($this->data[$key]))
		{
			return TRUE;
		}

		foreach ($this->data_objects as $state_data)
		{
			if (isset($state_data->$key))
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}

	/**
	 * Adds a StateData object to the internal list to be checked for data.
	 * 
	 * @param object $data Blackbox_IStateData object
	 *
	 * @return void
	 */
	public function addStateData(Blackbox_IStateData $data)
	{
		$this->data_objects[] = $data;
	}

	/**
	 * Allows users to determine if the key they are attempting to set is allowed.
	 *
	 * @return array
	 */
	public function getMutableKeys()
	{
		return $this->mutable_keys;
	}
}
?>
