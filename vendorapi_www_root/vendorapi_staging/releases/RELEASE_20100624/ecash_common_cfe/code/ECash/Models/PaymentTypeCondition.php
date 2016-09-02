<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_PaymentTypeCondition extends ECash_Models_WritableModel implements ECash_Models_IHasPermanentData 
{
	public function getColumns()
	{
		static $columns = array(
				'date_modified', 'date_created', 'payment_type_id', 'loan_type_id',
				'payment_type_condition_id', 'lvalue', 'operator', 'rvalue', 'payment_type_condition_type',
				'is_active'
		);
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('payment_type_condition_id');
	}

	public function getAutoIncrement()
	{
		return 'payment_type_condition_id';
	}

	public function getTableName()
	{
		return 'payment_type_condition';
	}
}
?>
