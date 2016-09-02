<?php
/**
 * Blackbox_Data class file.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */

/**
 * Blackbox data transfer object to manage housing the passed in data used by Blackbox.
 * 
 * You must modify this class to include what data you want Blackbox to work on. This allows
 * us to clearly define what data we pass to Blackbox and that data doesn't get blown out of
 * proportion.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class Blackbox_Data
{
	/**
	 * Data passed into the Blackbox object for target rule validation.
	 *
	 * @var array
	 */
	protected $data = array();
	
	/**
	 * Blackbox_Data constructor.
	 */
	public function __construct()
	{
		// only keys initialized here will be allowed changed/set later.
		$this->data['target'] = array();
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
	 * @param string $name  The name of the class variable to set
	 * @param mixed  $value The value to set
	 * 
	 * @return void
	 */
	public function __set($name, $value)
	{
		if (array_key_exists($name, $this->data))
		{
			$this->data[$name] = $value;
		}
		else
		{
			throw new Blackbox_Exception("Variable $name not found in data array");
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
		unset($this->data[$name]);
	}
	
	/**
	 * Returns the keys used in the $data array.  This is
	 * useful for knowing which data is being stored in this
	 * object.
	 *
	 * @return array The keys from the $data array.
	 */
	public function getKeys()
	{
		return array_keys($this->data);
	}
}
?>
