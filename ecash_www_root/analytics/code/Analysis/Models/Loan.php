<?php

class Analysis_Models_Loan extends DB_Models_WritableModel_1
{		
	public function getColumns()
	{
		static $columns = array(
			'loan_id',
			'application_id',
			'customer_id',
			'company_id',
			'status_id',
			'date_advance',
			'date_first_payment',
			'date_application_sold',
			'fund_amount',
			'amount_paid',
			'principal_paid',
			'fees_accrued',
			'fees_paid',
			'loan_balance',
			'collection_fees',
			'collection_principal',
			'first_return_pay_cycle',
			'current_cycle',
			'loan_number',
			'date_loan_paid',
			'first_return_code',
			'first_return_msg',
			'first_return_date',
			'last_return_code',
			'last_return_msg',
			'last_return_date',
			'campaign_short',
			'promo_id',
			'promo_id_first',
			'promo_id_final',
			'lead_price',
		);
		return $columns;
	}
	public function getPrimaryKey()
	{
		return array('loan_id');
	}
	public function getAutoIncrement()
	{
		return 'loan_id';
	}
	public function getTableName()
	{
		return 'loan';
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
