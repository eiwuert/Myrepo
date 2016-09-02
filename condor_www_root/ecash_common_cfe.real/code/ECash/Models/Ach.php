<?php

class ECash_Models_Ach extends ECash_Models_WritableModel
{
	public function getColumns()
	{
		static $columns = array(
			'date_modified', 'date_created', 'company_id', 'application_id',
			'ach_id', 'ach_batch_id', 'ach_report_id', 'origin_group_id', 
			'ach_date', 'amount', 'ach_type', 'bank_aba', 'bank_account', 
			'bank_account_type', 'ach_status', 'ach_return_code_id', 
			'ach_trace_number', 'transaction_id'
		);
		return $columns;
	}
	public function getPrimaryKey()
	{
		return array('ach_id');
	}
	public function getAutoIncrement()
	{
		return 'ach_id';
	}
	public function getTableName()
	{
		return 'ach';
	}
}
?>