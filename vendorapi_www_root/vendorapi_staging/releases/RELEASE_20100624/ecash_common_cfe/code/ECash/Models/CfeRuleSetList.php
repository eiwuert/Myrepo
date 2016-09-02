<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_CfeRuleSetList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_CfeRuleSet';
		}

		public function getTableName()
		{
			return 'cfe_rule_set';
		}
	}
?>
