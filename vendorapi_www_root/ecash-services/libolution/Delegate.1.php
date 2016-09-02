<?php
	/**
	 * @package Core
	 */

	/**
	 * A simple delegate that wraps the PHP callback psuedo-type
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 * @example examples/delegate.php
	 */
	class Delegate_1 extends Object_1
	{
		/**
		 * Convenience method for creating a delegate from a function
		 * @param string $function
		 * @param array $params
		 * @return Delegate_1
		 */
		public static function fromFunction($function, array $params = NULL)
		{
			return new Delegate_1($function, $params);
		}

		/**
		 * Convenience method for creating a delegate from a class method
		 * @param string $class
		 * @param string $method
		 * @param array $params
		 * @return Delegate_1
		 */
		public static function fromMethod($class, $method, array $params = NULL)
		{
			return new Delegate_1(array($class, $method), $params);
		}

		/**
		 * Turns out, this sucks.
		 * The overhead of calling this method actually completely erases
		 * any gains from not using call_user_func_array.
		 *
		 * @param mixed $delegate
		 * @param array $params
		 * @return mixed
		 */
		public static function call($delegate, array $params = NULL)
		{
			return call_user_func_array($delegate, $params);
		}

		/**
		 * @var callback
		 */
		protected $call;

		/**
		 * @var array
		 */
		protected $params = array();

		/**
		 * If provided, params here will be PREpended to the params
		 * provided when the delegate is invoked
		 *
		 * @param callback $call PHP callback psuedo-type
		 * @param array $params
		 * @return void
		 */
		public function __construct($call, array $params = NULL)
		{
			// only check syntax here (second param == TRUE),
			// because the function doesn't HAVE to exist yet!
			if (!is_callable($call, TRUE))
			{
				throw new InvalidArgumentException('Must be callable');
			}

			$this->call = $call;

			if ($params !== NULL)
			{
				$this->params = array_values($params);
			}
		}

		/**
		 * Set params to be PREpended on invoke
		 *
		 * @param array $params
		 */
		public function setParams(array $params)
		{
			$this->params = array_values($params);
		}

		/**
		 * Invoke the delegate using a variable list of parameters
		 * @return mixed
		 */
		public function invoke()
		{
			$params = func_get_args();

			// merge in our static list of parameters;
			// they ALWAYS come first
			if ($this->params)
			{
				$params = array_merge($this->params, $params);
			}

			return call_user_func_array($this->call, $params);
		}

		/**
		 * Invoke the delegate using an array of parameters
		 *
		 * @param array $params
		 * @return mixed
		 */
		public function invokeArray(array $params)
		{
			// merge in our static list of parameters;
			// they ALWAYS come first
			if ($this->params)
			{
				$params = array_merge($this->params, array_values($params));
			}

			return call_user_func_array($this->call, $params);
		}
	}

?>
