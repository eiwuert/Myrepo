<?php
/**
* @package Ecash.Models
*/
class ECash_Models_CardAudit extends ECash_Models_WritableModel  
{
	public function getColumns()
	{
		static $columns = array('date_created', 'company_id', 'application_id', 'table_name', 'column_name',
					'value_before', 'value_after', 'agent_id', 'audit_log_id');
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
		return 'card_audit';
	}
}

?>
