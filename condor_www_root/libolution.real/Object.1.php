<?php
/**
 * @package Core
 *
 */

require_once 'libolution/InvalidPropertyException.1.php';

/**
 * The Libolution base object
 * Extending Object_1 adds psuedo-properties and restricts usage
 * to properly defined public members.
 *
 * @author John Hargrove <john.hargrove@sellingsource.com>
 */
class Object_1
{
	/**
	 * "magic method" for processing property get requests for any class
	 * which inherits this.  Will search for the proper get function and
	 * return the return value of said method.  Will throw an exception
	 * if no method is found.
	 *
	 * The conversion:
	 *
	 * $myobj->Hello becomes $myobj->getHello();
	 *
	 * @param string $property_name
	 * @return mixed
	 */
	public function __get($property_name)
	{
		if (method_exists($this, 'get' . $property_name))
		{
			return $this->{'get' . $property_name}();
		}
		throw new InvalidPropertyException_1($property_name);
	}

	/**
	 * "magic method" for processing property set requests for any class
	 * which inherits this.  Will search for the proper set function and
	 * return anything said method returns (should be nothing).  Will
	 * throw an exception if no set method is defined for $property_name
	 *
	 * The conversion:
	 *
	 * $myobj->Hello = "World"; becomes $myobj->setHello("World");
	 *
	 * @param string $property_name
	 * @param mixed $value
	 * @return mixed
	 */
	public function __set($property_name, $value)
	{
		if (method_exists($this, 'set' . $property_name))
		{
			return $this->{'set' . $property_name}($value);
		}
		throw new InvalidPropertyException_1($property_name);
	}

	/**
	 * "magic method" for processing property isset requests for any class
	 * which inherits this. Will search for the proper get function and if
	 * it is found it will return true.
	 *
	 * @param string $property_name
	 * @return bool
	 */
	public function __isset($property_name)
	{
		return method_exists($this, 'get' . $property_name);
	}
}

?>
