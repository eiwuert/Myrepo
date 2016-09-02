<?php

	/**
	 * A condition that contains sub-conditions
	 *
	 */
	class ECash_CFE_CompositeCondition implements ECash_CFE_ICondition, ECash_CFE_ISchedulable
	{
		const OP_OR = 1;
		const OP_AND = 2;

		/**
		 * @var array
		 */
		protected $cond;

		/**
		 * @var bool
		 */
		protected $or;

		/**
		 * @var array
		 */
		protected $matched = array();

		public function __construct(array $conditions = array(), $op = self::OP_AND)
		{
			$this->cond = $conditions;
			$this->or = ($op == self::OP_OR);
		}

		public function addCondition(ECash_CFE_ICondition $c)
		{
			$this->cond[] = $c;
		}

		public function getConditions()
		{
			return $this->cond;
		}

		/**
		 * Validates all sub-conditions
		 *
		 * @param ECash_CFE_IContext $c
		 * @return bool
		 */
		public function isValid(ECash_CFE_IContext $c)
		{
			$valid = TRUE;
			$this->matched = array();

			foreach ($this->cond as $condition)
			{
				try
				{
					$valid = $condition->isValid($c);
					if ($valid) $this->matched[] = $condition;
				}
				catch (ECash_CFE_FatalException $e)
				{
					throw $e;
				}
				catch (ECash_CFE_RuntimeException $e)
				{
					$valid = FALSE;
				}

				// if it's an OR rule, we can break as soon as a single
				// condition evaluates to TRUE; otherwise we can break
				// as soon as a single condition evaluates to FALSE
				if ($valid === $this->or) break;
			}

			return $valid;
		}

		public function addToAgenda(ECash_CFE_Agenda $agenda)
		{
			foreach ($this->matched as $rule)
			{
				$agenda->addAction($rule);
			}
		}
	}

?>