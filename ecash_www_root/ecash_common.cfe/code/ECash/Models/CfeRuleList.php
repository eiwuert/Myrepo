<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_CfeRuleList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_CfeRule';
		}

		public function getTableName()
		{
			return 'cfe_rule';
		}
	}
?>
