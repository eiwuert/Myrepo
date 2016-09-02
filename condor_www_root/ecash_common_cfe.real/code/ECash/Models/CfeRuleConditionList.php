<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_CfeRuleConditionList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_CfeRuleConditionList';
		}

		public function createInstance(array $db_row)
		{
			$item = new ECash_Models_CfeRuleCondition($this->getDatabaseInstance());
			$item->fromDbRow($db_row);
			return $item;
		}
		public function loadBy(array $where_args)
		{
			$query = "SELECT * FROM cfe_rule_condition " . self::buildWhere($where_args);
			$this->statement = DB_Util_1::queryPrepared(
					$this->getDatabaseInstance(),
					$query,
					$where_args
			);
		}
	}
?>
