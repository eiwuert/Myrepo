<?php
/**
 * Blackbox_Config class file.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

/**
 * The Blackbox_Config class contains configuration information for Blackbox.
 *
 * Default values for configuration variables are set as the default values in the constructor.
 * Variables are considered unset if they have a NULL value. NULL shall not be used as a valid
 * value for any configuration variable.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Config
{
	/**
	 * Data contained within Blackbox_Config
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Blackbox_Config constructor.
	 */
	protected function __construct()
	{
	}

	/**
	 * Overloaded __get method to get class variables.
	 *
	 * @param string $name The name of the class variable to get
	 *
	 * @return mixed
	 */
	public function __get($name)
	{
		if (isset($this->data[$name]))
		{
			return $this->data[$name];
		}
	}

	/**
	 * Overloaded __set method to set class variables.
	 *
	 * Values are read-only unless they are unset() before being set() again. We want to prevent
	 * accidental overwriting of values. You should know that you're setting a value to another
	 * value.
	 *
	 * @param string $name  The name of the class variable to set
	 * @param mixed  $value The value to set
	 *
	 * @return void
	 */
	public function __set($name, $value)
	{
		if (!isset($this->data[$name]))
		{
			$this->data[$name] = $value;
		}
	}

	/**
	 * Overloaded __isset method to determine if a class variable is set.
	 *
	 * @param string $name The name of the class variable to check
	 *
	 * @return bool
	 */
	public function __isset($name)
	{
		return isset($this->data[$name]);
	}

	/**
	 * Overloaded __unset method to unset class variables.
	 *
	 * @param string $name The name of the class variable to unset
	 *
	 * @return void
	 */
	public function __unset($name)
	{
		if (array_key_exists($name, $this->data))
		{
			$this->data[$name] = NULL;
		}
	}
}
?>
