<?php
require_once(substr(__FILE__, 0 , strripos(__FILE__, "/") + 1) . 'ecash_api.2.php');
require_once LIB_DIR . 'business_rules.class.php';
require_once("interest_calculator.class.php");

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

Class Agean_eCash_API_2 extends eCash_API_2
{
	protected $biz_rules;
	protected $ruleset;

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
		// so the parent method used by CLK is sufficient
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
		require_once('interest_calculator.class.php');
		$payoff_date = $this->getPDC()->Get_Next_Business_Day(date("Y-m-d",time()));
		
		if (!empty($amount) && !empty($first_date) && !empty($payoff_date)) 
		{	
			$delinquency_date = $this->getDelinquencyDate($this->application_id);
			$interest = Interest_Calculator::calculateDailyInterest(
				$this->rule_set, 
				$amount, 
				$first_date, 
				$payoff_date, // Last Date needs to be the next Businessday if paid off today [#18805] [VT]
				NULL, 
				$delinquency_date);
		}
		
		$this->payoff_amount = round($interest + $principal,2);
	}

	/**
	 * Calculates the maximum loan amount the applicant can receive
	 * 
	 * This uses the LoanAmountCalculator class which currently
	 * is Agean specific and uses the loan type to determine which
	 * formula to use and the applicant's business rules for rates.
	 * 
	 * @return integer $max_loan_amount
	 */
	public function calculateMaxLoanAmount()
	{
		if(empty($this->rule_set))
		{
			if(empty($this->rule_set_id))
			{
				$this->_Get_Application_Info($this->application_id, TRUE);
			}
			
			$this->rule_set = $this->biz_rules->Get_Rule_Set_Tree($this->rule_set_id);
		}
		
		$data = new stdClass;
		$data->loan_type_name = $this->loan_type_description;
		$data->business_rules = $this->rule_set;
		$data->income_monthly = $this->income_monthly;
		$data->is_react       = $this->is_react;
		$data->num_paid_applications = $this->countNumberPaidApplications();

		require_once('loan_amount_calculator.class.php');

		$loan_amount_calc = LoanAmountCalculator::Get_Instance($this->db, $this->company_short);
		return $loan_amount_calc->calculateMaxLoanAmount($data);
	
	}

	/**
	 * Returns an array of the available loan amount choices for the applicant
	 * 
 	 * This uses the LoanAmountCalculator class which currently
	 * is Agean specific and uses the loan type to determine which
	 * formula to use and the applicant's business rules for rates.
	 *
	 * @return array
	 */
	public function calculateLoanAmountsArray()
	{
		if(empty($this->rule_set))
		{
			if(empty($this->rule_set_id))
			{
				$this->_Get_Application_Info($this->application_id, TRUE);
			}
			
			$this->rule_set = $this->biz_rules->Get_Rule_Set_Tree($this->rule_set_id);
		}
		
		$data = new stdClass;
		$data->loan_type_name = $this->loan_type_description;
		$data->business_rules = $this->rule_set;
		$data->income_monthly = $this->income_monthly;
		$data->is_react       = $this->is_react;
		$data->num_paid_applications = $this->countNumberPaidApplications();
		
		require_once('loan_amount_calculator.class.php');
		
		$loan_amount_calc = LoanAmountCalculator::Get_Instance($this->db, $this->company_short);
		return $loan_amount_calc->calculateLoanAmountsArray($data);
		
	}

	/**
	 * Finds applications with the same ssn of the given application_id
	 * that are in the Inactive Paid status and returns the total count
	 *
	 * @param integer application_id
	 * @return integer $num_paid
	 */
	public function countNumberPaidApplications($application_id=NULL)
	{
		if($application_id === NULL)
		{
			$application_id = $this->application_id;
		}
		
		if(empty($this->status_map))
		{
			$this->_Fetch_Status_Map($this->db);
		}
		
		$paid_status_id = $this->_Search_Status_Map('paid::customer::*root');
		
		$sql = "
        SELECT 	count(application_id) as num_paid
		FROM application
		WHERE ssn = (
						SELECT ssn
						FROM application
						WHERE application_id = {$application_id})
		AND company_id = {$this->company_id} 
		AND application_status_id = {$paid_status_id} ";

		$result = $this->db->query($sql);
		$row = $result->fetch(PDO::FETCH_OBJ);

		return $row->num_paid;
	}

	/**
	 * Fetches the Pay Date Calculator v2
	 *
	 * @return object Pay_Date_Calc_2
	 */
	private function getPDC()
	{
		static $pdc;
		
		if(! is_a($pdc, 'Pay_Date_Calc_2'))
		{
			require_once('/virtualhosts/lib/pay_date_calc.2.php');
			$pdc = new Pay_Date_Calc_2($this->Fetch_Holiday_List());
		}
		
		return $pdc;
	}
	
	/**
	 * Returns the list of holidays in an array.  
	 * Stolen from eCash for the API use.
	 *
	 * @return array $holiday_list
	 */
	private function Fetch_Holiday_List()
	{
		static $holiday_list;
	
		if(empty($holiday_list))
		{
			$query = "
							SELECT  holiday
							FROM    holiday
							WHERE   active_status = 'active'";
		
			$result = $this->db->query($query);	
			$holiday_list = array();
			while( $row = $result->fetch(PDO::FETCH_OBJ) )
			{
				$holiday_list[] = $row->holiday;
			}
		}

		return $holiday_list;
	}

	/**
	 * Returns the current Transfer Fee amount
	 * for a loan type for a given company based
	 * on the company's business rules.
	 *
	 * @param string $company_short - Example: pcal
	 * @param string $loan_type - Example: delaware_title
	 * @return string - Example: 10, 10.55
	 */
	public function getTransferFeeAmount($company_short, $loan_type)
	{
		$transfer_fee = $this->getCurrentRuleValue($loan_type, $company_short, 'moneygram_fee');
		return (! empty($transfer_fee)) ? $transfer_fee : 0;
	}

	/**
	 * Returns the current Delivery Fee amount
	 * for a loan type for a given company based
	 * on the company's business rules.
	 *
	 * @param string $company_short - Example: pcal
	 * @param string $loan_type - Example: delaware_title
	 * @return string - Example: 10, 10.55
	 */
	public function getDeliveryFeeAmount($company_short, $loan_type)
	{
		$delivery_fee = $this->getCurrentRuleValue($loan_type, $company_short, 'ups_label_fee');
		return (! empty($delivery_fee)) ? $delivery_fee : 0;		
	}

	/**
	 * Returns the current Delivery Fee amount
	 * for a loan type for a given company based
	 * on the company's business rules.
	 *
	 * @param string $company_short - Example: pcal
	 * @param string $loan_type - Example: delaware_title
	 * @return string - Example: 10, 10.55
	 */
	public function getReturnFeeAmount($company_short, $loan_type)
	{
		$return_fee = $this->getCurrentRuleValue($loan_type, $company_short, 'return_transaction_fee');
		return (! empty($return_fee)) ? $return_fee : 0;		
	}
	
	/**
	 * Returns the value for the current rule set for a given loan_type
	 *
	 * @param string $loan_type - Example: delaware_title
	 * @param string $company_short - Example: pcal
	 * @param string $rule_name - Example: moneygram_fee
	 * @return string or array depending on if the rule has one or multiple rule component parameters
	 */
	protected function getCurrentRuleValue($loan_type, $company_short, $rule_name)
	{
		$rules = new ECash_BusinessRules($this->db);
		$loan_type_id = $rules->Get_Loan_Type_For_Company($company_short, $loan_type);
		$rule_set_id  = $rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rule_set     = $rules->Get_Rule_Set_Tree($rule_set_id);

		return $rule_set[$rule_name];
	}
	
	/**
	 * Returns the Lien Fee amounts for a given state
	 *
	 * @param string $state - Example: nv
	 * @return string - Example: 10, 10.55
	 */
	public function getLienFeeAmount($state)
	{
		$query = "
       		SELECT
					lf.fee_amount
			FROM 	lien_fees AS lf
	       	WHERE 	lf.state = '{$state}'";
		
		if($result = $this->db->query($query))
		{
			return $result->fetch(PDO::FETCH_OBJ)->fee_amount;
		}

		return 0;		
	}
	
	
	/**
	 * Returns the APR
	 *
	 * @param string $loan_type_short - shortname for loan type
	 * @param string $company_short - shortname for company
	 * @param int $start_stamp - start timestamp for time period
	 * @param int $end_stamp - end timestamp for time period
	 * @return  float - APR rounded to two decimal places
	 */
	public function getAPR($loan_type_short, $company_short, $start_stamp=NULL, $end_stamp=NULL)
	{
		//Normalize timestamps
		$start_stamp = ($start_stamp) ? strtotime(date("m/d/Y", $start_stamp)) : NULL;
		$end_stamp = ($end_stamp) ? strtotime(date("m/d/Y", $end_stamp)) : NULL;
	
		$rule_name = 'service_charge';
		$svc_chg = $this->getCurrentRuleValue($loan_type_short, $company_short, $rule_name);

		$svc_chg_pct  = $svc_chg['svc_charge_percentage'];
		$svc_chg_type = $svc_chg['svc_charge_type'];

		switch(strtolower($svc_chg_type))
		{
			case 'daily':
				$num_days = 7;
				break;
				
			case 'fixed':
				if($start_stamp && $end_stamp)
				{
					$num_days = round(($end_stamp - $start_stamp) / 60 / 60 / 24 );
				}
				else 
				{
					throw new Exception ("{$loan_type_short} applications require starting and ending timestamps for the relevant time period.");
				}
				break;	
			default:
				throw new Exception("getAPR() called with no business rule for service charge type!");
		}
		$num_days = ($num_days < 1) ? 1 : $num_days;
		
		$apr = round( (($svc_chg_pct / $num_days) * 365), 2);
		
		return $apr;
	}

	/**
	 * Returns the estimated Interest / Service charge amount
	 *
	 * Example:  getInterestChargeAmount($loan_type, $company, $fund_amount, $fund_estimated, $first_due_date);
	 * 
	 * @param string $company_short - shortname for company
	 * @param string $loan_type_short - shortname for loan type
	 * @param integer $principal_amount
	 * @param string $first_date - Example: YY-mm-dd
	 * @param string $last_date - Example: YY-mm-dd
	 * @return float
	 */
	public function getInterestChargeAmount($company_short, $loan_type, $principal_amount, $first_date, $last_date)
	{
		$rules = new ECash_BusinessRules($this->db);
		$loan_type_id = $rules->Get_Loan_Type_For_Company($company_short, $loan_type);
		$rule_set_id  = $rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rule_set     = $rules->Get_Rule_Set_Tree($rule_set_id);
		
		require_once('interest_calculator.class.php');
		$interest_amount = Interest_Calculator::calculateDailyInterest($rule_set, $principal_amount, $first_date, $last_date);
		
		return $interest_amount;
		
	}
	
	/**
	 * Returns the Agean Delinquency Date
	 *
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
		
		// This should be better documented [benb]
		$query = "
       	-- eCash_API ".__FILE__.":".__LINE__.":".__METHOD__."()
       		SELECT
			CASE
				WHEN
				(
				SELECT LEAST(IFNULL((SELECT transaction_register.date_effective from transaction_register
				JOIN ach USING (ach_id)
				JOIN ach_return_code as arc USING (ach_return_code_id)
				where transaction_register.application_id = tr.application_id
				AND arc.is_fatal = 'yes'
				AND ach.ach_type != 'credit'
				ORDER BY transaction_register.date_effective ASC LIMIT 1), '2222:22:22')
				,
				IFNULL((SELECT transaction_register.date_effective from transaction_register
				WHERE transaction_register_id = (
					select origin_id from transaction_register
					JOIN event_schedule as es USING (event_schedule_id)
					where transaction_register.application_id = tr.application_id
					AND transaction_status = 'failed'
					AND context = 'reattempt'
					ORDER BY transaction_register.date_effective ASC LIMIT 1)), '2222:22:22')
				)
				) != '2222:22:22'
				THEN
				(
				SELECT LEAST(IFNULL((SELECT transaction_register.date_effective from transaction_register
				JOIN ach USING (ach_id)
				JOIN ach_return_code as arc USING (ach_return_code_id)
				where transaction_register.application_id = tr.application_id
				AND arc.is_fatal = 'yes'
				AND ach.ach_type != 'credit'
				ORDER BY transaction_register.date_effective ASC LIMIT 1), '2222:22:22')
				,
				IFNULL((SELECT transaction_register.date_effective from transaction_register
				WHERE transaction_register_id = (
					select origin_id from transaction_register
					JOIN event_schedule as es USING (event_schedule_id)
					where transaction_register.application_id = tr.application_id
					AND transaction_status = 'failed'
					AND context = 'reattempt'
					ORDER BY transaction_register.date_effective ASC LIMIT 1)), '2222:22:22')
				)
				)
			ELSE
			(
			IF(
			# if the last failure comes after the last complete transaction, return the failure's date effective
			(select tr_1.date_effective from transaction_register as tr_1
			JOIN transaction_type as tt USING (transaction_type_id)
			JOIN event_schedule USING (event_schedule_id)
			where tr_1.application_id = tr.application_id
			AND (((tt.clearing_type = 'external') AND (tt.affects_principal = 'no'))               
			OR ((tt.clearing_type = 'ach') AND (tt.affects_principal = 'no')) 
			OR (tt.affects_principal = 'yes'))
			AND tr_1.transaction_status = 'complete'
			AND ( event_schedule.context != 'arrangement' AND event_schedule.context != 'partial' )
			ORDER BY tr_1.date_effective DESC LIMIT 1)
			<
			(select transaction_register.date_effective from transaction_register
			where transaction_register.application_id = tr.application_id
			AND transaction_status = 'failed'
			ORDER BY transaction_register.date_effective DESC LIMIT 1)
			,
			# Do Checks to determine failure type and return effective date accordingly
			( SELECT IF(
		        (SELECT COUNT(transaction_register.date_effective)
		        FROM transaction_register where transaction_register.application_id = tr.application_id
			      AND transaction_status = 'failed') > {$max_failures},
		        (SELECT transaction_register.date_effective
		        FROM transaction_register where transaction_register.application_id = tr.application_id
			      AND transaction_status = 'failed'
		        ORDER BY transaction_register.date_effective ASC LIMIT 1),
		        (SELECT transaction_register.date_effective
		        FROM transaction_register where transaction_register.application_id = tr.application_id
		      	AND transaction_status = 'failed'
		        ORDER BY transaction_register.date_effective DESC LIMIT 1))
			)
			,
			# return next scheduled transaction due date
			(select event_schedule.date_effective from event_schedule
			where event_schedule.application_id = tr.application_id
			AND (amount_principal + amount_non_principal) < 0
			AND event_status = 'scheduled'
			ORDER BY event_schedule.date_effective ASC LIMIT 1)
			))END as delinquency_date
			FROM application as app
			JOIN transaction_register as tr USING (application_id)
			WHERE app.application_id =  '{$application_id}'";
		
		if($result = $this->db->query($query))
		{
			return $result->fetch(PDO::FETCH_OBJ)->delinquency_date;
		}
		return NULL;	
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
			
				$table = 'n_time_sensitive_queue_entry';
				$query = "insert into {$table} (queue_id, agent_id, related_id, date_queued, date_available, priority, dequeue_count, start_hour, end_hour)
						  values ({$row->queue_id}, {$this->Get_Agent_Id()}, '{$this->application_id}', '". date("Y-m-d H:i:s") . "', '". date("Y-m-d H:i:s") . "', 100, 0, 8, 20)";
			}
			$this->db->exec($query);
			return true;
		}
		return false;
	}
	

}
?>
