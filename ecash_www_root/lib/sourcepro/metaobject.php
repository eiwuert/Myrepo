<?php
/**
	An abstract base for objects with overloaded attributes.
*/

abstract class SourcePro_Metaobject
{
	/// The collection of Attributes. (assoc. array)
	public $v_attribute;

	/**
		Initializes an instance of this class.
	*/
	function __construct ()
	{
		$this->v_attribute = array();
	}

	function __destruct ()
	{
		unset ($this->v_attribute);
	}

	/**
		Magic __sleep function.
	*/
	function __sleep ()
	{
		return array_keys(get_object_vars($this));
	}

	/**
		Magic __wakeup function.
	*/
	function __wakeup ()
	{

	}

	/**
		Overloaded magic __get function.

		@param name		The name of the attribute.
		@exception		SourcePro_Exception General exception occured.
	*/
	function __get ($name)
	{
		if (! isset($this->v_attribute[$name]))
		{
			throw new SourcePro_Exception("invalid attribute ($name)", 1000);
		}

		try
		{
			$value = $this->v_attribute[$name]->_get();
		}
		catch (Exception $e)
		{
			throw $e;
		}
		return $value;
	}

	/**
		Overloaded magic __set function.

		@param name		The name of the attribute.
		@exception		SourcePro_Exception General exception occured.
	*/
	function __set ($name, $value)
	{
		if (! isset($this->v_attribute[$name]))
		{
			throw new SourcePro_Exception("invalid attribute ($name)", 1000);
		}

		try
		{
			$this->v_attribute[$name]->_set($value);
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}

	/**
		Overloaded magic __call function.

		@param name		The name of the attribute.
		@exception		SourcePro_Exception General exception occured.
	*/
	function __call ($name, $args)
	{
		if (! isset($this->v_attribute[$name]))
		{
			throw new SourcePro_Exception("invalid attribute ($name)", 1000);
		}

		try
		{
			return $this->v_attribute[$name]->_call($args);
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}
}

?>
