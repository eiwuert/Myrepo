<?php

require_once('dfa.php');
require_once(SERVER_CODE_DIR . "schedule_event.class.php");

/**
 * This DFA Renews a loan, creating a service charge payment for the 
 * current next due date and a minimum principal payment if applicable, 
 * and then rescheduling a payout for the pay period after that.  Using 
 * the 'max_renew_svc_charge_only_pmts' rule, it is possible to charge 
 * only service charges for a number of periods before finally charging
 * the minimum principal amount.
 * 
 * For Agean, this currently only applies to Delaware Title Loans.
 * Delaware Payday Loans automatically renew like CLK loans.
 * California Payday Loans do not renew ever.
 */
class RenewScheduleDFA extends DFA {

	public $principal_balance;
	public $fee_balance;
	public $service_charge_balance;
	public $num_scs_assessed;
	public $scs_made;
	public $new_events;
	public $special_payments;
	public $dates;
	public $fund_amount;
	public $renewal_class;
	private $last_date_effective;
	
	const NUM_STATES = 9;

	public function __construct() 
	{
		for ($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->initial_state = 0;
		$this->final_states = array(6);
		$this->tr_functions = array(
						8 => 'add_fee_payment',
						7 => 'num_sc_only_rollovers_max',
						1 => 'add_min_principal_payment',
					    2 => 'add_cso_fees',
					    3 => 'reschedule_special_payments',
					    4 => 'add_interest_payment',
					    5 => 'payout',
					    0 => 'add_interest_payment', 
					    9 => 'paydown_requested',
					   );

		$this->transitions = array(
					  0 => array( 1 => 3),
					  3 => array( 1 => 8),
					  8 => array( 1 => 9),
					  9 => array( 0 => 7, 1 => 2),
					   7 => array( 0 => 2, 1 => 1),
					   1 => array( 1 => 2),
					   2 => array( 1 => 4),
					  // 3 => array( 1 => 4),
					   4 => array( 1 => 5),
					   5 => array( 1 => 6)); 

		parent::__construct();
	}

	public function run($parameters) 
	{
		$this->num_scs_assessed = $parameters->num_scs_assessed;

		$this->fund_amount  = $parameters->fund_amount;
		$this->scs_made = $parameters->status->posted_service_charge_count;
		$this->fee_balance = $parameters->status->running_fees;
		$this->principal_balance = $parameters->status->posted_and_pending_principal;
		$this->service_charge_balance = $parameters->status->posted_and_pending_interest;
		$this->special_payments = $parameters->special_payments;
		$this->last_date_effective = NULL;
		$info        = $parameters->info;
		$rules       = $parameters->rules;
		$this->renewal_class = ECash::getFactory()->getRenewalClassByApplicationID($parameters->application_id);
		// If the last payment date is set, use it as the start date
		$start_date = (! empty($info->last_payment_date)) ? $info->last_payment_date : $parameters->fund_date;
	//	$this->last_date_effective = (! empty($info->last_payment_date)) ? $info->last_payment_date : NULL;
		
		$dates = Get_Date_List($info, $start_date, $rules, 20);
		$this->dates = $dates;
		
		/**
		 * Hack the Rules!
		 */
		//$this->rules['principal_payment']['principal_payment_type'] = 'Fixed'; // Fixed or Percentage
		//$this->rules['principal_payment']['principal_payment_amount'] = 100;
		//$this->rules['principal_payment']['principal_payment_percentage'] = 100;
		//$this->rules['service_charge']['svc_charge_type'] = 'Fixed'; // Fixed or Daily
		//$this->rules['service_charge']['max_svc_charge_only_pmts'] = 0;

		// This needs to be configured
		$this->rules = $rules;
		
		
		return parent::run($parameters);

	}

	function paydown_requested($parameters)
	{
		$paydown = $parameters->paydown_amount;
		if(!($paydown > 0))
		{
			return 0;	
		}
		else 
		{
			//create requested principal payment amount
			
			$amounts = array();
			$amounts[] = Event_Amount::MakeEventAmount('principal', -$paydown);
			
			$principal_payment = Schedule_Event::MakeEvent($this->dates['event'][0], $this->dates['effective'][0],
				$amounts, 'repayment_principal',"Paying off {$paydown} from the principal balance Principal Balance",'scheduled','generated');
			$this->Add_Event($principal_payment);
			$this->Log("Adding requested principal payment of {$paydown}");
			return 1;
		}
		
	}
	
	/**
	 * This method is used to generate payments for outstanding fee balances
	 * that may exist.
	 *
	 * @param Object $parameters
	 * @return Integer 1
	 */
	public function add_fee_payment($parameters)
{
		$fees = array();
		
		$fees['other'] = array('balance' => 0, 'pay_type' => 'none!  This should be allocated to other fees!');
		$fees['ach_fail'] = array('balance' => 0, 'pay_type' => 'payment_fee_ach_fail');
		$fees['delivery'] = array('balance' => 0, 'pay_type' => 'payment_fee_delivery');
		$fees['transfer'] = array('balance' => 0, 'pay_type' => 'payment_fee_transfer');
		$fees['lien']     = array('balance' => 0, 'pay_type' => 'payment_fee_lien');
		$fees['imga_fees']= array('balance' => 0, 'pay_type' => 'payment_imga_fee');

		$fees['cso_application'] = array('balance' => 0, 'pay_type' => 'cso_pay_fee_app');
		$fees['cso_broker']      = array('balance' => 0, 'pay_type' => 'cso_pay_fee_broker');
		$fees['cso_late']        = array('balance' => 0, 'pay_type' => 'cso_pay_fee_late');
		$fees['lend_ach']         = array('balance' => 0, 'pay_type' => 'lend_pay_fee_ach');

		// This look will adjust the fee balances for each fee type.
		
		//We're merging the schedule with the new events in case we already have 
		//some special payments that account for fees which have been rescheduled
		//$schedule = array_merge($parameters->schedule, $this->new_events);
		$schedule = $parameters->schedule;
		foreach($schedule as $e)
		{
			
			if($e->status != 'failed')
			{
				//Determine the fee amount, since, potentially, we're compensating for events that haven't been inserted yet.[#20760]
				foreach ($e->amounts as $a)
				{
					if($a->event_amount_type === 'fee')
					{
						$fee_amount = $a->amount;
					}
				}
				switch($e->type)
				{
					//case 'adjustment_internal_fees':
					case 'payment_imga_fee':
						$fees['imga_fees']['balance'] += $fee_amount;
					
					break;
					case 'assess_fee_ach_fail':
					case 'payment_fee_ach_fail':
					case 'writeoff_fee_ach_fail':
						$fees['ach_fail']['balance'] += $fee_amount;
						break;
	
					case 'assess_fee_delivery':
					case 'payment_fee_delivery':
					case 'writeoff_fee_delivery':
						$fees['delivery']['balance'] += $fee_amount;
						break;
	
					case 'assess_fee_transfer':
					case 'payment_fee_transfer':
					case 'writeoff_fee_transfer':
						$fees['transfer']['balance'] += $fee_amount;
						break;
	
					case 'assess_fee_lien':
					case 'payment_fee_lien':
					case 'writeoff_fee_delivery':
						$fees['lien']['balance'] += $fee_amount;
						break;
						
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
						$fees['other']['balance'] += $fee_amount;
						//continue;
						break;
				}
			}
		}

		// If there are any fee balances, we'll go ahead and create the 
		// corresponding payments for them.
		
		//The goal is to allocate fee payments to fees that don't belong to a fee to fees so that we have 
		//an appropriate balance.  Possible scenarios are $60 broker fee assessment + $30 App fee assessment - $70 manual payment
		//The manual payment covers $30 of the app fee and $40 of the broker, without actually being the fee payment type.
		foreach ($fees as $fee_type => $fee)
		{
			if($fee['balance'] > 0)
			{
				if ($fees['other']['balance'] < 0) 
				{
					$paid_fee = bcadd($fees['other']['balance'], $fee['balance'],2);
					//paid fee is negative, we still have fee payments left to allocate
					if($paid_fee < 0)
					{
						$fees[$fee_type]['balance'] = 0;
						$fees['other']['balance'] = $paid_fee;
					}
					//paid fee is positive, we've allocated all of our payment, there are still fees owed.
					else 
					{
						$fees[$fee_type]['balance'] = $paid_fee;
						$fees['other']['balance'] = 0;
					}
				}
			}
		}

		foreach ($fees as $fee)
		{
			if($fee['balance'] > 0)
			{
				$this->Log("Adding event: {$fee['pay_type']} with amount {$fee['balance']}");
				$amounts = array();
				$amounts[] = Event_Amount::MakeEventAmount('fee', -$fee['balance']);

				$event = Schedule_Event::MakeEvent($this->dates['event'][0], $this->dates['effective'][0],
													$amounts, $fee['pay_type'], 'Fee Payment');
				
				$this->Add_Event($event);
			}
		}
		
		return 1;
	}
	
	
	
	/**
	 * Create the CSO related fees.
	 */
	function add_cso_fees($parameters)
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
 		$this->Add_Event($broker_fee);
		$this->Add_Event($broker_payment);
		
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
	function getCSOFeeEvent($parameters, $action_date, $due_date, $rule_name, $fee_name, $fee_description)
	{
		
		/**
		 * Debug logging.  This can go away soon.
		 */
		$this->Log("Rule Name: $rule_name");
		$this->Log("Amount Type: $amount_type");
		$this->Log("Fixed Amount: $fixed_amount");
		$this->Log("Percentage Type: $percentage_type");
		$this->Log("Percentage: $percentage");

		$fee_amount = $this->renewal_class->getCSOFeeAmount($rule_name,$parameters->application_id,$action_date,$due_date,null,$this->principal_balance);
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
	
	public function reschedule_special_payments($parameters)
	{
		$application_id = $parameters->application_id;
		
		/**
		 * Handle Reattempts First
		 */
		if(count($parameters->reattempts) > 0)
		{		
			$this->Log("Before Reattempts: Principal Balance: {$this->principal_balance}, SC Balance: {$this->service_charge_balance}");
	
			foreach($parameters->reattempts as $e)
			{
				if(($e->origin_id != $e->event_schedule_id) && ($e->origin_group_id < 0)) 
				{
					$this->Log("Rescheduling Re-Attempt, Event ID: {$e->event_schedule_id}");
	
					$e->date_event     = $this->dates['event'][0];
					$e->date_effective = $this->dates['effective'][0];
					
					// Add the event to the schedule
					$this->Add_Event($e);
				}
			}
			$parameters->reattempts = array();
			$this->Log("After Reattempts: Principal Balance: {$this->principal_balance}, SC Balance: {$this->service_charge_balance}");
		}		

		/**
		 * Now run through any "Special" payments.  These are items like manual payments, arrangements,
		 * paydowns, etc.
		 */
		if(count($parameters->special_payments) > 0)
		{
			$events = $this->special_payments;

			// Flag to be used to shift the dates if for some reason we insert a payment
			// on the same date as the first event.
			$shift_dates = false;
			
			// Iterate through the events.  If the event exists before or up to
			// the first date in our current date list, then add the event to the
			// schedule and remove it from the special payments list
			foreach($this->special_payments as $e)
			{
				$date = strtotime($this->dates['event'][0]);
				if ($e->is_shifted)
				{
					$this->dates['event'][0] = $e->date_event;
					$this->dates['effective'][0] = $e->date_effective;
					$date = strtotime($this->dates['event'][1]);
				}

				$this->Log("Special payment type {$e->type} on {$e->date_event} compare to $date");
				if(strtotime($e->date_event) <= $date);
				{
					// These events should only appear here if they have been "shifted"
					// by an agent.
					switch ($e->type)
					{
						case 'payment_service_chg':
							if(empty($e->origin_id) && strtotime($e->date_event) == strtotime($this->dates['event'][0]))
							{
								$shift_dates = TRUE;
							}
							break;
						case 'assess_service_chg':
							if (!$e->origin_id)
							{
								$amount = $this->principal_balance * $this->rules['interest'];
								$e->amounts = array(Event_Amount::MakeEventAmount('service_charge', $amount));
								$e->fee_amount = $amount;
								if(strtotime($e->date_event) == strtotime($this->dates['event'][0]))
								{
									$shift_dates = TRUE;
								}
							}
							break;
						case 'repayment_principal':
							if(strtotime($e->date_event) == strtotime($this->dates['event'][0]))
							{
								$shift_dates = TRUE;
							}
							break;
						case 'paydown':
							if($this->rules['service_charge']['svc_charge_type'] === 'Daily')
							{
								$shift_dates = true;
							}
							break;	
						case 'credit_card':
						case 'moneygram':
						case 'money_order':
						case 'payment_arranged':
							$shift_dates = true;
							break;
					}

					// Add the event to the schedule
					$this->Add_Event($e);
					// Remove the current event from the special payments array
					array_shift($this->special_payments);
				}
			}
			
			// Shift dates forward
			if($shift_dates === true && $this->rules['service_charge']['svc_charge_type'] === 'Daily') 
			{
				array_shift($this->dates['event']);
				array_shift($this->dates['effective']);
				$this->Log("Shifting Dates, next action date: {$this->dates['event'][0]}");
				$this->Log("Special Payments requires us to shift dates");
			}
		}

		return 1;
	}

	public function add_interest_payment($parameters)
	{
		require_once(ECASH_COMMON_DIR . "/ecash_api/interest_calculator.class.php");

		$rules = $parameters->rules;

		if($this->last_date_effective === NULL)
		{
			$first_date = $parameters->info->last_assessment_date ? $parameters->info->last_assessment_date : $parameters->fund_date;
			$this->Log("There's no last_date_effective, we're using the fund date {$first_date}");
			$first_date = $parameters->pdc->Get_Next_Business_Day($first_date);
			$this->last_date_effective = $this->dates['effective'][0];
		}
		else
		{
			
			$first_date = $this->last_date_effective;
			
			// Now shift our dates off - include any period skips we might need.
			array_shift($this->dates['event']);
			array_shift($this->dates['effective']);
			$this->last_date_effective = $this->dates['effective'][0];
		}

		$last_date  = $this->dates['effective'][0];

		$amount = Interest_Calculator::calculateDailyInterest($rules, $this->principal_balance, $first_date, $last_date);

		$first_date_display = date('m/d/Y', strtotime($first_date));
		$last_date_display = date('m/d/Y', strtotime($last_date));
		$comment = "$amount Interest accrued from {$first_date_display} to {$last_date_display}";
		$this->Log($comment);
		
		// Create the SC assessment
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('service_charge', $amount);
		$sc_assessment = Schedule_Event::MakeEvent($this->dates['event'][0],
														$this->dates['event'][0],
					  $amounts, 'assess_service_chg', $comment);
		$this->Add_Event($sc_assessment);
		// Now create the SC payment event
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$this->service_charge_balance);
		$sc_payment = Schedule_Event::MakeEvent($this->dates['event'][0],
														$this->dates['effective'][0],
					  $amounts, 'payment_service_chg', "Payment for $comment");
		$this->Add_Event($sc_payment);
		return 1;

	}

	// Payout the entire principal balance
	public function payout($parameters) 
	{
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('principal', -$this->principal_balance);
		$payout = Schedule_Event::MakeEvent($this->dates['event'][0], $this->dates['effective'][0],
					  $amounts, 'repayment_principal','Pay Full Principal Balance');
		$this->Add_Event($payout);
		$this->Log("Adding payout of {$this->principal_balance }");
		return 1;
	}

	public function add_min_principal_payment($parameters) 
	{
		$percentage = $this->rules['principal_payment']['min_renew_prin_pmt_prcnt'];
		$payment_amount = ($this->principal_balance * ($percentage / 100));
		$this->Log("Calculating amount of $payment_amount using ({$this->principal_balance}) * ({$percentage}/100)");

		
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('principal', -$payment_amount);
		/**
		 * 
		 */
		$paydown = Schedule_Event::MakeEvent($this->dates['event'][0], $this->dates['effective'][0],
					  $amounts, 'repayment_principal',"Pay {$percentage}% of Principal Balance",'scheduled','generated');
		$this->Add_Event($paydown);
		$this->Log("Adding minimum principal payment of {$payment_amount }");
		
		
		return 1;
	}
	
	/**
	 * Take the new events list, and send it back to the caller.
	 */
	public function State_6($parameters) 
	{
		return $this->new_events;
	}

	public function has_fund_event($parameters) { return $this->has_type($parameters, 'loan_disbursement'); }
	
	public function has_type($parameters, $comparison_type) 
	{
		if (!isset($parameters->schedule)) return 0;
		
		foreach ($parameters->schedule as $e) 
		{
			if ($e->type == $comparison_type) return 1;
		}
		return 0;
	}

	
	function num_sc_only_rollovers_max($parameters) 
	{
		/**
		 * Note: We use a different 'max service charge only payments' rule for
		 * manual renewals.  This is because the default may be to pay out immediately
		 * without any service charge only payments.  If the loan is manually renewed, 
		 * a different set of rules applies.  That's where this new rule comes in.
		 */ 
		
		//Rollovers is the rollover term -1, as the initial loan (with no rollover) is counted as 1
		$rollovers = $this->renewal_class->getRolloverTerm($parameters->application_id) - 1;
		$max = intval($this->rules['service_charge']['max_renew_svc_charge_only_pmts']);
		$this->Log("Max SC Payments: $max.  Current Rollovers: {$rollovers}");
		
		if ($rollovers <= $max) return 0;
		if ($rollovers > $max) return 1;
		return 0; // In case something is jacked rule-wise
	}

	public function Add_Event($event)
	{
		// Last Payment / Disbursement?  $this->last_date
		// Calculate Interest up to last completed item? Check.
		$type = $event->type;
		
		$principal = NULL;
		$principal_balance = $this->principal_balance;
		$total = 0;
		
		$this->Log("Adding event of type '{$type}' for '{$event->date_event}'");
		
		if($type === 'payment_service_chg') $this->num_scs_payment++;
		if($type === 'assess_service_chg') $this->num_scs_assessed++;

		foreach($event->amounts as $ea)
		{
			if(!empty($ea->amount) || $this->skip_first_interest_payment)
			{
				$total += $ea->amount;

				switch ($ea->event_amount_type)
				{
					case 'principal' :
						$this->principal_balance = bcadd($this->principal_balance, $ea->amount);
						$principal = $ea->amount;
						$this->Log("Adjusted principal balance : {$ea->amount}, Principal Balance: {$this->principal_balance}");
						break;
					case 'service_charge' :
						$this->service_charge_balance = bcadd($ea->amount, $this->service_charge_balance);
						$this->Log("Adjusted interest balance : {$ea->amount}, Interest Balance: {$this->service_charge_balance}");
						break;
					case 'fee' :
						$this->fee_balance = bcadd($ea->amount, $this->fee_balance);
						$this->Log("Adjusted fees balance : {$ea->amount}, Fee Balance: {$this->fee_balance}");
						break;
				}
			}
		}
		
	//	if($total === 0) return;
		
//		if($this->rules['service_charge']['svc_charge_type'] === 'Daily')
//		{
//			$this->Log("Attempting to add Daily interest");
//			if($principal !== NULL)
//			{
//				$this->Log("Princial = $principal");
//				if($this->last_date === NULL)
//				{
//					$paid_to = Interest_Calculator::getInterestPaidPrincipalAndDate($this->posted_schedule,false);
//					$first_date = $paid_to['date'];
//					$this->last_date = $event->date_effective;
//					$amount = Interest_Calculator::scheduleCalculateInterest($this->rules, $this->posted_schedule, $this->last_date);
//					$this->Log("New interest balance: $amount");
//					$this->Add_Interest_Assessment($amount, $first_date, $this->last_date, $event->date_event);
//				}
//				else if(strtotime($event->date_effective) > strtotime($this->last_date))
//				{
//					$this->Log("{$event->date_effective} > {$this->last_date}");
//
//					$interest = Interest_Calculator::calculateDailyInterest($this->rules, $principal_balance, $this->last_date, 
//																		    $event->date_effective);
//					$this->Add_Interest_Assessment($interest, $this->last_date, $event->date_effective, $event->date_event);
//					$this->last_date = $event->date_effective;
//				}
//				$this->Log("Comparing {$event->date_event} to {$this->dates['event'][0]}");
//				if(strtotime($event->date_event) === strtotime($this->dates['event'][0]))
//				{
//					$this->Log("Dates Match, Interest Balance: {$this->service_charge_balance}");
//					if ($this->service_charge_balance > 0 && !$this->skip_first_interest_payment)
//					{
//						$sc_amounts = array();
//						$sc_amounts[] = Event_Amount::MakeEventAmount('service_charge', -$this->service_charge_balance);
//						$sc_event = Schedule_Event::MakeEvent($this->dates['event'][0],$this->dates['effective'][0],
//															   $sc_amounts, 'payment_service_chg', "Interest Payment");
//						$this->new_events[] = $sc_event;
//						$this->service_charge_balance = 0;
//					}
//					else 
//					{
//						$this->skip_first_interest_payment =  false;
//					}
//				}
//			}
//		}

		if ($total != 0) $this->new_events[] = $event;
	}
	
	
}

?>
