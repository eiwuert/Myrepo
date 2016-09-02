<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_FlagTypeList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_FlagType';
		}
		public function createInstance(array $db_row, array $override_dbs = NULL)
		{
			$model = new ECash_Models_FlagType($this->getDatabaseInstance());
			$model->fromDbRow($db_row);
			return $model;
		}
		public function loadBy(array $where_args)
		{
			$query = "SELECT * FROM flag_type " . self::buildWhere($where_args);
			$this->statement = DB_Util_1::queryPrepared(
					$this->getDatabaseInstance(),
					$query,
					$where_args
			);
		}
	}

?>
