<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_EventTransactionList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_EventTransaction';
		}
		
		public function getTableName()
		{
			return 'event_transaction';
		}
		
	}

?>