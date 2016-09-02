<?php

/**
 * An object that embodies a function or method call, will be callable in 5.3
 * 
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 */
class OLPBlackbox_Rule_Callback extends Object_1
{
	/**
	 * @var object
	 */
	protected $object;
	
	/**
	 * @var string
	 */
	protected $function;
	
	/**
	 * @var array
	 */
	protected $arguments;
	
	/**
	 * Create a new Callback object.
	 * @param string $function The name of the function/method.
	 * @param array $arguments The list of arguments to pass when calling the
	 * specified function/method.
	 * @param object $object Indicates this is a method call and provides the
	 * instantiation of the object to use to call the method.
	 * @return void
	 */
	public function __construct($function, array $arguments = array(), $object = NULL)
	{
		if (!is_null($object) && !is_object($object))
		{
			throw new InvalidArgumentException('object parameter must be NULL or object');
		}
		$this->function = $function;
		$this->arguments = $arguments;
		$this->object = $object;
	}
	
	/**
	 * Set the callback object.
	 * @param object $object The object to execute the method on.
	 * @return void
	 */
	public function setObject($object)
	{
		$this->object = $object;
	}
	
	/**
	 * Returns the object this callback will execute the method on.
	 * @return object
	 */
	public function getObject()
	{
		return $this->object;
	}
	
	/**
	 * Returns the name of the function/method to execute.
	 * @return string
	 */
	public function getFunction()
	{
		return $this->function;
	}
	
	/**
	 * The list of arguments that will be passed to the callback function/method.
	 * @return array
	 */
	public function getArguments() 
	{
		return $this->arguments;
	}
	
	/**
	 * The main method for this class, calls the function/method callback.
	 * 
	 * @return mixed Whatever the return value of the callback is.
	 */
	public function __invoke()
	{
		if ($this->isValidMethod())
		{
			return call_user_func_array(array($this->object, $this->function), $this->arguments);
		}
		elseif ($this->isValidFunction())
		{
			return call_user_func_array($this->function, $this->arguments);
		}
		else
		{
			throw new RuntimeException(sprintf(
				'%s was not valid function or method (object %s)',
				var_export($this->function, TRUE),
				(is_object($this->object) ? get_class($this->object) : 'NULL')
			));
		}
	}
	
	/**
	 * Determines if the this object represents a valid method callback.
	 * @return bool
	 */
	protected function isValidMethod()
	{
		return is_object($this->object) && method_exists($this->object, $this->function);
	}
	
	/**
	 * Determines if this object represents a valid function callback.
	 * @return bool
	 */
	protected function isValidFunction()
	{
		return function_exists($this->function);
	}
}

?>