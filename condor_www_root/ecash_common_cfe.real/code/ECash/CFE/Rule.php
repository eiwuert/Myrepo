<?php

	/**
	 * A rule contains a collection of conditions and actions
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class ECash_CFE_Rule implements ECash_CFE_ICondition, ECash_CFE_IAction
	{
		/**
		 * @var int
		 */
		protected $salience = 0;

		protected $or = FALSE;

		/**
		 * @var array ECash_CFE_ICondition
		 */
		protected $conditions = array();

		/**
		 * @var array ECash_CFE_IAction
		 */
		protected $actions = array();

		public function __construct(array $conditions, array $actions)
		{
			$this->conditions = $conditions;
			$this->actions = $actions;
		}

		// these are gay so far
		public function getType() {}
		public function getParameters() {}

		public function getActions()
		{
			return new ArrayIterator($this->actions);
		}

		/**
		 * Validates all conditions
		 *
		 * @param ECash_CFE_IContext $c
		 * @return bool
		 */
		public function isValid(ECash_CFE_IContext $c)
		{
			$result = TRUE;

			foreach ($this->conditions as $con)
			{
				try
				{
					$result = $con->isValid($c);
				}
				catch (Exception $e)
				{
					$result = FALSE;
				}

				// if it's an OR rule, we can break as soon as a single
				// condition evaluates to TRUE; otherwise we can break
				// as soon as a single condition evaluates to FALSE
				if ($this->or === $result) break;
			}

			return $result;
		}

		/**
		 * Executes all actions contained within the rule
		 *
		 * @param ECash_CFE_IContext $c
		 */
		public function execute(ECash_CFE_IContext $c)
		{
			foreach ($this->actions as $a)
			{
				try
				{
					$a->execute($c);
				}
				catch (ECash_CFE_RuntimeException $e)
				{
					// if this action actually throws a
					// runtime exception, then let it continue on...
					throw $e;
				}
				/*catch (Exception $e)
				{
					// for all other exceptions, throw a
					// new runtime exception
					throw new ECash_CFE_RuntimeException();
				}*/
			}
		}
	}

?>
