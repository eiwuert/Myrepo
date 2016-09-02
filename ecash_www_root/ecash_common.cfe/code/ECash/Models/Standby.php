<?php

class ECash_Models_Standby extends ECash_Models_WritableModel
{
	public function getColumns()
	{
		static $columns = array(
			'date_created', 'application_id', 'process_type', 'company_id'
		);
		return $columns;
	}
	public function getPrimaryKey()
	{
		return array('application_id');
	}
	public function getAutoIncrement()
	{
		return NULL;
	}
	public function getTableName()
	{
		return 'standby';
	}
}
?>
