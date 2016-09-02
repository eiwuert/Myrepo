<?php

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_TransactionAmountList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_TransactionAmount';
		}

        public function createInstance(array $db_row)
        {
                $event = new ECash_Models_TransactionAmount($this->getDatabaseInstance());
                $event->fromDbRow($db_row);
                return $event;
        }
        
		public function loadBy(array $where_args)
		{
			$query = 'SELECT * FROM transaction_amount ' . self::buildWhere($where_args) . ' ORDER BY transaction_id';
			
			$this->statement = DB_Util_1::queryPrepared(
					$this->getDatabaseInstance(),
					$query,
					$where_args
			);
		}
	}
?>