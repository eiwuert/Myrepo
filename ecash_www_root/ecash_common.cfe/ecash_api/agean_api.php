<?php
require_once(dirname(__FILE__) . '/ecash_api.2.php');
require_once 'business_rules.class.php';

/**
 * Enterprise-level eCash API extension
 * 
 * An enterprise specific extension to the eCash API for Agean.
 *  
 * 
 * NOTE: For Agean, the Current Due is not what they would have to pay
 * today, but what their next scheduled payment is for.  Also, the
 * next_due is almost never going to be set because loans do not renew
 * automatically, so expect it to return null most of the time.
 * 
 * 
 * @author Josef Norgan <josef.norgan@sellingsource.com>
 * @author Brian Ronakd <brian.ronald@sellingsource.com>
*/

class Agean_eCash_API_2 extends eCash_API_2
{
	public function __construct($db, $application_id, $company_id = NULL)
	{
		parent::__construct($db, $application_id, $company_id);

		/**
		 * Agean uses a TON of Business Rules for things, so we may
		 * as well obstantiate it right off.
		 */
		$this->biz_rules = new ECash_BusinessRules($db);
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
		// For JiffyCash, we don't calculate daily intetrest
		// so the parent method is sufficient
		if($this->company_short === 'jiffy')
		{
			return parent::_Get_Payoff_Amount();
		}

		// Get the rule set
		if(empty($this->rule_set))
		{
			if(empty($this->rule_set_id))
			{
				$this->_Get_Application_Info($this->application_id, TRUE);
			}
			
			$this->rule_set = $this->biz_rules->Get_Rule_Set_Tree($this->rule_set_id);
		}

		$balance_info = $this->Get_Balance_Information();
		$principal = $balance_info->principal_pending;
		$fee       = $balance_info->fee_pending;
		$amount = $principal + $fee;

		/**
		 * For GForge #23749, if the total pending balance is less than
		 * or equal to zero and if the status is Inactive paid, then we'll
		 * return a $0 balance for the payoff amount. [BR]
		 */
		$status = $this->Get_Application_Status_Chain();
		if($balance_info->total_pending <= 0 && $status = 'paid::customer::*root')
		{
			$this->payoff_amount = 0;
			return;
		}

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
		$current_due_date = $this->Get_Current_Due_Date();
		if(!empty($current_due_date))
		{
			// Find the next due date
			$last_date = ($this->income_direct_deposit != 'no')?$this->getPDC()->Get_Last_Business_Day($current_due_date):$current_due_date;
		}
		$payoff_date = $this->getPDC()->Get_Next_Business_Day(date("Y-m-d",time()));
		
		if (!empty($amount) && !empty($first_date) && !empty($payoff_date)) 
		{
			$rate_calc = $this->getRateCalculator();
			$interest = $rate_calc->calculateCharge(
				$amount, 
				$first_date, 
				$payoff_date); // Last Date needs to be the next Businessday if paid off today [#18805] [VT]
		}
		
		$this->payoff_amount = round($interest + $principal,2);
	}

	/**
	 * Returns the Agean Delinquency Date
	 *
	 * @DEPRICATED replaced by ECash_Data_Application::getDelinquencyDate($application_id, $max_failures), currently a hackish way to call that class
	 * @param integer $application_id
	 * 
	 * @return string 
	 */
	public function getDelinquencyDate($application_id)
	{
		$app_info = $this->_Get_Application_Info($application_id, TRUE);
		$rules = new ECash_BusinessRules($this->db);
		$loan_type_id = $rules->Get_Loan_Type_For_Company($this->company_short, $this->loan_type);
		$rule_set_id  = $rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rule_set     = $rules->Get_Rule_Set_Tree($rule_set_id);
		$max_failures = (isset($rule_set['max_svc_charge_failures'])) ? $rule_set['max_svc_charge_failures'] : 2;
		$app_data = new ECash_Data_Application($this->db);
		return $app_data->getDelinquencyDate($application_id, $max_failures);
	}

	/**
	 * Adds the application to a named queue for immediate availability.
	 * 
	 * This function is not safe for an automated queue. You should pass a 
	 * QUEUE_* constant as the $queue_name parameter.
	 *
	 * @param string $queue_name
	 */
	public function Push_To_Queue($queue_name) {
		//this is lame and a hack because ECash object is not created for this
		$query = "
			Select queue_id, control_class from n_queue where name = {$this->db->quote($queue_name)} and (company_id = {$this->company_id} or company_id is null)
	        ";
	    $result = $this->db->query($query);
		if($row = $result->fetch(PDO::FETCH_OBJ))
		{
			if($row->control_class == 'BasicQueue')
			{
				$table = 'n_queue_entry';
				$query = "insert into {$table} (queue_id, agent_id, related_id, date_queued, date_available, priority, dequeue_count)
						  values ({$row->queue_id}, {$this->Get_Agent_Id()}, '{$this->application_id}', '". date("Y-m-d H:i:s") . "', '". date("Y-m-d H:i:s") . "', 100, 0)";
				
			}
			else
			{
				//[#48296] Payouts/Paydowns didn't have the loan_type_id set
				//so they weren't showing up in the queue count
				$rules = new ECash_BusinessRules($this->db);
				$loan_type_id = $rules->Get_Loan_Type_For_Company($this->company_short, $this->loan_type);
			
				$table = 'n_time_sensitive_queue_entry';
				$query = "insert into {$table} (queue_id, agent_id, related_id, loan_type_id, date_queued, date_available, priority, dequeue_count, start_hour, end_hour)
						  values ({$row->queue_id}, {$this->Get_Agent_Id()}, '{$this->application_id}', '{$loan_type_id}', '". date("Y-m-d H:i:s") . "', '". date("Y-m-d H:i:s") . "', 100, 0, 8, 20)";
			}
			$this->db->exec($query);
			return true;
		}
		return false;
	}

	/**
	 * Override to add delinquency date
	 */
	protected function getRateCalculator()
	{
		if(!$this->rate_calculator)
		{
			$this->_Get_Rule_Set();
			$ratebuilder = new ECash_Transactions_RateCalculatorBuilder($this->rule_set,
																		$this->loan_type,
																		$this->countNumberPaidApplications($this->application_id),
																		$this->rate_override,
																		$this->getDelinquencyDate($this->application_id));
			$this->rate_calculator = $ratebuilder->buildRateCalculator();
		}
		return $this->rate_calculator;		
	}
	
}
?>
