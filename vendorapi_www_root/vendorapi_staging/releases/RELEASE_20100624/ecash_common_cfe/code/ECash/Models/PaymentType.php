<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_PaymentType extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
{
	public function getColumns()
	{
		static $columns = array(
				'date_modified', 'date_created', 'payment_type_id',
				'name', 'name_short', 'is_active'
		);
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('payment_type_id');
	}

	public function getAutoIncrement()
	{
		return 'payment_type_id';
	}

	public function getTableName()
	{
		return 'payment_type';
	}
}
?>
