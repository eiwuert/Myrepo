<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_TransactionHistoryList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_TransactionHistory';
		}

        public function createInstance(array $db_row)
        {
			$class = $this->getClassName();
			$event = new $class($this->getDatabaseInstance());
			$event->fromDbRow($db_row);
			return $event;
        }
        
		public function loadBy(array $where_args)
		{
			$query = 'SELECT * FROM transaction_history ' . self::buildWhere($where_args) . ' ORDER BY transaction_id, date_created DESC';
			
			$this->statement = DB_Util_1::queryPrepared(
					$this->getDatabaseInstance(),
					$query,
					$where_args
			);
		}
	}
?>