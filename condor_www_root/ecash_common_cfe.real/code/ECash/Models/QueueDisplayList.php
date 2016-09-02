<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_QueueDisplayList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_QueueDisplay';
		}
		
		public function createInstance(array $db_row, array $override_dbs = NULL)
		{
			$queue = new ECash_Models_QueueDisplay($this->getDatabaseInstance());
			$queue->fromDbRow($db_row);

			return $queue;
		}
		
		
		public function loadAll($queue_id_asc = TRUE)
		{
			$query = 'select * from n_queue_display order by queue_id ' . ($queue_id_asc ? 'asc' : 'desc');

			$this->statement = $this->getDatabaseInstance()->query($query);
		}
		
	}

?>