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
		public function getTableName()
		{
			return 'n_queue';
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
	}

?>
