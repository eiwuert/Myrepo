<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_CardInfoList extends ECash_Models_IterativeModel
{
	protected $keyfile;

	public function getClassName()
	{
		return 'ECash_Models_CardInfo';
	}

	public function getTableName()
	{
		return 'card_info';
	}

	// Fixes some weird bug, or at least stops the error message from appearing
	// I'd feel bad about this, but PHP isn't very helpful at debugging this.
	public function __sleep()
	{
	}
}

?>
