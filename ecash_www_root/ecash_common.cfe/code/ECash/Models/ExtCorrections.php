<?php

class ECash_Models_ExtCorrections extends ECash_Models_WritableModel
{
	public function getColumns()
	{
		static $columns = array(
			'date_modified', 'date_created', 'ext_corrections_id', 'company_id', 'application_id',
			'old_balance', 'adjustment_amount', 'new_balance', 'file_name', 'file_contents', 'download_count',
		);
		return $columns;
	}
	public function getPrimaryKey()
	{
		return array('ext_corrections_id');
	}
	public function getAutoIncrement()
	{
		return 'ext_corrections_id';
	}
	public function getTableName()
	{
		return 'ext_corrections';
	}
}
?>
