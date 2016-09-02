<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_AchList extends ECash_Models_IterativeModel
{
	protected $keyfile;

	public function getClassName()
	{
		return 'ECash_Models_Ach';
	}

	public function getTableName()
	{
		return 'ach';
	}
		
	public function createInstance(array $db_row, array $override_dbs = NULL)
	{
		$model = new ECash_Models_Ach($this->getDatabaseInstance());
		$model->fromDbRow($db_row);
		return $model;
	}

	// Fixes some weird bug, or at least stops the error message from appearing
	// I'd feel bad about this, but PHP isn't very helpful at debugging this.
	public function __sleep()
	{
	}

	public function loadAll()
	{
		$query = "SELECT * FROM ach";
		$this->statement = DB_Util_1::queryPrepared(
				$this->getDatabaseInstance(),
				$query,
				array()
		);
	}
}

?>
