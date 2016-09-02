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

		public function getTableName()
		{
			return 'transaction_amount';
		}
	}
?>