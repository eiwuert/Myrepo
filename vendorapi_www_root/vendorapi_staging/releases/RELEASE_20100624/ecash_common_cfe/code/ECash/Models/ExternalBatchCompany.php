<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_ExternalBatchCompany extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
{
	public function getColumns()
	{
		static $columns = array(
				'date_modified', 'date_created', 'external_batch_company_id',
				'company_id', 'loan_type_id', 'external_batch_report_id'
		);
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('external_batch_company_id');
	}

	public function getAutoIncrement()
	{
		return 'external_batch_company_id';
	}

	public function getTableName()
	{
		return 'external_batch_company';
	}
}
?>
