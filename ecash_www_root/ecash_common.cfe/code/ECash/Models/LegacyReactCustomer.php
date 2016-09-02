<?php

/**
 * @package Ecash.Models
 */
class ECash_Models_LegacyReactCustomer extends ECash_Models_WritableModel  
{
	public function getColumns()
	{
		static $columns = array(
				'date_modified', 'date_created', 'application_id', 'status',
				'ssn', 'legacy_react_customer_id', 'encryption_key_id'
		);
		return $columns;
	}

	public function getPrimaryKey()
	{
		return array('legacy_react_customer_id');
	}

	public function getAutoIncrement()
	{
		return 'legacy_react_customer_id';
	}

	public function getTableName()
	{
		return 'legacy_react_customer';
	}
}
?>
