<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_ExternalBatchReport extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
{
	public function getColumns()
	{
		static $columns = array(
				'date_modified', 'date_created', 'external_batch_type_id',
				'external_batch_report_id', 'class_name', 'name_short', 'name'
		);
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('external_batch_report_id');
	}

	public function getAutoIncrement()
	{
		return 'external_batch_report_id';
	}

	public function getTableName()
	{
		return 'external_batch_report';
	}
}
?>
