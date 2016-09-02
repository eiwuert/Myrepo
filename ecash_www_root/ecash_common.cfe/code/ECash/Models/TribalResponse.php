<?php

/**
* @package Ecash.Models
*/
class ECash_Models_TribalResponse extends ECash_Models_WritableModel  
{
	public function getColumns()
	{
		static $columns = array('date_modified', 'date_created', 'tribal_response_id', 'ssn', 'decision', 'code', 'message');
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('tribal_response_id');
	}

	public function getAutoIncrement()
	{
		return 'tribal_response_id';
	}

	public function getTableName()
	{
		return 'tribal_response';
	}
}

?>
