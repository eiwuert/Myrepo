<?php

	/**
	 * An action that inserts the current application into the given queue
	 *
	 */
	class ECash_CFE_Action_AttributeSet extends ECash_CFE_Base_AttributeAction implements ECash_CFE_IExpression
	{
		public function getType()
		{
			return 'AttributeSet';
		}

		public function evaluate(ECash_CFE_IContext $c)
		{
			return $this->execute($c);
		}

		/**
		 * Inserts into the queue
		 *
		 * @param CFE_IContext $c
		 */
		public function execute(ECash_CFE_IContext $c)
		{
			// evaluate any expression parameters
			$params = $this->evalParameters($c);

			// set the attribute in the context
			$c->setAttribute($params['name'], $params['value']);
			return $params['value'];
		}
	}

?>
