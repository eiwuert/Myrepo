<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_EcldList extends ECash_Models_IterativeModel
{
	protected $keyfile;

	public function getClassName()
	{
		return 'ECash_Models_Ecld';
	}

	public function getTableName()
	{
		return 'ecld';
	}
}

?>
