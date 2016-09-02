<?php

	/**
	 * Acts as a "proxy" between a PRPC Server and a class,
	 * allowing you to abstract an API's transport
	 * from it's base class without lots of extra work.
	 *
	 * @author Andrew Minerd
	 */
	class PRPC_Proxy extends PRPC_Server
	{

		protected $object;

		/**
		 * PRPC Proxy constructor.
		 *
		 * @param string $class The class to proxy requests to
		 * @param array $args Arguments passed to $class construct
		 * @param boolean $process Process the PRPC request now?
		 * @param boolean $strict Strict?
		 */
		public function __construct($class, $args = NULL, $process = TRUE, $strict = FALSE)
		{

			// create our object
			if (is_object($class))
			{
				$this->object = $class;
			}
			elseif (is_array($args))
			{
				// this stinks, but there's no other way of doing this without
				// resorting to eval (array($class, '__construct') doesn't work)
				// [http://www.php.net/manual/en/language.oop5.reflection.php]
				$this->object = call_user_func_array(array(new ReflectionClass($class), 'newInstance'), $args);
			}
			elseif ($args !== NULL)
			{
				$this->object = new $class($args);
			}
			else
			{
				$this->object = new $class();
			}

			parent::__construct($process, $strict);

		}

		/**
		 * Override function for function calls.
		 *
		 * @param string $name
		 * @param array $args
		 * @return mixed
		 */
		public function __call($name, $args)
		{

			$result = call_user_func_array(array(&$this->object, $name), $args);
			return $result;

		}

		/**
		 * Checks to see if a method exists.
		 *
		 * @param string $function
		 * @return boolean
		 */
		protected function __exists($function)
		{
			$exists = method_exists($this->object, $function);
			return $exists;
		}

	}

?>