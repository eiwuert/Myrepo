<?php
	class ECash_Models_QueueConfigList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_QueueConfig';
		}

		public function getTableName()
		{
			return 'n_queue_config';
		}

		public function loadByQueueId($queue_id)
		{
/*                      $query = "
				select
					distinct qc1.config_key as config_key,
					IF(qc2.queue_id IS NULL,
						qc1.config_value,
						qc2.config_value
					) as config_value,
					IF(qc2.queue_id IS NULL,
						qc1.queue_id,
						qc2.queue_id
					) queue_id,
					IF(qc2.queue_id IS NULL,
						NULL,
						qc2.queue_config_id
					) queue_config_id
				from n_queue_config qc1
				left join (
					select
						queue_config_id,
						queue_id,
						config_key,
						config_value
					from n_queue_config qc0
					where qc0.queue_id = ?
				) qc2 on (qc2.config_key = qc1.config_key)
				where qc1.queue_id in (0, ?)
			";*/

			$query ='SELECT * FROM n_queue_config WHERE queue_id IN (0, ?)';
			$this->statement = DB_Util_1::queryPrepared(
					$this->getDatabaseInstance(),
					$query,
					array($queue_id)
			);
		}
		//This is here to fix a bug that exists in php that was causing a Exception thrown without a stack frame in Unknown on line 0,
		//a future version of php may fix this 
		function __sleep(){

		}
	}
?>
