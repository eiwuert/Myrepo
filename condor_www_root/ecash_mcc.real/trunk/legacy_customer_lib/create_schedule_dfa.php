<?php

require_once('dfa.php');
require_once(SERVER_CODE_DIR . "schedule_event.class.php");

/**
 * Generates a fresh schedule from scratch.
 *
 * Current assumptions:
 *
 * 1) There is no initial principal balance.
 * 2) The fund_amount member is defined in the parameters.
 * 3) The parameters also contain a list of dates to be used as
 *    pay dates. Need to have "period skip" accounted for.
 * 4) The rules have been run through the "Prepare_Rules" function.
 */

class CreateScheduleDFA extends DFA {

	public $fund_amount;
	public $principal_balance;
	public $scs_assessed;
	public $new_events;
	public $dates;
	public $rules;
	public $last_date;
	
	const NUM_STATES = 23;

	public function __construct() {
		for ($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->initial_state = 0;
		$this->final_states = array(21, 22);
		$this->tr_functions = array(
					    0 => 'has_fund_event',
					   23 => 'using_cso_model',
					   24 => 'add_cso_fees',
					    1 => 'create_fee_payments',
					    2 => 'create_fund_event',
					    3 => 'funding_method',
					    4 => 'add_converted_service_charge_events',
					    5 => 'at_sc_only_threshold',
					    6 => 'daily_interest_or_flat_fee',
					    7 => 'add_interest_payment',
					    8 => 'shift_dates',
					    9 => 'add_fixed_service_charge',
					   10 => 'has_principal_balance',
					   11 => 'daily_interest_or_flat_fee',
					   12 => 'add_interest_payment',
					   13 => 'add_principal_payment',
					   14 => 'shift_dates',
					   15 => 'add_fixed_service_charge',
					   16 => 'add_principal_payment',
					   17 => 'daily_interest_or_flat_fee',
					   18 => 'add_interest_payment',
					   19 => 'add_fixed_service_charge',
					   20 => 'add_principal_payment',
					   );

		$this->transitions = array(
					   0 => array( 0 =>  23, 1 => 21),
					  23 => array( 0 =>  1, 1 => 24),
					  24 => array( 1 =>  1),
					   1 => array( 1 =>  2),
					   2 => array( 1 => 3),
					   3 => array( 'Fund_Paydown' => 4, 'Fund' => 5, 'Fund_Payout' => 17),
					   4 => array( 1 =>  5),
					   5 => array( 0 =>  6, 1 => 10),
					   6 => array( 0 =>  7, 1 =>  9),
					   7 => array( 1 =>  8),
					   8 => array( 1 =>  5),
					   9 => array( 1 =>  5),
					  10 => array( 0 => 22, 1 => 11),
					  11 => array( 0 => 12, 1 => 15),
					  12 => array( 1 => 13),
					  13 => array( 1 => 14),
					  14 => array( 1 => 10),
					  15 => array( 1 => 16),
					  16 => array( 1 => 10),
					  17 => array( 0 => 18, 1 =>  19),
					  18 => array( 1 => 20),
					  19 => array( 1 => 20),
					  20 => array( 1 => 22));

		parent::__construct();
	}

	public function run($parameters) 
	{
		$this->last_date    = $parameters->fund_date;
		$this->rules        = $parameters->rules;
		$this->scs_assessed = 0;
		$this->fund_amount  = $parameters->fund_amount;

		$start_date         = $parameters->fund_date;
		$info               = $parameters->info;
		
		//$this->rules['principal_payment_type'] = 'Fixed'; // Fixed or Percentage
		//$this->rules['principal_payment_amount'] = 100;
		//$this->rules['principal_payment_percentage'] = 50;
		//$this->rules['service_charge']['svc_charge_type'] = 'Fixed'; // Fixed or Daily
		//$this->rules['max_svc_charge_only_pmts'] = 1;
		
		$this->Log("Principal Payment Type: " . $this->rules['principal_payment']['principal_payment_type']);
		$this->Log("Principal Payment Percentage: " . $this->rules['principal_payment']['principal_payment_percentage']);

		/** @todo: Figure out # of dates to fetch again */
		$dates = Get_Date_List($info, $start_date, $this->rules, 20);

		if (!isset($this->rules['grace_period'])) $this->rules['grace_period'] = 10;

		$gp = $parameters->pdc->Get_Calendar_Days_Forward($start_date, $this->rules['grace_period']);

		// If the date_first_payment is greater than the GP, use it.
		if(strtotime($info->date_first_payment) > strtotime($gp)) 
		{
			$min_date = $info->date_first_payment;
		} 
		else 
		{
			$min_date = $gp;
		}
		
		// Note: Get_Date_List should take care of this:
		// Make sure our first payment is at least past the grace period.
		while(strtotime($dates['effective'][0]) < strtotime($min_date)) 
		{
			array_shift($dates['event']);
			array_shift($dates['effective']);
		}
		
		$this->dates = $dates;
		
		return parent::run($parameters);

	}	

	/**
	 * Checks the current schedule (if one exists) for a
	 * fund event that is either scheduled, pending, or complete.
	 */
	public function has_fund_event($parameters) 
	{ 
		if (!isset($parameters->schedule)) return 0;
		
		foreach ($parameters->schedule as $e)
		{
			if (in_array($e->type, array('loan_disbursement', 'moneygram_disbursement', 'check_disbursement'))
				&& in_array($e->status, array('scheduled','pending','complete')))
			{
				 return 1;
			}
		}

		return 0;
	}
	
	/**
	 * Function that checks the business rule loan_type_model
	 * to determine whether the loan is a CSO loan or not.
	 */
	function using_cso_model($parameters)
	{
		return ($this->rules['loan_type_model'] === 'CSO') ? 1 : 0;
	}

	/**
	 * Create the CSO related fees.
	 */
	function add_cso_fees($parameters)
	{
		// Action date and Due date are same day for fee assessments
		$action_date = $parameters->fund_date;
		$due_date    = $parameters->fund_date;
		
		/** Add fees to $parameters->schedule so create_fee_payments() will add appropriate payments **/
		
		//This should be set to 0, and as such should not be registering any fee.
		// Application Fee
		$application_fee = $this->getCSOFeeEvent($parameters, $action_date, $due_date, 'cso_assess_fee_app', 'cso_assess_fee_app', 'CSO Application Fee');
		if($application_fee)
		{
			$this->new_events[] = $application_fee;
			$parameters->schedule[] = $application_fee;
		}
		
		// Broker Fee
		$broker_fee = $this->getCSOFeeEvent($parameters, $action_date, $due_date, 'cso_assess_fee_broker', 'cso_assess_fee_broker', 'CSO Broker Fee');
		if($broker_fee)
		{
			$this->new_events[] = $broker_fee;
			$parameters->schedule[] = $broker_fee;
		}
		
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
	function getCSOFeeEvent($parameters, $action_date, $due_date, $rule_name, $fee_name, $fee_description)
	{

		//This gets the requested fee amount
		$fee_amount = ECash_CSO::getCSOFeeAmount($rule_name,$parameters->application_id,$parameters->fund_date,$this->dates['effective'][0]);
		
		if($fee_amount)
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
	
	
	/**
	 * If any fees assessments exist in the account, this will create payments 
	 * on their first due date.
	 *
	 * @param object $parameters
	 * @return int 1 (always)
	 */
	public function create_fee_payments($parameters)
	{
		if(isset($parameters->schedule) && is_array($parameters->schedule) && (count($parameters) > 0))
		{
			/**
			 * Fee payments are due on the first due date
			 */
			$action_date = $this->dates['event'][0];
			$due_date    = $this->dates['effective'][0];

			$total_fees  = 0.0;
			$new_fee_events = array();
			
			foreach($parameters->schedule as $e)
			{
				switch($e->type)
				{
					case 'assess_fee_delivery':
						$type = 'payment_fee_delivery';
						break;
					case 'assess_fee_transfer':
						$type = 'payment_fee_transfer';
						break;
					case 'assess_fee_lien':
						$type = 'payment_fee_lien';
						break;
					case 'cso_assess_fee_app':
						$type = 'cso_pay_fee_app';
						break;
					case 'cso_assess_fee_broker':
						$type = 'cso_pay_fee_broker';
						break;
					case 'cso_assess_fee_late':
						$type = 'cso_pay_fee_late';
						break;
					default:
						continue;
						break;
				}

				$this->Log("Adding event: $type");
				
				// Using new_amounts to get rid of empty amount types
				$new_amounts = array();			

				if(!empty($e->amounts))
				{
					// Make the values negative
					foreach($e->amounts as $a)
					{
						if($a->event_amount_type === 'fee')
						{
							$total_fees -= $a->amount;
							$new_amounts[] = Event_Amount::MakeEventAmount('fee', -$a->amount);
						}
					}
					
					$new_fee_events[] = Schedule_Event::MakeEvent($action_date, $due_date,
						  								$new_amounts, $type, 'Fee Payment');
				}
			}
			
			// Precautionary in the case that fees may have been adjusted out already.
			if($total_fees <> 0.0)
			{
				foreach($new_fee_events as $fee)
				{
					$this->new_events[] = $fee;
				}
			}
		}

		return 1;
	}
	
	/**
	 * Creates the Fund event.
	 *
	 * @param object $parameters
	 * @return int 1 (always)
	 */
	public function create_fund_event($parameters) 
	{
		$action_date = $parameters->fund_date;
		$due_date    = $parameters->pdc->Get_Next_Business_Day($parameters->fund_date);

		$this->Log("Fund Date: $action_date, Due Date: $due_date");
		
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('principal', $parameters->fund_amount);

		if($parameters->fund_method === 'Fund_Moneygram')
		{
			if (preg_match('/Moneygram #/', $parameters->comment)) 
			{
			} 
			else 
			{
				$parameters->comment = 'Moneygram # ' . $parameters->comment;
			}
			$this->new_events[] = Schedule_Event::MakeEvent($action_date, $action_date,
					  				$amounts, 'moneygram_disbursement', $parameters->comment);
		}
		else if($parameters->fund_method === 'Fund_Check')
		{
			if (preg_match('/Check #/', $parameters->comment)) 
			{
			} 
			else 
			{
				$parameters->comment = 'Check # ' . $parameters->comment;
			}
			$this->new_events[] = Schedule_Event::MakeEvent($action_date, $action_date,
					  				$amounts, 'check_disbursement', $parameters->comment);
		}
		else
		{
			$this->new_events[] = Schedule_Event::MakeEvent($action_date, $due_date,
					  				$amounts, 'loan_disbursement','Fund of Loan');
		}
		
		$this->principal_balance = $parameters->fund_amount;
		
		return 1;
	}

	/**
	 * Determine what method was used to fund the account
	 *
	 * @param array $parameters
	 * @return string
	 */
	public function funding_method($parameters) 
	{
		if(empty($parameters->fund_method))
			return 'Fund';

		// I wrote it this way to add flexibility [BR]
		switch($parameters->fund_method)
		{
			case 'Fund_Moneygram' :
			case 'Fund_Check' :
				return 'Fund';
				break;
			default:
				return $parameters->fund_method;
				break;
		}
	}
	
	public function at_sc_only_threshold($parameters) 
	{
		$this->Log("Interest Threshold: {$this->rules['service_charge']['max_svc_charge_only_pmts']}, Current Charges: {$this->scs_assessed}");
		
		return (($this->scs_assessed >= $this->rules['service_charge']['max_svc_charge_only_pmts'])?1:0);
	}

	public function has_principal_balance($parameters) 
	{
		/**
		 * If there is no minimum principal payment, return 0 so we'll short-circuit
		 * the process and write out the schedule.  The Regenerate Schedule cron will handle these
		 * accounts once they've run through their scheduled events.
		 */
		if($this->rules['principal_payment']['principal_payment_type'] === 'Percentage' &&
		   $this->rules['principal_payment']['principal_payment_percentage'] === '0' &&
		   $this->scs_assessed > 0) 
		{
			$this->Log("No Minimum Principal Payment required.  Stopping.");
			return 0;
		}
		
		return (($this->principal_balance > 0) ? 1 : 0);
	}
	
	// This is used to insert empty service charge only events for applications that are funded with
	// the Paydown method, where the customer starts making principal payments right away.
	public function add_converted_service_charge_events($parameters) {
		$date = $parameters->fund_date;
		$num_sco_events = $parameters->rules['service_charge']['max_svc_charge_only_pmts'];
		for ($x = 0; $x < $num_sco_events; $x++) 
		{
			$this->new_events[] = Schedule_Event::MakeEvent($date, $date, array(), 'converted_sc_event', 'Placeholder for SC Only Event for Paydown Funding');
			$this->Log("Adding converted interest charge.");
			$this->scs_assessed++;
		}
		return 1;
	}
	
	/**
	 * Determine how we calculate the service charge
	 *
	 * @param object $parameters
	 * @return int
	 */
	public function daily_interest_or_flat_fee($parameters)
	{
		// Return 0 for Daily Interest, or 1 for Fixed Interest
		if($this->rules['service_charge']['svc_charge_type'] === 'Daily')
		{
			return 0;
		}
		
		return 1;
	}

	/**
	 * 1) Create a daily interest based service charge assesment
	 * 2) Create the Interest payment.
	 * 3) Shift off dates
	 */
	public function add_interest_payment($parameters)
	{
		require_once(ECASH_COMMON_DIR . "/ecash_api/interest_calculator.class.php");

		if($this->last_date === $parameters->fund_date)
		{
			$first_date = $parameters->pdc->Get_Next_Business_Day($parameters->fund_date);
		}
		else
		{
			$first_date = $this->last_date;
		}

		$this->last_date = $this->dates['effective'][0];

		$days   = Interest_Calculator::dateDiff($first_date, $this->last_date);
		$amount = Interest_Calculator::calculateDailyInterest($this->rules, $this->principal_balance, $first_date, $this->last_date);

		$first_date_display = date('m/d/Y', strtotime($first_date));
		$last_date_display = date('m/d/Y', strtotime($this->last_date));
		$comment = "Interest accrued from {$first_date_display} to {$last_date_display} ($days days)";
		
		// Create the SC assessment
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('service_charge', $amount);
		$this->new_events[] = Schedule_Event::MakeEvent($this->dates['event'][0],
														$this->dates['event'][0],
					  $amounts, 'assess_service_chg', $comment);

		$this->scs_assessed++;

		// Now create the SC payment event
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$amount);
		$this->new_events[] = Schedule_Event::MakeEvent($this->dates['event'][0],
														$this->dates['effective'][0],
					  $amounts, 'payment_service_chg', "Payment for $comment");

		return 1;

	}
	
	/**
	 * 1) Create a fixed Interest assessment.
	 * 2) Shift off dates
	 * 3) Create the Interest payment.
	 */
	public function add_fixed_service_charge($parameters) 
	{
		if($this->principal_balance == 0) return 1;
		
		$sc_amount = ($parameters->rules['interest'] * $this->principal_balance);
		
		// If this is the first SC Assessment, use the fund date.
		if(	$this->scs_assessed == 0
			|| ($parameters->fund_method == 'Fund_Paydown'
				&& $this->scs_assessed == $parameters->rules['service_charge']['max_svc_charge_only_pmts'])) 
		{
			$sc_date = $parameters->fund_date;
		} 
		else 
		{
			$sc_date = $this->dates['event'][0];
		}
			
		// Create the SC assessment
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('service_charge', $sc_amount);
		$this->new_events[] = Schedule_Event::MakeEvent($sc_date,
														$sc_date,
					  $amounts, 'assess_service_chg', 'Fixed Interest Assessment');

		// If there are no service charges assessed, do not shift the dates yet.
		if(($this->scs_assessed != 0 && $parameters->fund_method != 'Fund_Paydown')
			|| ($parameters->fund_method == 'Fund_Paydown'
				&& $this->scs_assessed > $parameters->rules['service_charge']['max_svc_charge_only_pmts'])) 
		{
			// Now shift our dates off - include any period skips we might need.
			$this->shift_dates($parameters);
		}

		// Count it!
		$this->scs_assessed++;
		
		// Now create the SC payment event
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$sc_amount);
		$this->new_events[] = Schedule_Event::MakeEvent($this->dates['event'][0], 
														$this->dates['effective'][0],
					  $amounts, 'payment_service_chg', 'Interest Payment');

		return 1;
	}

	public function add_principal_payment($parameters) 
	{
		$princ_decrement = $this->get_principal_payment_amount();
		
		// If the amount is zero, don't create an event
		if($princ_decrement == 0) return 1;
		
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('principal', -$princ_decrement);
		$this->new_events[] = Schedule_Event::MakeEvent($this->dates['event'][0], $this->dates['effective'][0],
					  $amounts, 'repayment_principal','Principal Payment');
		$this->principal_balance -= $princ_decrement;
		$this->Log("Adding payment of $princ_decrement with {$this->principal_balance } remaining.");
		return 1;
	}

	public function shift_dates($parameters)
	{
		array_shift($this->dates['event']);
		array_shift($this->dates['effective']);

		return 1;
	}
	
	/**
	 * Do nothing. Don't create a newly funded loan if we see that someone was already funded.
	 */
	public function State_21($parameters) 
	{ 
		return Array();
	}

	/**
	 * Take the new events list, and record all the events into the database.
	 */
	public function State_22($parameters) 
	{
		return $this->new_events;
	}
	
	/**
	 * Checks the existing schedule for a particular event type
	 *
	 * @param object $parameters
	 * @param string $comparison_type
	 * @return int 0 or 1
	 */
	public function has_type($parameters, $comparison_type) 
	{
		if (!isset($parameters->schedule)) return 0;
		
		foreach ($parameters->schedule as $e) 
		{
			if ($e->type == $comparison_type) return 1;
		}
		return 0;
	}
	
	public function get_principal_payment_amount()
	{
		if($this->rules['principal_payment']['principal_payment_type'] === 'Percentage')
		{
			$p_amount = (($this->fund_amount / 100) * $this->rules['principal_payment']['principal_payment_percentage']);
			$this->Log("Calculating amount of $p_amount using ({$this->fund_amount}/100) * {$this->rules['principal_payment_percentage']}");
		}
		else
		{
			$p_amount = $this->rules['principal_payment']['principal_payment_amount'];
		}
		
		return min($p_amount, $this->principal_balance);
	}
	
}

?>
