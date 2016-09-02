<?php

require_once('dfa.php');
require_once(ECASH_COMMON_DIR . "/ecash_api/interest_calculator.class.php");

/**
 * This code somehow manages to deal with Fixed Interest and Daily Interest accounts
 * by using properly set up business rules.  It's not clean, and there's plenty of room for
 * improvement.  This will happen eventually as I know there are plenty of ways to optimize
 * the code.
 */
class CompleteScheduleDFA extends DFA
{
	const NUM_STATES = 43;

	// counter
	private $fund_amount;
	private $fund_date;
	private $last_date;
	private $special_payments;
	private $dates;
	private $rules;
	private $new_events;
	private $posted_schedule;
	private $last_sc_date;
	private $last_debit_date;
	private $principal_balance;
	private $service_charge_balance;
	private $fee_balance;

	private $num_scs_assess;
	private $num_scs_payment;

	function __construct()
	{
		$this->new_events = array();

		for($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->final_states = array(2,3,24,40);
		$this->initial_state = 0;
		$this->tr_functions = array
					   (  0 => 'is_in_holding_status',
					      1 => 'has_registered_events',
					      4 => 'reschedule_special_payments',
					      5 => 'use_manual_renewal_rules',
					      6 => 'has_fees_or_service_charges',
					      7 => 'has_registered_fees',
					      8 => 'adjust_for_grace_period',
					      9 => 'has_fees_balance',
					     10 => 'add_fee_payment',

					     41 => 'has_service_charge_balance',
					     42 => 'add_sc_payment',
					     43 => 'num_scs_exceeds_max',
					     44 => 'add_principal_payment',
					      
					     12 => 'is_fund_payout',
					    // 13 => 'payout',
					    13 => 'add_principal_payment',
					     15 => 'has_principal_balance',
					     16 => 'num_scs_exceeds_max',
					     17 => 'daily_interest_or_flat_fee',
						 18 => 'add_interest_payment',
					     19 => 'shift_dates',
					     20 => 'add_fixed_service_charge_assessment',
					     21 => 'shift_dates',
					     22 => 'add_sc_payment',
					     23 => 'has_principal_balance',
						 25 => 'daily_interest_or_flat_fee',
					     26 => 'reschedule_special_payments',
						 27 => 'add_principal_payment',
					     28 => 'shift_dates',
					     30 => 'add_fixed_service_charge_assessment',
					     31 => 'shift_dates',
					     32 => 'reschedule_special_payments',
					     33 => 'add_sc_payment',
						 34 => 'add_principal_payment',
					     35 => 'num_renew_scs_exceeds_max',
						 36 => 'add_min_principal_payment',
						 37 => 'add_interest_payment',
					     38 => 'shift_dates',
					     39 => 'add_principal_payment',
					     45 => 'add_fee_payment',
					     46 => 'add_interest_payment',
					     47 => 'add_cso_fees',
					     );

		$this->transitions = array
					  ( 0 => array( 0 =>  1, 1 =>  2),
					    1 => array( 0 =>  3, 1 =>  4),
					    4 => array( 1 =>  5),
					    5 => array( 0 =>  6, 1 => 46),
					    6 => array( 0 => 15, 1 => 7),
					    7 => array( 0 =>  8, 1 =>  9),
					    8 => array( 1 =>  9),
					    9 => array( 0 => 41, 1 => 10),
					   10 => array( 1 => 41),
					   12 => array( 0 => 16, 1 => 13),
					   13 => array( 1 => 24),
					   15 => array( 0 => 24, 1 => 12),
					   16 => array( 0 => 17, 1 => 23),
					   17 => array( 0 => 18, 1 => 20),
					   18 => array( 1 => 19),
					   19 => array( 1 => 16),
					   20 => array( 1 => 21),
					   21 => array( 1 => 22),
					   22 => array( 1 => 16),
					   23 => array( 0 => 24, 1 => 25),
					   25 => array( 0 => 26, 1 => 30),
					   26 => array( 1 => 27),
					   27 => array( 1 => 28),
					   28 => array( 1 => 23),
					   30 => array( 1 => 31),
					   31 => array( 1 => 32),
					   32 => array( 1 => 33),
					   33 => array( 1 => 34),
					   34 => array( 1 => 23),
					   35 => array( 0 => 37, 1 => 36),
					   36 => array( 1 => 37),
					   37 => array( 1 => 47),
					   38 => array( 1 => 39),
					   39 => array( 1 => 24),
					   41 => array( 0 => 15, 1 => 42),
					   42 => array( 1 => 43),
					   43 => array( 0 => 15, 1 => 44),
					   44 => array( 1 => 15),
					   45 => array( 1 => 35),
					   46 => array( 1 => 45),
					   47 => array( 1 => 38),
					   );
					   
		parent::__construct();
	}

	// Quick override to do some setup
	public function run($parameters) 
	{
		// Count the number of service charge assessments
		$this->num_scs_assess  = 0;
		$this->num_scs_payment = 0;
		
		$this->num_scs_assess  = $parameters->status->num_reg_sc_assessments;
		$this->num_scs_payment = $parameters->status->posted_service_charge_count;
		$this->Log("Num SC Assessments: {$this->num_scs_assess}, Number SC Made: {$this->num_scs_payment}");

		// For account correction use, get the fund date
		$this->fund_date        = $parameters->fund_date;
		$this->special_payments = $parameters->special_payments;

        if (!isset($parameters->info->fund_actual))
        {
            $this->fund_amount = $parameters->status->initial_principal;
        }
        else
        {
            $this->fund_amount = $parameters->info->fund_actual;
        }

		$this->posted_schedule  = $parameters->schedule;
		$this->last_date        = NULL;
		$this->last_sc_date		= $parameters->last_service_charge_date == 'N/A'? $this->fund_date : $parameters->last_service_charge_date;
		$this->last_debit_date  = $parameters->info->last_debit_date != null ? $parameters->info->last_debit_date : $this->fund_date;
		$this->principal_balance 		= $parameters->balance_info->principal_pending;
		$this->service_charge_balance 	= $parameters->balance_info->service_charge_pending;
		$this->fee_balance 				= $parameters->balance_info->fee_pending;
		
		// Set the rules.  Set the grace period to a default of 10 days if it isn't set already
		$this->rules = $parameters->rules;
		if (!isset($this->rules['grace_period'])) $this->rules['grace_period'] = 10;
		
		$info        = $parameters->info;
		$rules       = $parameters->rules;
		
		$start_date = (! empty($info->last_payment_date)) ? $info->last_payment_date : $this->fund_date;
		$date_list = Get_Date_List($info, $start_date, $rules, 20);
		
		/**
		 * The following routine looks to see if next_action_date is set.  This value is the date of
		 * the next scheduled event in the schedule before Complete_Schedule was run.  The only benefit I
		 * can see in this is if the dates were set differently due to shifting or data fixes.
		 * 
		 * -- Perhaps a safer method would be to look at the next action date if the event is a
		 *    principal or service charge payment that isn't in the date list?  [BR]
		 */
		while (strtotime($date_list['effective'][0]) <= strtotime(date('Y-m-d')) && !empty($date_list['effective'][0])) 
		{
			$this->Log($date_list['event'][0] . " < " . date('Y-m-d') . " ... shifting off");
			array_shift($date_list['event']);
			array_shift($date_list['effective']);
		}

		$this->skip_first_interest_payment = $parameters->skip_first_interest_payment;

		$this->Log("First Event Date: " . $date_list['event'][0]);
		$this->dates = $date_list;

		/**
		 * Hack the Rules!
		 */
		//$this->rules['principal_payment']['principal_payment_type'] = 'Fixed'; // Fixed or Percentage
		//$this->rules['principal_payment']['principal_payment_amount'] = 100;
		//$this->rules['principal_payment']['principal_payment_percentage'] = 100;
		//$this->rules['service_charge']['svc_charge_type'] = 'Fixed'; // Fixed or Daily
		//$this->rules['service_charge']['max_svc_charge_only_pmts'] = 0;

		// This needs to be configured
		//$this->rules['principal_payment']['min_renew_prin_pmt_prcnt'] = 10;

		// For debugging purposes
		if(EXECUTION_MODE === 'RC' || EXECUTION_MODE === 'LOCAL')
		{
			$this->Log("Rule principal_payment_type: {$this->rules['principal_payment']['principal_payment_type']}");
			$this->Log("Rule principal_payment_amount: {$this->rules['principal_payment']['principal_payment_amount']}");
			$this->Log("Rule principal_payment_percentage: {$this->rules['principal_payment']['principal_payment_percentage']}");
			$this->Log("Rule svc_charge_type: {$this->rules['service_charge']['svc_charge_type']}");
			$this->Log("Rule max_svc_charge_only_pmts: {$this->rules['service_charge']['max_svc_charge_only_pmts']}");
		}
		return parent::run($parameters);
	}

	public function is_in_holding_status($parameters) 
	{
		$application_id = $parameters->application_id;

		// Account is in Second Tier Collections
		if (($parameters->level1 == 'external_collections') &&
		    ($parameters->level2 == '*root')) return 1;

		
		if(In_Holding_Status($application_id)) 
		{
			return 1;
		}
		
		// If the account has any quickchecks, then we do
		// not want to create ANY ACH transactions except
		// for arrangements that an Agent will manually create.
		// Mantis: 4365
		if($parameters->status->num_qc > 0)
			return 1;

		return 0;
	}

	/**
	 * Handle any previously made payments/arrangements that exist
	 */
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
					$this->Log("Is shifted!");
					$this->dates['event'][0] = $e->date_event;
					$this->dates['effective'][0] = $e->date_effective;
					$date = strtotime($this->dates['event'][1]);
				}

				$this->Log("Special payment type {$e->type} on {$e->date_event} compare to ".date('Y-m-d',$date));
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
							//	$shift_dates = true;
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
				$this->shift_dates($parameters);
				$this->Log("Special Payments requires us to shift dates");
			}
		}

		return 1;
	}

	/**
	 * Adjusts the dates in the case that they are not past 
	 * the grace period defined by the business rules
	 */
	public function adjust_for_grace_period($parameters) 
	{
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);
		$grace_period = $this->rules['grace_period'];

		$threshold = $pdc->Get_Calendar_Days_Forward($this->fund_date, $grace_period);
		
		while (strtotime($this->dates['effective'][0]) < strtotime($threshold)) 
		{
			$obj1 = array_shift($this->dates['event']);
			$obj2 = array_shift($this->dates['effective']);
			$this->Log("Shifted dates to conform to grace period of {$grace_period}");
			if (($obj1 == null) || ($obj2 == null)) 
				throw new Exception("No more dates to shift.");
		}

		return 1;
	}

	public function add_fixed_service_charge_assessment($parameters) 
	{
		if($this->principal_balance == 0) return 1;
		
		$sc_amount = ($parameters->rules['interest'] * $this->principal_balance);
		$this->Log("Principal: {$this->principal_balance}");
		$this->Log("Interest Rate: {$parameters->rules['interest']}");
		$this->Log("Service Charge Amount: {$sc_amount}");
		
		// If this is the first SC Assessment, use the fund date.
		if(	$this->num_scs_assess == 0
			|| ($parameters->fund_method == 'Fund_Paydown'
				&& $this->num_scs_assess == $parameters->rules['service_charge']['max_svc_charge_only_pmts'])) 
		{
			$sc_date = $this->fund_date;
		} 
		else 
		{
			$sc_date = $this->dates['event'][0];
		}
			
		// Create the SC assessment
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('service_charge', $sc_amount);
		$event = Schedule_Event::MakeEvent($sc_date, $sc_date, $amounts, 
								'assess_service_chg', 'scheduled interest');
		
		$this->Add_Event($event);

		return 1;
	}
		
	public function add_sc_payment($parameters) 
	{
		if($this->service_charge_balance > 0)
		{
			$amounts = array();
			$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$this->service_charge_balance);
			$event = Schedule_Event::MakeEvent($this->dates['event'][0], $this->dates['effective'][0],
							$amounts, 'payment_service_chg','Interest Payment');
			$this->Add_Event($event);
			$this->Log("Added SC Payment {$this->service_charge_balance}, SC Balance: 0");
		}
		else
		{
			$this->Log("WARNING :: Attempted to create a service charge payment while service charge balance of {$this->service_charge_balance} > 0");
		}
	
		return 1;
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
		$schedule = array_merge($parameters->schedule, $this->new_events);
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
					$paid_fee = bcadd($fees['other']['balance'], $fee['balance'], 2);
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
	 * This is a funny method in that you only want to use it when you aren't making
	 * principal payments for a given payment period.
	 */
	public function add_interest_payment($parameters)
	{
		// If last date is NULL, we'll recalculate the interest up until this point.
		if($this->last_date === NULL)
		{
			$fund_date = $parameters->fund_date;
			$paid_to = Interest_Calculator::getInterestPaidPrincipalAndDate($this->posted_schedule,false,$this->rules,false);
			$first_date = $paid_to['date'];
			$this->last_date = $this->dates['effective'][0];
			$action_date = $this->dates['event'][0];
			
			$days = Interest_Calculator::dateDiff($first_date, $this->last_date);
			$first_date_display = date('m/d/Y', strtotime($first_date));
			$last_date_display = date('m/d/Y', strtotime($this->last_date));
			$comment = "Interest accrued from {$first_date_display} to {$last_date_display} ($days days)";
			
			$amount = Interest_Calculator::scheduleCalculateInterest($this->rules, $this->posted_schedule, $this->last_date);
			$this->Log("First SC Accrual, new interest balance: $amount");
			$this->Add_Interest_Assessment($amount, $first_date, $this->last_date, $action_date);
		}
		else
		{
			$first_date = $this->last_date;
			$this->last_date = $this->dates['effective'][0];
			$days = Interest_Calculator::dateDiff($first_date, $this->last_date);
		
			$amount = Interest_Calculator::calculateDailyInterest($this->rules, $this->principal_balance, $first_date, $this->last_date);

			$first_date_display = date('m/d/Y', strtotime($first_date));
			$last_date_display = date('m/d/Y', strtotime($this->last_date));
			$comment = "Interest accrued from {$first_date_display} to {$last_date_display} ($days days)";
			
			// Create the SC assessment
			$amounts = array();
			$amounts[] = Event_Amount::MakeEventAmount('service_charge', $amount);
			$event = Schedule_Event::MakeEvent($this->dates['event'][0],$this->dates['event'][0],
						  					   $amounts, 'assess_service_chg', $comment);
			$this->Add_Event($event);
		
			if($this->service_charge_balance <= 0) return 1;
		}
				
		// Now create the SC payment event
		$amounts = array();
		if ($this->service_charge_balance > 0 && !$this->skip_first_interest_payment) 
		{
			$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$this->service_charge_balance);
			$event = Schedule_Event::MakeEvent($this->dates['event'][0],$this->dates['effective'][0],
										   	$amounts, 'payment_service_chg', "Payment for $comment");
			$this->Add_Event($event);
		}
		else
		{
			$this->skip_first_interest_payment = false;
		}

		return 1;

	}
	
	public function add_principal_payment($parameters) 
	{
		$princ_decrement = $this->get_principal_payment_amount($parameters);
		
		if($princ_decrement == 0) return 1;
		
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('principal', -$princ_decrement);
		$event = Schedule_Event::MakeEvent($this->dates['event'][0], $this->dates['effective'][0],
										     $amounts, 'repayment_principal','Principal Payment');
		$this->Add_Event($event);
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
		 * IMPORTANT NOTE: The context of a manual renewal MUST be set to 'manual' instead
		 * of generated.  Complete schedule will check for the existence for a principal 
		 * payment with the context of manual to determine which renewal rules to use.
		 */
		$this->new_events[] = Schedule_Event::MakeEvent($this->dates['event'][0], $this->dates['effective'][0],
					  $amounts, 'repayment_principal',"Pay {$percentage}% of Principal Balance",'scheduled','generated');
		$this->Log("Adding minimum principal payment of {$payment_amount }");
		$this->principal_balance = bcsub($this->principal_balance, $payment_amount,2);
		
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
	function getCSOFeeEvent($parameters, $action_date, $due_date, $rule_name, $fee_name, $fee_description)
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
			$term = Interest_Calculator::dateDiff($parameters->fund_date, $this->dates['effective'][0]);
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
	
	
	
	public function payout($parameters) 
	{
		$total = 0;
		$amounts = array();
		if($this->principal_balance > 0)
		{
			$amounts[] = Event_Amount::MakeEventAmount('principal', -$this->principal_balance);
			$total += $this->principal_balance;
		}
		
		if($this->service_charge_balance > 0)
		{
			$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$this->service_charge_balance);
			$total += $this->service_charge_balance;
		}

		if($this->fee_balance > 0)
		{
			$amounts[] = Event_Amount::MakeEventAmount('fee', -$this->fee_balance);
			$total += $this->fee_balance;
		}
		
		if($total > 0)
		{
			$event = Schedule_Event::MakeEvent($this->dates['event'][0], $this->dates['effective'][0],
						 			$amounts, 'payout',"Pay full remaining balance of \${$total}");
			$this->Add_Event($event);
			$this->Log("Adding payout of {$this->principal_balance }");
		}
		return 1;
	}

	public function num_scs_exceeds_max($parameters) 
	{
		$max = intval($this->rules['service_charge']['max_svc_charge_only_pmts']);
			
		$scheduled_assessments = 0;
		
		foreach($this->new_events as $e)
		{
			if($e->status == 'scheduled' && $e->type == 'assess_service_chg') $scheduled_assessments++;
		}
		
		$this->Log("Max SC Only Payments: $max.  Current SC Assessments: {$this->num_scs_assess}");
			
		if ($this->num_scs_assess < $max || ($this->rules['principal_payment']['principal_payment_type'] === 'Percentage' &&
		   $this->rules['principal_payment']['principal_payment_percentage'] === '0' && $scheduled_assessments == 0)) return 0;
		
		   if ($this->num_scs_assess >= $max) return 1;
	}

	public function num_renew_scs_exceeds_max($parameters) 
	{
		$max = intval($this->rules['service_charge']['max_renew_svc_charge_only_pmts']);
		$this->Log("Max Renew SC Only Payments: $max.  Current SC Assessments: {$this->num_scs_assess}");
		if ($this->num_scs_assess <= $max) return 0;
		if ($this->num_scs_assess > $max) return 1;
	}

	public function has_principal_balance($parameters) 
	{
		$scheduled_payments = 0;
		$scheduled_assessments = 0;
		
		foreach($this->new_events as $e)
		{
			if($e->status == 'scheduled')
			{
				if($e->type == 'assess_service_chg') $scheduled_assessments++;
				if($e->type == 'payment_service_chg') $scheduled_payments++;
			}
		}
		
		/**
		 * If there is no minimum principal payment, make sure we've at least
		 * scheduled some interest payments and then return 0 to short circuit
		 * the process so we don't loop continuously.  The Regenerate Schedules
		 * cron will pick up and regenerate these accounts once they've run through
		 * their scheduled events so long as they stay 'Active'.
		 */
		if($this->rules['principal_payment']['principal_payment_type'] === 'Percentage' &&
		   $this->rules['principal_payment']['principal_payment_percentage'] === '0' &&
		   ($scheduled_assessments > 0 || $scheduled_payments > 0)) 
		{
			$this->Log("No Minimum Principal Payment required.  Stopping.");
			return 0;
		}
		
		$this->Log("Current principal balance: \${$this->principal_balance}");
		return (($this->principal_balance > 0)? 1 : 0);
	}

	public function has_registered_events($parameters) 
	{
		$this->Log("Number of registered events: {$parameters->status->num_registered_events}");
		if($parameters->status->num_registered_events > 0)	return 1;
		
		return 0;
	}

	public function has_fees_or_service_charges($parameters) 
	{
		return (($this->fee_balance > 0 || $this->service_charge_balance > 0) ? 1 : 0);
	}	

	public function has_fees_balance($parameters) 
	{
		return (($this->fee_balance > 0) ? 1 : 0);
	}

	public function has_service_charge_balance($parameters) 
	{
		return (($this->service_charge_balance > 0) ? 1 : 0);
	}

	public function has_registered_fees($parameters) 
	{
		foreach ($parameters->schedule as $event) 
		{
			if (($event->status != 'scheduled') && ($event->fee_amount < 0)) return 1;
		}
		return 0;
	}

	public function daily_interest_or_flat_fee($parameters)
	{
		// Return 0 for Daily Interest, or 1 for Fixed Interest
		if($this->rules['service_charge']['svc_charge_type'] === 'Daily')
		{
			return 0;
		}
		
		return 1;
	}

	public function get_principal_payment_amount($parameters)
	{
		if($this->rules['principal_payment']['principal_payment_type'] === 'Percentage')
		{
			$p_amount = (($this->fund_amount / 100) * $this->rules['principal_payment']['principal_payment_percentage']);
			$this->Log("Calculating amount of $p_amount using ({$this->fund_amount}/100) * {$this->rules['principal_payment']['principal_payment_percentage']}");
		}
		else
		{
			// If the new rule style exists, use it, else use the old rule style.
			$p_amount = (isset($this->rules['principal_payment']['principal_payment_amount'])) ? $this->rules['principal_payment']['principal_payment_amount'] : $this->rules['principal_payment_amount'];
		}
		return number_format(min($p_amount, $this->principal_balance),2);
	}
	
	public function use_manual_renewal_rules($parameters)
	{
		return ($parameters->has_pending_rollover === TRUE) ? 1 : 0;
	}
	
	public function shift_dates($parameters)
	{
		array_shift($this->dates['event']);
		array_shift($this->dates['effective']);
		$this->Log("Shifting Dates, next action date: {$this->dates['event'][0]}");
		return 1;
	}

	/**
	 * Determines whether or not the account should pay off it's
	 * entire balance on the customer's first due date.
	 */
	public function is_fund_payout($parameters)
	{
		if(isset($this->rules['principal_payment']['principal_payment_percentage'])
		&& $this->rules['principal_payment']['principal_payment_percentage'] == '100')
		{
			return 1;
		}
		
		return 0;
	}
	
	
	/**
	 * Situation: We are in a "Held" status, meaning the account is in a status that should not 
	 * transition until an expiration period or some sort of human intervention takes place.
	 * We should not attempt to adjust the account at this time.
	 */
	public function State_2($parameters)
	{
		return Array();
	}

	/**
	 * Situation: Nothing exists for this person.
	 * For now, error out. We'll decide on this later.
	 */
	public function State_3($parameters) 
	{ 
		throw new Exception("No existing registered events found."); 
	}
	
	/**
	 * Situation: There is no principal balance and no fee balance
	 * Do nothing.
	 */
	public function State_10($parameters)
	{
		return Array();
	}

	/**
	 * Return out the schedule we've created
	 */
	public function State_24($parameters) 
	{
		if(count($this->new_events) > 0)
		{
			return $this->new_events;
		}
		else
		{
			$this->Log("No events to record.  Why was this application run through the DFA?");
		}
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
		if($type === 'assess_service_chg') $this->num_scs_assess++;

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
		
		if($this->rules['service_charge']['svc_charge_type'] === 'Daily')
		{
			$this->Log("Attempting to add Daily interest");
			if($principal !== NULL)
			{
				$this->Log("Principal = $principal");
				if($this->last_date === NULL)
				{
					$paid_to = Interest_Calculator::getInterestPaidPrincipalAndDate($this->posted_schedule,false);
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
					$amount = Interest_Calculator::calculateDailyInterest($this->rules, $principal_balance, $first_date, $this->last_date);
					//$amount = Interest_Calculator::scheduleCalculateInterest($this->rules, $this->posted_schedule, $this->last_date);
					$this->Log("New interest balance: $amount");
					$this->Add_Interest_Assessment($amount, $first_date, $this->last_date, $event->date_event);
				}
				else if(strtotime($event->date_effective) > strtotime($this->last_date))
				{
					$this->Log("{$event->date_effective} > {$this->last_date}");

					$interest = Interest_Calculator::calculateDailyInterest($this->rules, $principal_balance, $this->last_date, 
																		    $event->date_effective);
					$this->Add_Interest_Assessment($interest, $this->last_date, $event->date_effective, $event->date_event);
					$this->last_date = $event->date_effective;
				}
				$this->Log("Comparing {$event->date_event} to {$this->dates['event'][0]}");
				if(strtotime($event->date_event) === strtotime($this->dates['event'][0]))
				{
					$this->Log("Dates Match, Interest Balance: {$this->service_charge_balance}");
					if ($this->service_charge_balance > 0 && !$this->skip_first_interest_payment)
					{
						$sc_amounts = array();
						$sc_amounts[] = Event_Amount::MakeEventAmount('service_charge', -$this->service_charge_balance);
						$sc_event = Schedule_Event::MakeEvent($this->dates['event'][0],$this->dates['effective'][0],
															   $sc_amounts, 'payment_service_chg', "Interest Payment");
						$this->new_events[] = $sc_event;
						$this->service_charge_balance = 0;
					}
					else 
					{
						$this->skip_first_interest_payment =  false;
					}
				}
			}
		}

		if ($total != 0) $this->new_events[] = $event;
	}
	
	public function Add_Interest_Assessment($amount, $first_date, $last_date, $action_date)
	{
		if($amount <> 0)
		{
			$days = Interest_Calculator::dateDiff($first_date, $last_date);
			
			$first_date_display = date('m/d/Y', strtotime($first_date));
			$last_date_display = date('m/d/Y', strtotime($last_date));
			$comment = "Interest accrued from {$first_date} to {$last_date} ($days days)";
			
			$amounts = array();
			$amounts[] = Event_Amount::MakeEventAmount('service_charge', $amount);
			$event = Schedule_Event::MakeEvent($action_date, $action_date, $amounts, 'assess_service_chg', $comment);
			$this->Add_Event($event);
		}
	}
	
}
?>
