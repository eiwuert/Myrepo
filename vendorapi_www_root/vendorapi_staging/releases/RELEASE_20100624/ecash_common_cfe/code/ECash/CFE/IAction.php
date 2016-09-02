<?php

	/**
	 * A CFE action
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	interface ECash_CFE_IAction
	{
		const TYPE_BOOL 	= 'bool';
		const TYPE_NUMBER 	= 'number';
		const TYPE_STRING 	= 'string';
		const TYPE_DATE 	= 'date';
		const TYPE_VARIABLE = 'var';

		/**
		 * Returns a name short that can be used to identify this rule in the database
		 * @return string
		 */
		public function getType();

		/**
		 * Returns an array of required parameters with format name=>type
		 * @return array
		 */
		public function getParameters();

		/**
		 * Executes the action
		 *
		 * @param ECash_CFE_IContext $c
		 */
		public function execute(ECash_CFE_IContext $c);
	}

?>
