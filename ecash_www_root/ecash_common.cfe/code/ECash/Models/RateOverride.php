<?php

/**
 * Used for AALM and OPM's overridable service charge rates
 * 
 * @package Ecash.Models
 */
class ECash_Models_RateOverride extends ECash_Models_ObservableWritableModel
{
	public function getColumns()
	{
		static $columns = array(
			'application_id', 'rate_override'
		);
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('application_id');
	}

	public function getTableName()
	{
		return 'rate_override';
	}

	public function getAutoIncrement()
	{
		return NULL;
	}
}
?>
