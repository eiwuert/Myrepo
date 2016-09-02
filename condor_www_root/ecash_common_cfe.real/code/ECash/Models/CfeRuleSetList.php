<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_CfeRuleSetList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_CfeRuleSetList';
		}

		public function createInstance(array $db_row)
		{
			$item = new ECash_Models_CfeRuleSet($this->getDatabaseInstance());
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
