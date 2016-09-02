<?php

require_once(dirname(__FILE__) . '/ecash_api.2.php');

/**
 * Enterprise-level eCash API extension
 * 
 * An enterprise specific extension to the eCash API for CFE.
 *  
 * This API is basically a mix of the Agean API meant for newer
 * eCash Commercial / CFE Customers.
 * 
 * 
 * @author Will! Parker <william.parker@cubisfinancial.com>
 * 
 */

class CFE_eCash_API_2 extends eCash_API_2
{	
	protected $rate_override;

	public function __construct($db, $application_id, $company_id = NULL)
	{
		parent::__construct($db, $application_id, $company_id);
		/**
		 * we use a TON of Business Rules for things, so we may
		 * as well obstantiate it right off.
		 */
		$this->biz_rules = new ECash_BusinessRulesCache($db);
	}
	
	/**
	 * Overloaded method for fetching the Payoff Amount for Agean Customers
	 * - Sets $this->payoff_amount
	 * - Uses either the last payment date or the business day after the
	 *   Fund date to determine the time period to calculate interest from.
	 *
	 */
	protected function _Get_Payoff_Amount()
	{
		
		// Get the rule set
		if(empty($this->rule_set))
		{
			if(empty($this->rule_set_id))
			{
				$this->_Get_Application_Info($this->application_id, TRUE);
			}
			
			$this->rule_set = $this->biz_rules->Get_Rule_Set_Tree($this->rule_set_id);
		}

		// Find the amounts
		$balance_info = $this->Get_Balance_Information();
		$principal = $balance_info->principal_pending;
		$fee       = $balance_info->fee_pending;
		
		$amount = $principal + $fee;

		// Get the first date of the calculation
		if($last_due_date = $this->Get_Last_Payment_Date())
		{
			$first_date = $last_due_date;
		}
		else
		{
			$fund_date = $this->Get_Date_Funded();
			$first_date = $this->getPDC()->Get_Next_Business_Day($fund_date);
		}

		// Find the next business day
		$last_date = $this->getPDC()->Get_Next_Business_Day(date('Y-m-d'));

		$rate_calc = $this->getRateCalculator();
		$interest = $rate_calc->calculateCharge($amount, $first_date, $last_date);
		
		$this->payoff_amount = number_format($interest + $principal, 2);
		
	}
	
	public function getLenderFee($fee_type, $company_short=NULL, $loan_type)
	{
		if(!$company_short)
		{
			$company_short = $this->company_short;
		}
		
		switch($fee_type)
		{
			case 'bank':
				$rule_name = 'lend_assess_fee_ach';
				break;
			case 'late':
				$rule_name = 'lend_assess_fee_late';
				break;
		}
		
		$fee_rules = $this->getCurrentRuleValue($loan_type, $company_short, $rule_name);
		$this->_Get_Application_Info($this->application_id, TRUE);
		$this->_Get_Due_Info();

		$type = $fee_rules['amount_type'];
		$pct_amt = $fee_rules['percent_amount'];
		$pct_type = $fee_rules['percent_type'];
		$fixed_amt = $fee_rules['fixed_amount'];
		
		$balance_info = $this->Get_Balance_Information();
		$principal = $balance_info->principal_balance;
		$payment_amount = $this->current_due_info->amount_due;
		
		//Do any math required to compute the fee percentage
		switch($pct_type)
		{
			case 'apr':
				$num_days = Date_Util_1::dateDiff($this->date_funded, $this->current_due_info->date_due);
				$pct = $pct_amt * ($num_days / 365);
				break;
			case 'fixed':
			default:
				$pct = $pct_amt;
				break;
		}
		
		$pct_of_principal = $principal * ($pct / 100);
		$pct_of_payment = $payment_amount * ($pct / 100);
//		amt - Fixed Amount
//		pct of principal - Percentage of Principal owed
//		pct of fund - Percentage of Fund amount
//		amt or pct of prin > - Fixed Amount OR Percentage of Principal owed, Whichever is Higher
//		amt or pct of prin < - Fixed Amount OR Percentage of Principal owed, Whichever is Lower

		switch($type)
		{
			case 'amt':
				$fee = $fixed_amt;
				break;
			case 'pct of principal':
				$fee = $pct_of_principal;
				break;
			case 'pct of fund':
				//get funded amount
				$fee = $this->fund_amount * ($pct / 100);
				break;
			case 'amt or pct of prin >':
				$fee = ($fixed_amt > $pct_of_principal) ? $fixed_amt : $pct_of_principal;
				break;
			case 'amt or pct of prin <':
				$fee = ($fixed_amt < $pct_of_principal) ? $fixed_amt : $pct_of_principal;
				break;
			case 'amt or pct of pymnt >':
				$fee = ($fixed_amt > $pct_of_payment) ? $fixed_amt : $pct_of_payment;
				break;
			case 'amt or pct of pymnt <':
				$fee = ($fixed_amt < $pct_of_payment) ? $fixed_amt : $pct_of_payment;
				break;
		}
		return (!empty($fee)) ? $fee : 0;
	}

	public function getLenderBankFee($company_short=NULL, $loan_type)
	{
		return $this->getLenderFee('bank', $company_short, $loan_type);			
	}
	
	public function getLenderLateFee($company_short=NULL, $loan_type)
	{
		return $this->getLenderFee('late', $company_short, $loan_type);
	}
}
?>
