<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_CardTypeList extends ECash_Models_IterativeModel
{
	public function getClassName()
	{
		return 'ECash_Models_CardType';
	}

	public function getTableName()
	{
		return 'card_type';
	}
}

?>
