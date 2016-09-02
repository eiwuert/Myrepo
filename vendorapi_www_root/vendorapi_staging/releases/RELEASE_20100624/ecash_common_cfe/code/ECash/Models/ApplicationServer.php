<?php

/**
* @package Ecash.Models
*/
class ECash_Models_ApplicationServer extends ECash_Models_WritableModel  
{
	public function getColumns()
	{
		static $columns = array('date_modified', 'date_created', 'application_server_id', 'application_id', 'server_ip');
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('application_server_id');
	}

	public function getAutoIncrement()
	{
		return 'application_server_id';
	}

	public function getTableName()
	{
		return 'application_server';
	}
}

?>
