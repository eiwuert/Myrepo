<?php

class IMPACT_Models_Loan extends Analysis_Models_Loan
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
		
			// IMPACT:
			'model',
			'previous_model',
			'portfolio_tag',
			'verification_agent',
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
}
?>