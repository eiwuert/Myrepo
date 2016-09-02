<?php

require_once(ECASH_COMMON_DIR . "/ecash_api/interest_calculator.class.php");

class ECash_DFA_CompleteScheduleCSO extends ECash_DFA_CompleteScheduleBase
{
	protected $last_sc_date;
	protected $last_debit_date;

	public function __construct()
	{
		//do this first so we can override
		parent::__construct();

		/**
		 * Start any new functions with 100 just to avoid any collisions
		 */
		$this->tr_functions[100] = 'add_cso_fees';
		
		$this->transitions[5] = array( 0 =>  6, 1 => 18);	// use_manual_renewal_rules: 0 => has_fees_or_service_charges, 1 => add_interest_payment
		$this->transitions[37] = array( 1 => 100);			// add_interest_payment: 1 => add_cso_fees
		$this->transitions[100] = array( 1 => 38); 			// add_cso_fees: 1 => shift_dates
	}
	
	// Quick override to do some setup
	public function run($parameters) 
	{
		$this->last_date        = NULL;
		$this->last_sc_date		= $parameters->last_service_charge_date == 'N/A'? $this->fund_date : $parameters->last_service_charge_date;
		return parent::run($parameters);
	}

	protected function getFeeArray()
	{
		$fees = parent::getFeeArray();
		$fees['cso_application'] = array('balance' => 0, 'pay_type' => 'cso_pay_fee_app');
		$fees['cso_broker']      = array('balance' => 0, 'pay_type' => 'cso_pay_fee_broker');
		$fees['cso_late']        = array('balance' => 0, 'pay_type' => 'cso_pay_fee_late');
		$fees['lend_ach']        = array('balance' => 0, 'pay_type' => 'lend_pay_fee_ach');
		return $fees;		
	}
	
	protected function addFee($event_type, &$fees, $fee_amount)
	{
		switch($event_type)
		{	

			case 'cso_assess_fee_broker':
			case 'cso_pay_fee_broker':
				$fees['cso_broker']['balance'] += $fee_amount;
				break;
						
			case 'cso_assess_fee_app':
			case 'cso_pay_fee_app':
				$fees['cso_application']['balance'] += $fee_amount;
				break;
						
			case 'cso_assess_fee_late':
			case 'cso_pay_fee_late':
				$fees['cso_late']['balance'] += $fee_amount;
				break;
						
			case 'lend_assess_fee_ach':
			case 'lend_pay_fee_ach':
				$fees['lend_ach']['balance'] += $fee_amount;
				break;

			default:
				parent::addFee($event_type, &$fees, $fee_amount);
				break;
		}						
	}
	
	/**
	 * Create the CSO related fees.
	 */
	protected function add_cso_fees($parameters)
	{
		$action_date = $this->dates['event'][0];
		$due_date    = $this->dates['effective'][0];
		
		/** Add fees to $parameters->schedule so create_fee_payments() will add appropriate payments **/
		
		// Broker Fee!  Fees are assessed and effective on the event.
		$broker_fee = $this->getCSOFeeEvent($parameters, $action_date, $action_date, 'cso_assess_fee_broker', 'cso_assess_fee_broker', 'CSO Broker Fee');
		//Broker fee payment
		foreach($broker_fee->amounts as $a)
		{
			if($a->event_amount_type === 'fee')
			{
				$total_fees -= $a->amount;
				$new_amounts[] = Event_Amount::MakeEventAmount('fee', -$a->amount);
			}
		}
		//This is for the NEXT period!
		$broker_payment = Schedule_Event::MakeEvent($this->dates['event'][1], $this->dates['effective'][1],
 								$new_amounts,'cso_pay_fee_broker', 'Broker Fee Payment','scheduled','generated');
		
				
		//Add them to the events we're gonna register
 		$this->new_events[] = $broker_fee;
		$this->new_events[] = $broker_payment;
		
		//Add them to the schedule for future calculations.
		$parameters->schedule[] = $broker_fee;
		$parameters->schedule[] = $broker_payment;
		
		return 1;
		
	}
	
	/**
	 * Function used to create CSO Fee Events
	 * 
	 * This currently works for Application Fees and Broker Fees.
	 * 
	 * A similar function exists in the CFE_eCash_API_2 but had difficulties 
	 * generating fees for accounts that had not been funded yet and required
	 * considerable overhead.
	 *
	 * @param Object $parameters
	 * @param date $action_date (Ymd)
	 * @param date $due_date (Ymd)
	 * @param string $rule_name
	 * @param string $fee_name
	 * @param string $fee_description
	 * @return Object $event
	 */
	private function getCSOFeeEvent($parameters, $action_date, $due_date, $rule_name, $fee_name, $fee_description)
	{
		// If the fee rules don't exist, return NULL
		//var_dump($this->rules);
		if(! isset($this->rules['cso_assess_fee_app']))
		{
			echo "No CSO Rule for Assess Fee App\n";
			return NULL;
		}
		
		$rules = $this->rules[$rule_name];

		$amount_type     = $rules['amount_type'];
		$fixed_amount    = $rules['fixed_amount'];
		$percentage_type = $rules['percent_type'];
		$percentage      = $rules['percent_amount'];
		
		/**
		 * Debug logging.  This can go away soon.
		 */
		$this->Log("Rule Name: $rule_name");
		$this->Log("Amount Type: $amount_type");
		$this->Log("Fixed Amount: $fixed_amount");
		$this->Log("Percentage Type: $percentage_type");
		$this->Log("Percentage: $percentage");
		
		/**
		 * Determine the percentage amount based on APR or Fixed
		 */
		if($percentage_type === 'apr')
		{
			// Get the daily rate assuming the average 365 days in a year
			$daily_rate = $percentage / 365;
			
			// Get the term of the loan (Fund Date till the Effective Due Date)
			$term = Date_Util_1::dateDiff($parameters->fund_date, $this->dates['effective'][0]);
			$percentage_amount = ((($term * $daily_rate) / 100) * $this->principal_balance);
		}
		else
		{
			$percentage_amount = (($this->principal_balance * $percentage) / 100);
		}

		switch($amount_type)
		{
			case 'amt':
				$fee_amount = $fixed_amount;
				break;
			
			case 'pct of prin':
			case 'pct of fund':
				$fee_amount = $percentage_amount;				
				break;
			
			case 'amt or pct of pymnt >':
			case 'amt or pct of prin >':
				$fee_amount = ($fixed_amount > $percentage_amount ? $fixed_amount : $percentage_amount );
				break;

			case 'amt or pct of pymnt <':
			case 'amt or pct of prin <':
				$fee_amount = ($fixed_amount < $percentage_amount ? $fixed_amount : $percentage_amount );
				break;
			default:
				$this->Log("No amount type!\n");
				$fee_amount = 0;
				break;
		}

		if(! empty($fee_amount))
		{
			$fee_amount = number_format($fee_amount, 2, '.', '');
			$amounts = array();
			$amounts[] = Event_Amount::MakeEventAmount('fee', $fee_amount);
			
			return Schedule_Event::MakeEvent($action_date, $due_date, $amounts, $fee_name, $fee_description);
		}
		else
		{
			return NULL;
		}
	}	
	
	public function addFirstDailyInterest($event, $principal_balance)
	{
		$rate_calc = $this->application->getRateCalculator();
		$paid_to = Interest_Calculator::getInterestPaidPrincipalAndDate($this->posted_schedule, FALSE, $this->rules);
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);
		$first_payment_date = $this->last_debit_date;
		$first_sc_date = $this->last_sc_date;
		$this->Log($first_payment_date." ------".$first_sc_date);
		if(strtotime($pdc->Get_Business_Days_Forward($first_sc_date,1)) > strtotime($first_payment_date))
		{
			$first_date = $first_sc_date;
		}
		else
		{
			$first_date = $pdc->Get_Business_Days_Forward($first_sc_date,1);
		}
		$this->last_date = $event->date_effective;
		$amount = $rate_calc->calculateCharge($principal_balance,$first_date,$this->last_date);		
		$this->Log("New interest balance: $amount");
		$this->addInterestAssessment($amount, $first_date, $this->last_date, $event->date_event);	
	}

}

?>
