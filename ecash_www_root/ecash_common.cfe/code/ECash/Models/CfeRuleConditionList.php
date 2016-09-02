<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_CfeRuleConditionList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_CfeRuleCondition';
		}

		public function getTableName()
		{
			return 'cfe_rule_condition';
		}

	}
?>
