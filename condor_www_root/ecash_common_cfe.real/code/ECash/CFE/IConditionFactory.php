<?php
	
	/**
	 * A ConditionFactory provides a means to fetch CFE_ICondition implementers
	 * by a common name (i.e., 'equals').
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */	
	interface ECash_CFE_IConditionFactory
	{
		/**
		 * @param string $name
		 * @param array $params
		 * @return ECash_CFE_ICondition
		 */
		public function getCondition($name, array $params);
	}
	
?>
