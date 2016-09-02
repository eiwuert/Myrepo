<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_QueueList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_Queue';
		}
		public function createInstance(array $db_row, array $override_dbs = NULL)
		{
			$queue = new ECash_Models_Queue($this->getDatabaseInstance());
			$queue->fromDbRow($db_row);

			return $queue;
		}
		public function loadAvailableQueues($company_id)
		{
			$query = "
				SELECT *
				FROM n_queue
				WHERE
					n_queue.company_id = :company_id
					OR n_queue.company_id IS NULL
			";
			$this->statement = $this->getDatabaseInstance()->queryPrepared($query, array('company_id' => $company_id));
		}
		public function loadBy(array $where_args)
		{
			$query = "SELECT * FROM n_queue " . self::buildWhere($where_args);
			$this->statement = DB_Util_1::queryPrepared(
					$this->getDatabaseInstance(),
					$query,
					$where_args
			);
		}
	}

?>
