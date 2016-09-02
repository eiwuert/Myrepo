<?php

	require_once 'WritableModel.php';

	/**
	 * @package Ecash.Models
	 */

	class ECash_Models_SoapLog extends ECash_Models_WritableModel
	{
		public $SoapLog;
		public function getColumns()
		{
			static $columns = array(
				'date_modified', 'date_created', 'company_id', 'soap_log_id',
				'application_id', 'agent_id', 'soap_data', 'soap_response', 
				'type', 'status'
			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('soap_log_id');
		}
		public function getAutoIncrement()
		{
			return 'soap_log_id';
		}
		public function getTableName()
		{
			return 'soap_log';
		}
	}
?>