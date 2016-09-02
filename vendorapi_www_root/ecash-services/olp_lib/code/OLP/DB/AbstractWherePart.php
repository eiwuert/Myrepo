<?php

/**
 * Implements common functions for OLP_DB_IWherePart implementations.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package DB
 */
abstract class OLP_DB_AbstractWherePart extends Object_1 implements OLP_DB_IWherePart
{
	/**
	 * Representation of a callable item, either an array of (object, methodName)
	 * or simply a functionname string.
	 *
	 * @var array|string
	 */
	protected $escape_callback;
	
	/**
	 * The table name to prefix to prefix to field names.
	 * 
	 * Even containers may need this to pass it down to children.
	 *
	 * @var string
	 */
	protected $table = NULL;
	/**
	 * Obtain this object as a full WHERE clause.
	 *
	 * @param string $table_fallback Table name to use if this object does not
	 * have one.
	 * @param mixed $escape_callback A callback (string, array(obj,methodName) or reflection
	 * object which can have invokeArgs() called. This parameter is used to 
	 * escape sql string values.
	 * 
	 * @return string
	 */
	public function toWhere($table_fallback = NULL, $escape_callback = NULL)
	{
		return 'WHERE ' . $this->toSql($table_fallback, $escape_callback);
	}

	/**
	 * Obtain this object as a full WHERE clause (with no table fallback)
	 *
	 * @see toWhere()
	 * @return string
	 */
	public function __toString()
	{
		return $this->toWhere();
	}

	// --------- GET/SET Object_1 stuff
	
	/**
	 * Set the table that this WherePart will prefix to the field.
	 *
	 * @param string $table
	 * @return void
	 */
	public function setTable($table)
	{
		if (!$table) return;
		// table could eventually be a reference to a model. 
		if (!is_string($table))
		{
			throw new OLP_DB_InvalidArgumentException(
				'table must be a string, not ' . var_export($table, TRUE)
			);
		}
		$this->table = $table;
	}
	
	/**
	 * Set the callback function to be used to escape string values.
	 *
	 * The method/function must either be a ReflectionFunction
	 * which can have invokeArgs() called with 1 parameter (the string), a
	 * string name for a function which takes 1 argument, or an array containing
	 * (object item, string methodName) where methodName belongs on object/class
	 * "item" and accepts 1 parameter.
	 * 
	 * @param ReflectionFunction|array|string $callback The 
	 * callback method/function to use to quote string values.
	 * @return void
	 */
	public function setEscapeCallback($callback)
	{
		$this->validateEscapeCallback($callback);
		
		$this->escape_callback = $callback;
	}
	
	/**
	 * Makes sure that a callback item is OK to assign or use.
	 *
	 * @param mixed $callback
	 * @return void
	 */
	protected function validateEscapeCallback($callback)
	{
		if (!$this->isValidCallbackType($callback))
		{
			throw new InvalidArgumentException(
				"callback must be string function name, reflection class or 
				array of (obj, methodName), not " . var_export($callback, TRUE)
			);
		}
		
		if (is_array($callback))
		{
			$this->validateCallbackArray($callback);
		}
		elseif (is_string($callback) && !function_exists($callback))
		{
			throw new InvalidArgumentException(
				'function must exist to be used as callback, ' 
				. var_export($callback, TRUE) . ' function does not exist.'
			);
		}
	}
	
	/**
	 * Determines whether the parameter is the right PHP type for a callback.
	 *
	 * @param ReflectionFunction|array|string $callback
	 * @return bool
	 */
	protected function isValidCallbackType($callback)
	{
		return $callback instanceof ReflectionFunction
			|| is_array($callback)
			|| is_string($callback);
	}
	
	/**
	 * Validate that a callback array is OK
	 *
	 * @param array $array A callback array in the form array(object, methodName)
	 * @return void
	 */
	protected function validateCallbackArray($array)
	{
		$object = $array[0];
		$method_name = $array[1];
		
		if (!is_object($object))
		{
			throw new InvalidArgumentException(
				'callback array first item must be an object, not ' 
				. var_export($object, TRUE)
			);
		}
		
		if (!method_exists($object, $method_name))
		{
			throw new InvalidArgumentException(
				"callback object must have method $method_name"
			);
		}
	}
}

?>
