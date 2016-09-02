<?php

class ECash_Models_ExtCollections extends ECash_Models_WritableModel
{
	public function getColumns()
	{
		static $columns = array(
			'date_modified', 'date_created', 'company_id', 'application_id', 'ext_collections_id',
			'ext_collections_batch_id', 'current_balance',
		);
		return $columns;
	}
	public function getPrimaryKey()
	{
		return array('ext_collections_id');
	}
	public function getAutoIncrement()
	{
		return 'ext_collections_id';
	}
	public function getTableName()
	{
		return 'ext_collections';
	}
}
?>
