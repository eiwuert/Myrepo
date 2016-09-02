<?php

/**
* @package Ecash.Models
*/
class ECash_Models_ApplicationDateEffective extends ECash_Models_WritableModel  
{
	public function getColumns()
	{
		static $columns = array('date_modified', 'date_created', 'application_date_effective_id',
					'application_id', 'date_effective_before', 'date_effective_after'
		);
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('application_date_effective_id');
	}

	public function getAutoIncrement()
	{
		return 'application_date_effective_id';
	}

	public function getTableName()
	{
		return 'application_date_effective';
	}
}

?>
