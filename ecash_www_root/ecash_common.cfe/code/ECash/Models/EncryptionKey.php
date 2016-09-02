<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_EncryptionKey extends ECash_Models_WritableModel
{
	public function getColumns()
	{
		static $columns = array(
				'date_modified', 'date_created', 'encryption_key_id',
				'key_data', 'fs_key_id', 'active_status'
		);
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('encryption_key_id');
	}

	public function getAutoIncrement()
	{
		return 'encryption_key_id';
	}

	public function getTableName()
	{
		return 'encryption_key';
	}
}
?>
