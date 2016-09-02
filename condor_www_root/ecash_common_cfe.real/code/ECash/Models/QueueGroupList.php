<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_QueueGroupList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_QueueGroup';
		}
		
		public function createInstance(array $db_row)
		{
			$item = new ECash_Models_QueueGroup($this->getDatabaseInstance());
			$item->fromDbRow($db_row);
			return $item;
		}

		public function loadBy(array $where_args, array $override_dbs = NULL)
		{
			$query = "select * from n_queue_group " . self::buildWhere($where_args);

			$this->statement = DB_Util_1::queryPrepared(
					$this->getDatabaseInstance(),
					$query,
					$where_args
			);
		}
		
	}
?>