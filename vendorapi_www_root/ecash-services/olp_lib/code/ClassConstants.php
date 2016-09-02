<?php

class ClassConstants
{
	const CASE_INSENSITIVE = 2;
	const I = 2;
	
	/**
	 * @var ReflectionClass
	 */
	protected $reflection_class;
	
	public function __construct($class)
	{
		$this->setClass($class);
	}
	
	/**
	 * Search for all constants with names starting with the supplied prefix.
	 * 
	 * @param string $prefix The string prefix the caller is interested in.
	 * @param int $flags Binary OR'ed flags which will affect the search. The
	 * most relevant flag is probably self::CASE_INSENSITIVE.
	 * @return array Assoc array of constants (name => value)
	 */
	public function keyStartsWith($prefix, $flags = NULL)
	{
		$function = 'strcmp';
		
		if ($flags && ($flags & self::CASE_INSENSITIVE))
		{
			$function = 'strcasecmp';
		}
		
		$return = array();
		
		foreach ($this->reflection_class->getConstants() as $key => $value)
		{
			if ($function($prefix, substr($key, 0, strlen($prefix))) == 0)
			{
				$return[$key] = $value;
			}
		}
		
		return $return;
	}
	
	protected function setClass($class)
	{
		if (is_string($class) && class_exists($class, TRUE))
		{
			$this->reflection_class = new ReflectionClass($class);
		}
		elseif (is_object($class))
		{
			$this->reflection_class = new ReflectionObject($class);
		}
		else
		{
			throw new InvalidArgumentException(
				"argument must be valid class name or object, not $class"
			);
		}
	}
}

?>