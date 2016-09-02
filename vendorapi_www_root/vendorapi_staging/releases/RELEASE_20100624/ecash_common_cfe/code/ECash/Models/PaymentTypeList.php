<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_PaymentTypeList extends ECash_Models_IterativeModel
{
	protected $keyfile;

	public function getClassName()
	{
		return 'ECash_Models_PaymentType';
	}

	public function getTableName()
	{
		return 'payment_type';
	}

	// Fixes some weird bug, or at least stops the error message from appearing
	// I'd feel bad about this, but PHP isn't very helpful at debugging this.
	public function __sleep()
	{
	}

	public function loadAll()
	{
		$query = "SELECT * FROM payment_type";

		return ($this->statement = DB_Util_1::queryPrepared(
				$this->getDatabaseInstance(),
				$query,
				array()
		));
	}

}

?>
