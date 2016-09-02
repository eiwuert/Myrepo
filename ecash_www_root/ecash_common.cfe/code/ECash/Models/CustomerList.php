<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_CustomerList extends ECash_Models_IterativeModel
{
	protected $keyfile;

	public function getClassName()
	{
		return 'ECash_Models_Customer';
	}
	
	public function getTableName()
	{
		return 'customer';
	}

	public function createInstance(array $db_row, array $override_dbs = NULL)
	{
		$model = new ECash_Models_Customer($this->getDatabaseInstance());
		$model->fromDbRow($db_row);
		return $model;
	}

	// Fixes some weird bug, or at least stops the error message from appearing
	// I'd feel bad about this, but PHP isn't very helpful at debugging this.
	public function __sleep()
	{
	}

}

?>
