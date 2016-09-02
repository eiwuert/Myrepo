<?php

class ECash_Models_AchException extends ECash_Models_WritableModel
{
	public function getColumns()
	{
		static $columns = array(
			'date_modified', 'date_created', 'ach_exception_id', 'return_date', 'recipient_id',
			'recipient_name', 'ach_id', 'debit_amount', 'credit_amount', 'reason_code', 'company_id'
		);
		return $columns;
	}
	public function getPrimaryKey()
	{
		return array('ach_exception_id');
	}
	public function getAutoIncrement()
	{
		return 'ach_exception_id';
	}
	public function getTableName()
	{
		return 'ach_exception';
	}
}
?>
