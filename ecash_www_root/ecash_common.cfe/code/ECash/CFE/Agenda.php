<?php

	/**
	 * The agenda contains actions that are waiting to be executed
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class ECash_CFE_Agenda implements IteratorAggregate, Countable
	{
		protected $actions = array();
		protected $salience = array();

		public function clear()
		{
			$this->actions = array();
			$this->salience = array();
		}

		public function count()
		{
			return count($this->actions);
		}

		/**
		 * Add multiple actions to the agenda
		 *
		 * @param array $actions
		 */
		public function addActions(Traversable $actions)
		{
			foreach ($actions as $action)
			{
				if (!$action instanceof ECash_CFE_IAction)
				{
					throw new Exception('Action must implement ECash_CFE_IAction');
				}
				$this->actions[] = $action;
			}
		}


		/**
		 * Add an action to our agenda
		 *
		 * @param mixed $a
		 */
		public function addAction($a)
		{
			if ($a instanceof ECash_CFE_ISchedulable)
			{
				$a->addToAgenda($a);
			}
			else if ($a instanceof ECash_CFE_IAction)
			{
				$this->actions[] = $a;
			}
			else
			{
				throw new InvalidArgumentException('Argument must implement ECash_CFE_ISchedulable or ECash_CFE_IAction');
			}
		}

		/**
		 * Required by IteratorAggregate, returns an Iterator object
		 *
		 * @return Iterator
		 */
		public function getIterator()
		{
			return new ArrayIterator($this->actions);
		}
	}

?>
