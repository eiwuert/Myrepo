<?php

/**
* @package Ecash.Models
*/
class ECash_Models_AchProvider extends ECash_Models_WritableModel  
{
	public function getColumns()
	{
		static $columns = array('date_created', 'ach_provider_id', 'active_status', 'name_short', 'name');
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('ach_provider_id');
	}

	public function getAutoIncrement()
	{
		return 'ach_provider_id';
	}

	public function getTableName()
	{
		return 'ach_provider';
	}
}

?>
