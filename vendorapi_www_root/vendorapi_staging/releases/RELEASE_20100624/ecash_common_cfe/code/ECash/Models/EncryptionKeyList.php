<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_EncryptionKeyList extends ECash_Models_IterativeModel
{
	public function getClassName()
	{
		return 'ECash_Models_EncryptionKey';
	}

	public function createInstance(array $db_row, array $override_dbs = NULL)
	{
		$model = new ECash_Models_EncryptionKey($this->getDatabaseInstance());
		$model->fromDbRow($db_row);
		return $model;
	}

	// Fixes some weird bug, or at least stops the error message from appearing
	// I'd feel bad about this, but PHP isn't very helpful at debugging this.
	public function __sleep()
	{
	}

	public function getTableName()
	{
		return 'encryption_key';
	}
	public function loadBy(array $where_args = array())
	{
		$query = "SELECT * FROM encryption_key " . self::buildWhere($where_args);
		return ($this->statement = DB_Util_1::queryPrepared(
				$this->getDatabaseInstance(),
				$query,
				$where_args
		));
	}
}

?>
