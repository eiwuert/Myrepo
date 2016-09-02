<?php
	require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_ProcessLog extends ECash_Models_WritableModel
	{
		public $ProcessLog;
		public function getColumns()
		{
			static $columns = array(
				'business_day', 'company_id', 'process_log_id',
				'step', 'state', 'date_modifed', 'date_started' 
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('process_log_id');
		}
		public function getAutoIncrement()
		{
			return 'process_log_id';
		}
		public function getTableName()
		{
			return 'process_log';
		}
	}
?>