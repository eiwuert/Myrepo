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

		public function getTableName()
		{
			return 'transaction_history';
		}
	}
?>