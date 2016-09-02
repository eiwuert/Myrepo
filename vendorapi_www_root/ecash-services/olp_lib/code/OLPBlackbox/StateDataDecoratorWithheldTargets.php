<?php
/**
 * State data decorator that implements getting and setting withheld targets data within the state data
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
class OLPBlackbox_StateDataDecoratorWithheldTargets implements Blackbox_IStateData
{
	/**
	 * Withhheld targets array
	 *
	 * @var array
	 */
	protected $withheld_targets = array();

	/**
	 * Decorated state data object
	 *
	 * @var Blackbox_StateData
	 */
	protected $state_data;

	/**
	 * Constructor
	 *
	 * @param Blackbox_StateData $data
	 * @return void
	 */
	public function __construct(Blackbox_StateData $data)
	{
		$this->state_data = $data;
	}

	/**
	 * Adds a StateData object to the internal list to be checked for data
	 * completing the contract for Blackbox_IStateData
	 * 
	 * @param object $data Blackbox_IStateData object
	 * @return void
	 */
	public function addStateData(Blackbox_IStateData $data)
	{
		$this->state_data->addStateData($data);
	}

	/**
	 * Magical setter to set the local value or pass the request to the state data
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function __set($name, $value)
	{
		if ($name == 'withheld_targets')
		{
			$this->withheld_targets = $value;
		}
		else
		{
			$this->state_data->__set($name, $value);
		}
	}

	/**
	 * Magical getter to get the local value or pass the request to the state data
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if ($name == 'withheld_targets')
		{
			$value  = $this->withheld_targets;
		}
		else
		{
			$value = $this->state_data->__get($name);
		}
		return $value;
	}

	/**
	 * Magical getter to return TRUE for local values or pass the request to the state data
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		if ($name == 'withheld_targets')
		{
			$isset  = TRUE;
		}
		else
		{
			$isset = $this->state_data->__isset($name);
		}
		return $isset;
	}

	/**
	 * Allows users to determine if the key they are attempting to set is allowed.
	 *
	 * @return array
	 */
	public function getMutableKeys()
	{
		$keys = $this->state_data->getMutableKeys();
		$keys[] = 'withheld_targets';
		return $keys;
	}
	

	/**
	 * As the contract defined in Blackbox_IStateData is too loose and...
	 * there is no other interface that describes the interaction with the implementation of Blackbox_StateData and...
	 * we don't want to create yet another  Blackbox_IStateData class for this one key and...
	 * this is how state data was supposed to work in the first place...
	 * we will call the encapsulated state data object for any call not  handled by this decorator
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($name, $arguments)
	{
		return call_user_func_array(array($this->state_data, $name), $arguments);
	}
}
?>
