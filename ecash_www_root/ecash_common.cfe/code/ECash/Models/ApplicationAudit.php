<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_ApplicationAudit extends ECash_Models_WritableModel
	{

		public function getColumns()
		{
			static $columns = array(
				'date_created', 'audit_log_id', 'company_id', 'application_id',
				'table_name', 'column_name', 'secondary_key_value', 'tertiary_key_value',
				'value_before', 'value_after', 'update_process', 'agent_id',

			);
			return $columns;
		}
		public function getPrimaryKey()
		{
			return array('audit_log_id');
		}
		public function getAutoIncrement()
		{
			return 'audit_log_id';
		}
		public function getTableName()
		{
			return 'application_audit';
		}
	}
?>
