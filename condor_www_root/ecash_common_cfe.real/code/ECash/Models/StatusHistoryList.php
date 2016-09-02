<?php

	class ECash_Models_StatusHistoryList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_StatusHistory';
		}

		public function createInstance(array $db_row)
		{
			$model = new ECash_Models_StatusHistory($this->getDatabaseInstance());
			$model->fromDbRow($db_row);
			return $model;
		}


		public function loadBy(array $where_args)
		{
			$query = "SELECT * FROM status_history " . self::buildWhere($where_args);
			$this->statement = DB_Util_1::queryPrepared(
					$this->getDatabaseInstance(),
					$query,
					$where_args
			);
		}
	}

?>