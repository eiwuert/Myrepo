<?php

	/**
	 * Very simple class that acts like C#'s event construct.
	 *
	 * Stores a list of delegates internally, and when the event is
	 * invoked, any delegates contained within will be invoked blindly.
	 *
	 * @author John Hargrove <john.hargrove@sellingsource.com>
	 *
	 */
	class Event_1 extends Object_1
	{
		/**
		 * @var array
		 */
		protected $delegates = array();

		/**
		 * @var array
		 */
		protected $params = NULL;

		/**
		 * @param array|Delegate_1 $delegate
		 */
		public function __construct($delegate = NULL)
		{
			if ($delegate !== NULL)
			{
				if (!is_array($delegate))
				{
					$delegate = func_get_args();
				}

				foreach ($delegate as $d)
				{
					$this->addDelegate($d);
				}
			}
		}

		/**
		 * Set params to be PREpended for each delegate
		 *
		 * @param array $params
		 * @return void
		 */
		public function setParams(array $params)
		{
			$this->params = $params;
		}

		/**
		 * Adds a delegate to this event
		 *
		 * @param Delegate_1 $delegate
		 * @return void
		 */
		public function addDelegate(Delegate_1 $delegate)
		{
			$this->delegates[] = $delegate;
		}

		/**
		 * Invokes all delegates, returning nothing
		 * @return void
		 */
		public function invoke()
		{
			$arg = func_get_args();

			if ($this->params)
			{
				$arg = array_merge($this->params, $arg);
			}

			foreach ($this->delegates as $delegate)
			{
				$delegate->invokeArray($arg);
			}
		}

		/**
		 * Invoke all delegates with an array of arguments
		 *
		 * @param array $arg
		 * @return void
		 */
		public function invokeArray(array $arg)
		{
			if ($this->params)
			{
				$arg = array_merge($this->params, $arg);
			}

			foreach ($this->delegates as $delegate)
			{
				$delegate->invokeArray($arg);
			}
		}
	}
?>
