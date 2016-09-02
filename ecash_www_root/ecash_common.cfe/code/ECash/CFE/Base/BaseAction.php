<?php
	
	/**
	 * A base action that takes care of parameters that are expressions
	 *
	 */
	abstract class ECash_CFE_Base_BaseAction implements ECash_CFE_IAction, ECash_CFE_IExpression
	{
		protected $params;
		
		public function __construct(array $params = NULL)
		{
			$this->params = $params;
		}
		
		/**
		 * Returns an array of parameters with expressions resolved
		 *
		 * @param CFE_IContext $c
		 * @return array
		 */
		protected function evalParameters(ECash_CFE_IContext $c)
		{
			$p = array();

			foreach ($this->params as $name=>$value)
			{
				if ($value instanceof ECash_CFE_IExpression)
				{
					$value = $value->evaluate($c);
				}
				
				$p[$name] = $value;
			}
			
			return $p;
		}
		
		public function evaluate(ECash_CFE_IContext $c)
		{
			return $this->execute($c);
		}


		/**
		 * returns the reference data for the given parameter
		 *
		 * @param parameter name $param_name
		 * @return array($key => $name)
		 */
		public function getReferenceData($param_name) {
			return array();
		}
		
		public function isEcashOnly()
		{
			return false;
		}
	}
	
?>
