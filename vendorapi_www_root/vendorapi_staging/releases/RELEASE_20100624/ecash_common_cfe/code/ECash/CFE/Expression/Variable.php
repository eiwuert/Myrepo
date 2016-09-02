<?php
	
	class ECash_CFE_Expression_Variable implements ECash_CFE_IExpression
	{
		/**
		 * The variable name
		 *
		 * @var string
		 */
		protected $attr;
		
		public function __construct($attr)
		{
			$this->attr = $attr;
		}
		
		/**
		 * Evaluates this expression, returning the variable's value
		 *
		 * @param CFE_IContext $c
		 * @return mixed
		 */
		public function evaluate(ECash_CFE_IContext $c)
		{
			return $c->getAttribute($this->attr);
		}
	}
	
?>