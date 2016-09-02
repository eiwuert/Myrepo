<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_CfeRuleActionList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_CfeRuleAction';
		}

		public function getTableName()
		{
			return 'cfe_rule_action';
		}
	}
?>
