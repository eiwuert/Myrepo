<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_RuleSetComponentList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_RuleSetComponent';
		}

		public function getTableName()
		{
			return 'rule_set_component';
		}
	}
?>
