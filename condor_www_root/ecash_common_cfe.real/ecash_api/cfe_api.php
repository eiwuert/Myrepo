<?php

require_once(dirname(__FILE__) . '/ecash_api.2.php');
require_once(dirname(__FILE__) . '/business_rules.class.php');
require_once(dirname(__FILE__) . '/interest_calculator.class.php');

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

Class CFE_eCash_API_2 extends eCash_API_2
{
	protected $biz_rules;
	protected $ruleset;
	protected $db;
	private $status_map;
	protected $application_id;
	private $application_status_id;
	protected $company_id;
	protected $company_short;
	protected $date_funded;
	protected $balance_info;
	protected $next_due_info;
	protected $current_due_info;
	private $last_payment_date;
	private $last_payment_amount;
	protected $payoff_amount;
	private $returned_item_count;
	private $loan_status;
	private $status_dates;
	private $agent_id;
	private $paid_out_date;
	protected $loan_type;
	protected $rule_set_id;
	protected $is_react;
	protected $income_monthly;
	protected $fund_amount;
	
	public function __construct($db, $application_id, $company_id = NULL)
	{
		$this->db = $db;

		if(empty($application_id) || ! is_numeric($application_id))
		{
			throw new Exception ('Invalid application_id passed to ' . __CLASS__ );
		}
		else
		{
			$this->application_id = $application_id;
		}
		
		// If the company_id is not provided, look it up.  This is
		// required for event_type maps on a per-company basis.
		if($company_id === NULL || ! is_numeric($company_id))
		{
			$this->company_id = $this->_Get_Company_ID_by_Application();
		}
		else
		{
			$this->company_id = $company_id;
		}
		/**
		 * we use a TON of Business Rules for things, so we may
		 * as well obstantiate it right off.
		 */
		//Using ecash_common Legacy Business Rules.
		$this->biz_rules = new Legacy_Business_Rules($db);
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
		
		require_once('interest_calculator.class.php');
		$interest = Interest_Calculator::calculateDailyInterest($this->rule_set, $amount, $first_date, $last_date);
		
		$this->payoff_amount = number_format($interest + $principal, 2);
		
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
        -- eCashApi File: " . __FILE__ . ", Method: " . __METHOD__ . ", Line: " . __LINE__ . "
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
			$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
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
				require_once('interest_calculator.class.php');
				$num_days = Interest_Calculator::dateDiff($this->date_funded, $this->current_due_info->date_due);
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
		$rules = new Legacy_Business_Rules($this->db);
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
       	-- eCash_API ".__FILE__.":".__LINE__.":".__METHOD__."()
       		SELECT
					lf.fee_amount
			FROM 	lien_fees AS lf
	       	WHERE 	lf.state = '{$state}'";
		
		if($result = $this->db->Query($query))
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
		
		
		//I'm doing this because I hate you, I hate you all!  
		//Also, CSO, and only CSO loans (maybe only CSO loans for DMP, who knows!?!?!) are supposed to include the CSO fees
		//They also want something with puppies or kittens to happen.  Also, this has to be done NOW, its top A#1 priority, so I don't 
		//get the luxury of doing something which isn't a crime against humanity. [#20753]
		//Summary-  TODO: Fix this
		if ($loan_type_short == 'cso_loan')
		{
			$cso_broker_fee = $this->getCurrentRuleValue($loan_type_short,$company_short,'cso_assess_fee_broker');
			
			$svc_chg_apr = round(($svc_chg['svc_charge_percentage'] * 52), 2); 
			
			if($start_stamp && $end_stamp)
			{
				$num_days = round(($end_stamp - $start_stamp) / 60 / 60 / 24 );
			}
			else 
			{
				throw new Exception ("{$loan_type_short} applications require starting and ending timestamps for the relevant time period.");
			}
			
			$num_days = ($num_days < 1) ? 1 : $num_days;
			
			//THAT'S RIGHT, THIS WILL EXPLODE IF WE CHANGE IT FROM A STRAIGHT PERCENTAGE!
			$cso_broker_apr = round((($cso_broker_fee['percent_amount'] / $num_days) * 365),2);
			$apr = $cso_broker_apr + $svc_chg_apr;
		}
		else 
		{
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
		}
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
		$rules = new Legacy_Business_Rules($this->db);
		$loan_type_id = $rules->Get_Loan_Type_For_Company($company_short, $loan_type);
		$rule_set_id  = $rules->Get_Current_Rule_Set_Id($loan_type_id);
		$rule_set     = $rules->Get_Rule_Set_Tree($rule_set_id);
		
		require_once('interest_calculator.class.php');
		$interest_amount = Interest_Calculator::calculateDailyInterest($rule_set, $principal_amount, $first_date, $last_date);
		
		return $interest_amount;
		
	}
	
	
	private function _Get_Company_ID_by_Application()
	{
		$query = "-- ".__CLASS__ .":".__FILE__.":".__LINE__.":".__METHOD__."()
		SELECT company_id FROM application WHERE application_id = '{$this->application_id}' ";

		$result = $this->db->Query($query);

     
		if(!  $row_obj = $result->fetch(PDO::FETCH_OBJ))
		{
			// Set this to false in case the result returned 
			// is something unexpected
			throw new Exception ("Cannot determine the company_id for {$this->application}");
		}
		return $row_obj->company_id;
	}
	
	
	
}
?>
