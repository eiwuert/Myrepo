<?php

	/**
	 * A comparison condition
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	abstract class ECash_CFE_Condition_ComparisonCondition implements ECash_CFE_ICondition, ECash_CFE_IExpression
	{
		protected $v1;
		protected $v2;
		
		/**
		 * Compares two values and returns a boolean outcome
		 *
		 * @param unknown_type $value1
		 * @param unknown_type $value2
		 * @return bool
		 */
		abstract protected function compare($value1, $value2);
		
		public function __construct($value1, $value2)
		{
			$this->v1 = $value1;
			$this->v2 = $value2;
		}
		
		public function evaluate(ECash_CFE_IContext $c)
		{
			return $this->isValid($c);
		}

		/**
		 * Returns a boolean indicating whether the condition is valid
		 *
		 * @param CFE_IContext $c
		 * @return bool
		 */
		public function isValid(ECash_CFE_IContext $c)
		{
			// for future expansion, this could be:
			$value1 = ($this->v1 instanceof ECash_CFE_IExpression) ? $this->v1->evaluate($c) : $this->v1;
			$value2 = ($this->v2 instanceof ECash_CFE_IExpression) ? $this->v2->evaluate($c) : $this->v2;

			return $this->compare($value1, $value2);
		}
	}

?>
