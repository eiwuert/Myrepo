<?php

require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_CurrentQueueStatus extends ECash_Models_WritableModel
	{
		public $QueueStatus;
		
		
		public function getColumns()
		{
			static $columns = array(
				'application_id', 'queue_name', 'cnt', 'date_created', 'date_modified'
			);
			return $columns;
		}
		
		public function getPrimaryKey()
		{
			return array('application_id','queue_name');
		}
		
		public function getAutoIncrement()
		{
			return NULL;
		}
		
		public function getTableName()
		{
			return 'current_queue_status';
		}
	}
?>