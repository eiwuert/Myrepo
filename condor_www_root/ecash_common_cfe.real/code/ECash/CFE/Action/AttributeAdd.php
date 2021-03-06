<?php

	/**
	 * An action that adds a numeric value to the given attribute
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	class ECash_CFE_Action_AttributeAdd extends ECash_CFE_Base_AttributeAction
	{
		public function getType()
		{
			return 'AttributeAdd';
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

			// increment the named attribute
			$cur = $c->getAttribute($params['name']);
			$cur += (int)$params['value'];

			// set the attribute in the context
			$c->setAttribute($params['name'], $cur);
		}
	}

?>
