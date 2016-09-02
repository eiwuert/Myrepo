<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_PaymentTypeConditionList extends ECash_Models_IterativeModel
{
	protected $keyfile;

	public function getClassName()
	{
		return 'ECash_Models_PaymentTypeCondition';
	}

	public function getTableName()
	{
		return 'payment_type_condition';
	}
	
	// Fixes some weird bug, or at least stops the error message from appearing
	// I'd feel bad about this, but PHP isn't very helpful at debugging this.
	public function __sleep()
	{
	}
}

?>
