<?php
	/**
	 * @package Core
	 */

	/**
	 * Returned from a deferred action
	 * This allows you to add Delegates that will be fired upon
	 * the action's completion.
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class Deferred_1
	{
		/**
		 * @var array
		 */
		protected $on_complete = array();

		/**
		 * @var array
		 */
		protected $on_error = array();

		/**
		 * free-form events
		 * @var array
		 */
		protected $events = array();

		/**
		 * Add a delegate to be fired upon action completion
		 * @param Delegate_1 $d
		 * @return void
		 */
		public function addOnComplete(Delegate_1 $d)
		{
			$this->on_complete[] = $d;
		}

		/**
		 * Add a delegate to be fired upon an error
		 * @param Delegate_1 $d
		 * @return void
		 */
		public function addOnError(Delegate_1 $d)
		{
			$this->on_error[] = $d;
		}

		/**
		 * Signal that the action has completed
		 * NOTE: accepts a variable list of parameters
		 * @return void
		 */
		public function complete()
		{
			$params = func_get_args();
			return $this->invokeAll($this->on_complete, $params);
		}

		/**
		 * Signal that the action encountered an error
		 * NOTE: accepts a variable list of parameters
		 * @return void
		 */
		public function error()
		{
			$params = func_get_args();
			return $this->invokeAll($this->on_error, $params);
		}

		/**
		 * Overloaded access for free-form event types
		 * @example
		 *   $d->addOnBlah(Delegate_1::fromFunction('test'));
		 *   $d->blah();
		 * @param string $name
		 * @param array $params
		 * @return mixed
		 */
		public function __call($name, array $params)
		{
			// calls to methods beginning with "addon" are
			// assumed to be adding a delegate, otherwise
			// the entire name is assumed to be an event
			if (strncasecmp($name, 'addon', 5) == 0)
			{
				if (!$params[0] instanceof Delegate_1)
				{
					throw new InvalidArgumentException('Parameter 1 must be an instance of Delegate_1');
				}

				// strip the 'addon' portion
				$name = substr($name, 5);

				if (!isset($this->events[$name]))
				{
					$this->events[$name] = array();
				}
				$this->events[$name][] = $params[0];
			}
			elseif (isset($this->events[$name]))
			{
				return $this->invokeAll($this->events[$name], $params);
			}

			throw new BadMethodCallException('Invalid method, '.$name);
		}

		/**
		 * Fire a list of delegates with parameters
		 * @param array $delegates Array of Delegate_1 instances
		 * @param array $params Parameters
		 * @return void
		 */
		protected function invokeAll(array $delegates, array $params)
		{
			$c = count($params);
			$r = NULL;

			foreach ($delegates as $d)
			{
				$r = $d->invokeArray($params);

				// the return value of the previous delegate gets
				// passed as the last parameter to the next
				$params[$c] = $r;
			}

			return $r;
		}
	}

?>
