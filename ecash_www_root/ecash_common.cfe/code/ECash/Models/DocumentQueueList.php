<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_DocumentQueueList extends ECash_Models_IterativeModel
	{
		protected $limit;
		public function getClassName()
		{
			return 'ECash_Models_DocumentQueue';
		}

		public function getTableName()
		{
			return 'document_queue';
		}
	
		//needed to fix php bug in deconstructor that caused a Fatal error: Exception thrown without a stack frame in Unknown on line 0
		public function __sleep()
		{
		}

	}
?>
