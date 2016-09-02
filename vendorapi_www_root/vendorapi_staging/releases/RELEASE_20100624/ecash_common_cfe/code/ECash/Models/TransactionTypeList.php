<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_TransactionTypeList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_TransactionType';
		}

		public function getTableName()
		{
			return 'transaction_type';
		}

	}

?>