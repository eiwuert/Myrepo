<?php

class Analysis_Models_Customer extends DB_Models_WritableModel_1
{		
	public function getColumns()
	{
		static $columns = array(
			'customer_id',
			'company_id',
			'application_id',
			'cashline_id',
			'ssn',
			'name_last',
			'name_first',
			'name_middle',
			'phone_home',
			'phone_cell',
			'phone_work',
			'employer_name',
			'address_street',
			'address_unit',
			'address_city',
			'address_state',
			'address_zipcode',
			'drivers_license',
			'ip_address',
			'email_address',
			'date_origination',
			'dob',
			'pay_frequency',
			'income_monthly',
			'bank_aba',
			'bank_account',
		);
		return $columns;
	}
	public function getPrimaryKey()
	{
		return array('customer_id');
	}
	public function getAutoIncrement()
	{
		return 'customer_id';
	}
	public function getTableName()
	{
		return 'customer';
	}

	/**
	 * Overridden method, simplifying the call 
	 * to reduce overhead since how Analytics uses
	 * the models requires fewer checks.
	 */
	public function __set($name, $value)
	{
 		if ($this->column_data[$name] !== $value)
		{
			$this->column_data[$name] = $value;
			$this->altered_columns[$name] = $name;
		}
	}

	/**
	 * Overridden method, simplifying the call 
	 * to reduce overhead since how Analytics uses
	 * the models requires fewer checks.
	 */
	protected function quoteFields(array $fields, DB_IConnection_1 $db = NULL)
	{
		return $fields;
	}
}
?>
