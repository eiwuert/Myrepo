<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_QueueGroupList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_QueueGroup';
		}
		
		public function getTableName()
		{
			return 'n_queue_group';
		}
		
	}
?>