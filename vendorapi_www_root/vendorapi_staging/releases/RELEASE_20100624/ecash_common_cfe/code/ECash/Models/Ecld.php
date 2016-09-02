<?php

class ECash_Models_Ecld extends ECash_Models_WritableModel
{
	public function getColumns()
	{
		static $columns = array(
			'date_modified', 'date_created', 'company_id', 'application_id',
			'ecld_id', 'ecld_file_id', 'ecld_return_id', 'event_schedule_id', 
			'return_reason_code', 'business_date', 'amount', 'bank_aba', 
			'bank_account', 'bank_account_type', 'ecld_status', 'trans_ref_no',
			'transaction_id'
		);
		return $columns;
	}
	public function getPrimaryKey()
	{
		return array('ecld_id');
	}
	public function getAutoIncrement()
	{
		return 'ecld_id';
	}
	public function getTableName()
	{
		return 'ecld';
	}
}
?>