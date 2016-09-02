<?php

require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_BatchProgress extends ECash_Models_WritableModel 
	{		
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'batch_progress_id',
				'percent', 'message', 'batch', 'company_id', 'viewed' 
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('batch_progress_id');
		}
		public function getAutoIncrement()
		{
			return 'batch_progress_id';
		}
		public function getTableName()
		{
			return 'batch_progress';
		}
	}
?>