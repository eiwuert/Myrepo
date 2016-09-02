<?php

	/**
	 * A context implementation that keeps a local "carbon copy" of what was modified
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class ECash_CFE_CarbonContext implements ECash_CFE_IContext
	{
		/**
		 * @var ECash_CFE_IContext
		 */
		protected $context;

		/**
		 * @var array
		 */
		protected $copy = array();

		/**
		 * @var array
		 */
		protected $stack = array();

		/**
		 * @var array
		 */
		protected $vars = NULL;

		/**
		 * @param ECash_CFE_IContext $c
		 * @param array $vars
		 */
		public function __construct(ECash_CFE_IContext $c, array $vars = array())
		{
			$this->context = $c;
			if ($vars) $this->pushVars($vars);
		}

		/**
		 * Gets the carbon copy of the attributes
		 *
		 * @return array
		 */
		public function getCopy()
		{
			return $this->copy;
		}

		/**
		 * Returns an attribute value
		 * Uses local variables first, then the decorated context
		 *
		 * @param string $name
		 * @return mixed
		 */
		public function getAttribute($name)
		{
			if ($this->vars !== NULL
				&& isset($this->vars[$name]))
			{
				return $this->vars[$name];
			}
			return $this->context->getAttribute($name);
		}

		/**
		 * Sets an attribute
		 *
		 * @param string $name
		 * @param mixed $value
		 * @return void
		 */
		public function setAttribute($name, $value)
		{
			// keep track of everything that gets set
			$this->copy[$name] = $value;
			$this->context->setAttribute($name, $value);
		}

		/**
		 * Pushes a set of variables onto the stack
		 * This is used to place temporary event parameters in the context
		 *
		 * @param array $vars
		 * @return void
		 */
		public function pushVars(array $vars)
		{
			array_push($this->stack, $vars);
			$this->vars = $vars;
		}

		/**
		 * Pops a set of variables off the stack
		 * This allows you to receive modifications that the business rules
		 * may have made to the local variables
		 *
		 * @return array
		 */
		public function popVars()
		{
			if (!isset($this->stack[0]))
			{
				throw new ECash_CFE_RuntimeException('Stack is empty');
			}

			$vars = array_pop($this->stack);
			$this->vars = reset($this->stack);

			return $vars;
		}
	}

?>