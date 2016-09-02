<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_CFE_API_RuleSetList extends ECash_Models_CfeRuleSetList 
	{
		public function getClassName()
		{
			return 'ECash_CFE_API_RuleSetList';
		}

		public function createInstance(array $db_row)
		{
			$item = new ECash_CFE_API_RuleSetDef($this->getDatabaseInstance());
			$item->fromDbRow($db_row);
			return $item;
		}
		public function loadBy(array $where_args)
		{
			$query = "SELECT * FROM cfe_rule_set " . self::buildWhere($where_args);
			$this->statement = DB_Util_1::queryPrepared(
					$this->getDatabaseInstance(),
					$query,
					$where_args
			);
		}

	}
?>
