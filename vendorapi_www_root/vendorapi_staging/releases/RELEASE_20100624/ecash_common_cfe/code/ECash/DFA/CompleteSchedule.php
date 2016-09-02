<?php

require_once(ECASH_COMMON_DIR . "/ecash_api/interest_calculator.class.php");

/**
 * Consolidated from AALM, Agean, Impact, & OPM and moved to ECash_DFA
 * namespace.  DFA methods will be
 * lowercase_seperated_by_underscore($parameters) except the
 * State_Number($parameters) methods. The non-DFA methods have been
 * changed to camelCase()
 *
 * OLD NOTES
 *
 * Note: This is a hellacious hack.  If you're not me, do not touch it.  Seriously. [BrianR]
 * 
 * This code somehow manages to deal with Fixed Interest and Daily Interest accounts
 * by using properly set up business rules.  It's not clean, and there's plenty of room for
 * improvement.  This will happen eventually as I know there are plenty of ways to optimize
 * the code.
 * 
 * History:
 * 
 * 12/3/2008 - GForge #21536 - Added the "include_reattempts" argument to Interest_Calc::scheduleCalculateInterest()
 *                             and setting it to FALSE so we will ignore them when determining where to calculate 
 *                             interest from.  You can find this code change in add_interest_payment(). [BR]
 */
class ECash_DFA_CompleteSchedule extends ECash_DFA
{
	// counter
	protected $fund_amount;
	protected $fund_date;
	protected $last_date;
	protected $special_payments;
	protected $reattempts;
	protected $dates;
	protected $rules;
	protected $new_events;
	protected $posted_schedule;

	protected $principal_balance;
	protected $service_charge_balance;
	protected $fee_balance;

	protected $num_scs_assess;
	protected $num_scs_payment;

	protected $num_states = 51;

	protected $is_card_payment = FALSE;

	protected $princ_payment;
	protected $sc_payment;
	protected $payout_payment;

	public function __construct()
	{
		$this->new_events = array();

		for($i = 0; $i < $this->num_states; $i++) $this->states[$i] = $i;
		$this->final_states = array(2,3,24);
		$this->initial_state = 0;

		$this->tr_functions = array(
			0 => 'is_in_holding_status',
			1 => 'has_registered_events',
			4 => 'reschedule_special_payments',
			5 => 'use_manual_renewal_rules', //manual renewals only used by MMP/DMP
			6 => 'has_fees_or_service_charges',
			7 => 'has_registered_fees',
			8 => 'adjust_for_grace_period',
			9 => 'has_fees_balance',
			10 => 'add_fee_payment',
			11 => 'has_fees_balance',
			12 => 'is_fund_payout',
			13 => 'payout',
			14 => 'add_fee_payment',
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
			41 => 'has_service_charge_balance',
			42 => 'add_sc_payment',
			43 => 'num_scs_exceeds_max',
			44 => 'add_principal_payment',
			45 => 'num_scs_exceeds_max',
			46 => 'add_sc_payment',
			47 => 'has_principal_balance',
			48 => 'add_principal_payment',
			49 => 'first_new_event_is_scs',
			50 => 'has_assessment',
			51 => 'reschedule_special_payments',
			52 => 'reschedule_special_payments',
		);

		//"STANDARD" transition list
		$this->transitions = array (
			0 => array( 0 =>  1, 1 =>  2),
			1 => array( 0 =>  3, 1 =>  4),
			4 => array( 1 =>  6),
			5 => array( 0 =>  6, 1 => 35),
			6 => array( 0 => 15, 1 => 7),
			7 => array( 0 =>  8, 1 =>  9),
			8 => array( 1 =>  9),
			9 => array( 0 => 41, 1 => 10),
			10 => array( 1 => 41),
			11 => array( 0 => 45, 1 => 14),
			12 => array( 0 => 16, 1 => 13),
			13 => array( 1 => 24),
			14 => array( 1 => 45),
			15 => array( 0 => 24, 1 => 12),
			16 => array( 'less_than_max' => 17, 'at_max' => 23, 'above_max' => 23),
			17 => array( 0 => 51, 1 => 50),
			18 => array( 1 => 19),
			19 => array( 1 => 16),
			20 => array( 1 => 21),
			21 => array( 1 => 52),
			22 => array( 1 => 17),
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
			37 => array( 1 => 38),
			38 => array( 1 => 13),
			41 => array( 0 => 15, 1 => 42),
			42 => array( 1 => 43),
			43 => array('less_than_max' => 15, 'at_max' => 49, 'above_max' => 44),
			44 => array( 1 => 15),
			45 => array( 'less_than_max' => 22, 'at_max' => 46, 'above_max' => 46), // At maybe should go to 22
			46 => array( 1 => 47),
			47 => array( 0 => 24, 1 => 48 ),
			48 => array( 1 => 25 ),
			49 => array( 0 => 44, 1 => 15),
			50 => array( 0 => 20, 1 => 21),
			51 => array( 1 => 18),
			52 => array( 1 => 11),
		);
		
		parent::__construct();
	}

	// Quick override to do some setup
	public function run($parameters) 
	{
		$this->special_payments = $parameters->special_payments;
		$this->reattempts = $parameters->reattempts;

		$app = 	ECash::getApplicationByID($parameters->application_id);
		$flags = $app->getFlags();
		if($flags->get('card_schedule') && $parameters->may_use_card_schedule)
		{
			//switch special_payments
			foreach($this->special_payments as $key => $e)
			{
				switch ($e->type)
				{
					case 'paydown':
						$this->special_payments[$key]->type = 'card_paydown';
						$this->special_payments[$key]->event_name_short = 'card_paydown';
						break;

					case 'payout':
						$this->special_payments[$key]->type = 'card_payout';
						$this->special_payments[$key]->event_name_short = 'card_payout';
						//array_shift($this->special_payments);
						//$this->special_payments[] = $e;
						break;

					default:
						break;
				}
			}
			
			//switch reattempts
			foreach($this->reattempts as $key => $e)
			{
				switch ($e->type)
				{
					case 'repayment_principal':
						$this->reattempts[$key]->type = 'card_repayment_principal';
						$this->reattempts[$key]->event_name_short = 'card_repayment_principal';
						break;
					
					case 'payment_service_chg':
						$this->reattempts[$key]->type = 'card_payment_service_chg';
						$this->reattempts[$key]->event_name_short = 'card_payment_service_chg';
						break;
					
					case 'payment_fee_ach_fail':
						$this->reattempts[$key]->type = 'payment_fee_card_fail';
						$this->reattempts[$key]->event_name_short = 'payment_fee_card_fail';
						break;
					
					case 'full_balance':
						$this->reattempts[$key]->type = 'card_full_balance';
						$this->reattempts[$key]->event_name_short = 'card_full_balance';
						break;

					case 'cancel':
						$this->reattempts[$key]->type = 'card_cancel';
						$this->reattempts[$key]->event_name_short = 'card_cancel';
						break;

					case 'paydown':
						$this->reattempts[$key]->type = 'card_paydown';
						$this->reattempts[$key]->event_name_short = 'card_paydown';
						break;
	
					case 'payout':
						$this->reattempts[$key]->type = 'card_payout';
						$this->reattempts[$key]->event_name_short = 'card_payout';
						break;
					
					case 'payment_arranged':
						$this->reattempts[$key]->type = 'card_payment_arranged';
						$this->reattempts[$key]->event_name_short = 'card_payment_arranged';
						break;
					default:
					break;
				}
			}
			
			$this->is_card_payment = TRUE;
			$this->princ_payment = 'card_repayment_principal';
			$this->sc_payment = 'card_payment_service_chg';
			$this->payout_payment = 'card_payout';
		}
		else
		{
			$this->using_payement_card = false;
			
			//switch special_payments
			foreach($this->special_payments as $key => $e)
			{
				switch ($e->type)
				{
					case 'card_paydown':
						$this->special_payments[$key]->type = 'paydown';
						$this->special_payments[$key]->event_name_short = 'paydown';
						break;
	
					case 'card_payout':
						$this->special_payments[$key]->type = 'payout';
						$this->special_payments[$key]->event_name_short = 'payout';
						break;

					default:
						break;
				}
			}
			
			//switch reattempts
			foreach($this->reattempts as $key => $e)
			{
				switch ($e->type)
				{
					case 'card_repayment_principal':
						$this->reattempts[$key]->type = 'repayment_principal';
						$this->reattempts[$key]->event_name_short = 'repayment_principal';
						break;

					case 'card_payment_service_chg':
						$this->reattempts[$key]->type = 'payment_service_chg';
						$this->reattempts[$key]->event_name_short = 'payment_service_chg';
						break;
					
					case 'payment_fee_card_fail':
						$this->reattempts[$key]->type = 'payment_fee_ach_fail';
						$this->reattempts[$key]->event_name_short = 'payment_fee_ach_fail';
						break;
					
					case 'card_full_balance':
						$this->reattempts[$key]->type = 'full_balance';
						$this->reattempts[$key]->event_name_short = 'full_balance';
						break;

					case 'card_cancel':
						$this->reattempts[$key]->type = 'cancel';
						$this->reattempts[$key]->event_name_short = 'cancel';
						break;
					
					case 'card_paydown':
						$this->reattempts[$key]->type = 'paydown';
						$this->reattempts[$key]->event_name_short = 'paydown';
						break;
	
					case 'card_payout':
						$this->reattempts[$key]->type = 'payout';
						$this->reattempts[$key]->event_name_short = 'payout';
						break;
					
					case 'card_payment_arranged':
						$this->reattempts[$key]->type = 'payment_arranged';
						$this->reattempts[$key]->event_name_short = 'payment_arranged';
						break;
					default:
					break;
				}
			}

			$this->princ_payment = 'repayment_principal';
			$this->sc_payment = 'payment_service_chg';
			$this->payout_payment = 'payout';
		}
		
		// Count the number of service charge assessments
		$this->num_scs_assess  = 0;
		$this->num_scs_payment = 0;
		
		$this->num_scs_assess  = $parameters->status->num_reg_sc_assessments;
		$this->num_scs_payment = $parameters->status->attempted_service_charge_count; //$parameters->status->posted_service_charge_count;
		$this->Log("Num SC Assessments: {$this->num_scs_assess}, Number SC Made: {$this->num_scs_payment}");

		// For account correction use, get the fund date
		$this->fund_date        = $parameters->fund_date;
		//$this->special_payments = $parameters->special_payments;
		
		$parameters->info->is_card_payment = $this->is_card_payment;

		if (!isset($parameters->info->fund_actual))
		{
		    $this->fund_amount = $parameters->status->initial_principal;
		}
		else
		{
		    $this->fund_amount = $parameters->info->fund_actual;
		}

		$this->posted_schedule  = $parameters->schedule;

		$this->last_debit_date  = $parameters->info->last_debit_date != null ? $parameters->info->last_debit_date : $this->fund_date;
		$this->principal_balance = $parameters->balance_info->principal_pending;
		$this->service_charge_balance = $parameters->balance_info->service_charge_pending;
		$this->fee_balance = $parameters->balance_info->fee_pending;
		
		// Set the rules.  Set the grace period to a default of 10 days if it isn't set already
		$this->rules = $parameters->rules;
		if (!isset($this->rules['grace_period'])) $this->rules['grace_period'] = 10;
		
		$info        = $parameters->info;
		$rules       = $parameters->rules;
		
		$start_date = (! empty($info->last_payment_date)) ? $info->last_payment_date : $this->fund_date;
		/**
		 * @TODO this needs to really be replaced with the libolution
		 * iterating PayDateCalculator.  Otherwise the constant below
		 * will just need to be increased for edge-case applications.
		 * I will not volunteer for this task until the DFAs get some
		 * refactoring time first (to at least have a common
		 * ancestor). [JustinF] [#32214]
		 */
		$date_list = Get_Date_List($info, $start_date, $rules, 60);
		
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

		/**
		 * This really needs to be defined somehow.  AFAIK, it's only set by arrange next payment
		 */
		$this->skip_first_interest_payment = $parameters->skip_first_interest_payment;

		$this->Log("First Event Date: " . $date_list['event'][0]);
		$this->dates = $date_list;

		/**
		 * Last date the last date we've calculated interest to.
		 * Rather than having special code throughout that finds this value
		 * if it hasn't been set, I'm doing it in the constructor.
		 */
		$paid_to = Interest_Calculator::getInterestPaidPrincipalAndDate($this->posted_schedule, FALSE, $this->rules, FALSE);
		$this->last_date = $paid_to['date'];
		$this->Log("Using a 'last_date' of {$this->last_date} to start calculating Interest from");

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

	protected function is_in_holding_status($parameters) 
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
	 * These are items like manual payments, arrangements,
	 * paydowns, etc.
	 *
	 * Update: [GF #22406] Changed method to only add special payments to the schedule
	 * if we're in the correct time period. Before it would add all of the special
	 * payments before anything else, causing future service charges to be 
	 * a bit off (and never corrected) if the special payment is removed in
	 * the future. Also removed a lot of redundancies (initializing variables
	 * and never using them, grabbing payment info from different sources, etc)
	 * [kb][12-22-2008]
	 */
	protected function reschedule_special_payments($parameters)
	{
		//First Handle Reattempts
		$application_id = $parameters->application_id;
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);	
		if(count($this->reattempts) > 0)
		{		
			$this->Log("Before Reattempts: Principal Balance: {$this->principal_balance}, SC Balance: {$this->service_charge_balance}");
	
			foreach($this->reattempts as $e)
			{
				if(($e->origin_id != $e->event_schedule_id) && ($e->origin_group_id < 0)) 
				{
					$this->Log("Rescheduling Re-Attempt, Event ID: {$e->event_schedule_id}");

					if($this->is_first_return($parameters) == 1)						
						$date_pair = $this->getFirstReturnDate($parameters);
					else
						$date_pair = $this->getAdditionalReturnDate($parameters);

					$e->date_event     = $date_pair['event'];
					$e->date_effective = $date_pair['effective'];
					
					// Add the event to the schedule
					$this->addEvent($e);
				}
			}
			$this->reattempts = array();
			$this->Log("After Reattempts: Principal Balance: {$this->principal_balance}, SC Balance: {$this->service_charge_balance}");
		}		
	
		//Then Special Payments
		if(count($this->special_payments))
		{
			//Flag used to shift the date if the payment is being
			//added on the same day as the first event
			$shift_dates = FALSE;
			
			/** 
 			 * Interate Through The Events
			 * If the event falls in the current time period, then add it to the list
			 * If the event falls on the next date in the current date list, then
			 * shift the dates.
			 */
			foreach($this->special_payments as $e)
			{
				$next_date = strtotime($this->dates['event'][0]);
				if ($e->is_shifted == '1' || $e->context == 'arrange_next')
				{
					/**
					 * Arrange Next Payment should shift the dates off until they're after
					 * the payment date so they are always the next payment.
					 */
					if($e->context == 'arrange_next')
					{
						/**
						 * Only shift the dates for Daily Interest  
						 * if the payment is earlier than our next due
						 * date, otherwise it'll just be added as an additional
						 * payment rather than a replacement.
						 */
						if($this->daily_interest_or_flat_fee($parameters) == 0 && ($next_date < strtotime($e->date_event)))
						{
							$next_date = strtotime($e->date_event);
							$this->shift_dates($parameters, $next_date);
						}
					}
					else
					{
						$this->dates['event'][0] = $e->date_event;
						$this->dates['effective'][0] =  $pdc->Get_Next_Business_Day($e->date_event);
						
						// $next_date = The new next due date
						$next_date = strtotime($this->dates['event'][1]);
					}
				}
				
				$date_event     = strtotime($e->date_event);
				$date_effective = strtotime($e->date_effective);
				
				$this->Log("Special payment type {$e->type}, reason {$e->REASON}, ESID {$e->event_schedule_id} on " . date("Y/m/d", $date_event) . " compare to " . date("Y/m/d",$next_date));

				$add_special_event = FALSE;
				if($date_event <= $next_date)
				{
					$add_special_event = TRUE;
					switch ($e->type)
					{
						case 'payment_service_chg':
						case 'card_payment_service_chg':
							if(empty($e->origin_id) && $date_event == $next_date)
							{
								$shift_dates = TRUE;
								$this->Log(__LINE__ . " :: The event date matches the first date in the date list... So we shift?");
							}
							else
							{
								$this->Log(__LINE__ . " :: The event date does not match the first date in the date list...");
							}
							break;
						case 'assess_service_chg':
							if (!$e->origin_id)
							{
								$amount = $this->principal_balance * $this->rules['interest'];
								$e->amounts = array(Event_Amount::MakeEventAmount('service_charge', $amount));
								$e->fee_amount = $amount;
								if($date_event == $next_date)
								{
									$shift_dates = TRUE;
								}
							}
							break;
						case 'repayment_principal':
						case 'card_repayment_principal':
							if($date_event == $next_date)
							{
								$shift_dates = TRUE;
							}
							break;
						case 'paydown':
						case 'card_paydown':
							/**
							 * This change was added as a fix for
							 * 10126 The first principal payment was
							 * happening too early, this code
							 * eliminated the next pay date and thusly
							 * ensured that the number of service
							 * charge assessments happening was low
							 * enough to allow for principal payments
							 * to happen on the expected paydate.
							 */
							/**
							 * This code is also VERY WRONG!  A
							 * paydown should not have ANY effect on
							 * when the next payment is scheduled for.
							 * I've added a real solution for 10126,
							 * which is to count the service charge
							 * payments, rather than assessments, as
							 * an assessment should happen every time
							 * there's a transaction that effects
							 * principal.  I've commented this code
							 * out to serve as an example, as this is
							 * easier than setting Bunce's head on a
							 * pike.  [W!-01-06-2009][#21972]
							 */
							//$this->dates['event'][0] = $e->date_event;
							//$this->dates['effective'][0] = $e->date_effective;	
							//$shift_dates = TRUE;
							if($this->rules['service_charge']['svc_charge_type'] === 'Daily' &&
							   $this->num_scs_exceeds_max($parameters) != 'above_max') //[#43855] only shift before principal payments start
							{
								$this->Log(__LINE__ . " :: Shifting dates after paydown (before principal payments begin) for Daily Interest...");
								$shift_dates = TRUE;
							}
							break;
						case 'credit_card':
						case 'moneygram':
						case 'money_order':
						case 'payment_arranged':
							$shift_dates = TRUE;
							break;
					}
				}

				if($add_special_event || $e->type == 'payment_arranged')
				{
					//Add the event and remove it from the special payments array
					$this->addEvent($e);
					// Remove the current event from the special payments array
					array_shift($this->special_payments);
				}
			}

			// Shift dates forward
			if($shift_dates === TRUE && $this->rules['service_charge']['svc_charge_type'] === 'Daily') 
			{
				$this->shift_dates($parameters, $next_date);
				$this->Log("Special Payments requires us to shift dates");
			}

		}
		
		return 1;
	}

	/**
	 * Adjusts the dates in the case that they are not past 
	 * the grace period defined by the business rules
	 */
	protected function adjust_for_grace_period($parameters) 
	{
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);
		$grace_period = $this->rules['grace_period'];

	        // Include the reaction due date for the grace period for react apps
	        if ($this->info->is_react){
	            $react_due_time = strtotime($this->rules['react_grace_date']);
	            $react_due_offset = $react_due_time - time();
	            $react_due_offset = ceil($react_due_offset / (24 * 60 * 60));

                    if ($react_due_offset > $grace_days) $grace_days = $react_due_offset;
                }
																						    
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
	
	protected function add_fixed_service_charge_assessment($parameters) 
	{
		/**
		 * Per GForge #17578 we may only ever charge one service charge
		 * for a CA Payday Loan. [BR]
		 */
		if($this->application)
		{
			if($this->application->getLoanType()->name_short === 'california_payday' && $this->num_scs_assess >= 1)
			{
				return 1;
			}
		}

		if($this->principal_balance == 0) return 1;

		$rate_calc = $this->application->getRateCalculator();
		$sc_amount = $rate_calc->round($parameters->rules['interest'] * $this->principal_balance);
		$this->Log("Principal: {$this->principal_balance}");
		$this->Log("Interest Rate: {$parameters->rules['interest']}");
		$this->Log("Service Charge Amount: {$sc_amount}");
		
		// If this is the first SC Assessment, use the fund date.
		if(	$this->num_scs_assess === 0
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
								'assess_service_chg', 'Scheduled Interest Payment');
		
		$this->addEvent($event);

		return 1;
	}
		
	protected function add_sc_payment($parameters) 
	{
		if($this->service_charge_balance > 0)
		{
			$amounts = array();
			$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$this->service_charge_balance);
			$event = Schedule_Event::MakeEvent($this->dates['event'][0], $this->dates['effective'][0],
							$amounts, $this->sc_payment,'Interest Payment');
			$this->addEvent($event);
			$this->Log("Added SC Payment {$this->service_charge_balance}, SC Balance: 0");
		}
		else
		{
			$this->Log("WARNING :: Attempted to create a service charge payment while service charge balance of {$this->service_charge_balance} > 0");
		}
	
		return 1;
	}

	protected function getFeeArray()
	{
		if ($this->is_card_payment)
		{
			$payment_fee_fail = 'payment_fee_card_fail';
		}
		else
		{
			$payment_fee_fail = 'payment_fee_ach_fail';
		}

		$fees = array();

		//Hey!  Look at that, retards!  It's possible to be paying into the fee bucket without using those specific
		//payment types!  [W!-2009-06-18][#34839]
		$fees['other'] = array('balance' => 0, 'pay_type' => 'none!  This should be allocated to other fees!');

		$fees['ach_fail'] = array('balance' => 0, 'pay_type' => $payment_fee_fail);
		//$fees['card_fail'] = array('balance' => 0, 'pay_type' => 'payment_fee_card_fail');
		$fees['delivery'] = array('balance' => 0, 'pay_type' => 'payment_fee_delivery');
		$fees['transfer'] = array('balance' => 0, 'pay_type' => 'payment_fee_transfer');
		$fees['lien']     = array('balance' => 0, 'pay_type' => 'payment_fee_lien');
		$fees['imga_fees']= array('balance' => 0, 'pay_type' => 'payment_imga_fee');
		return $fees;
	}

	protected function addFee($event_type, &$fees, $fee_amount)
	{
		switch($event_type)
		{
			case 'adjustment_internal_fees':
			case 'payment_imga_fee':
				$fees['imga_fees']['balance'] += $fee_amount;
				
				break;
			case 'assess_fee_ach_fail':
			case 'assess_fee_card_fail':
			case 'payment_fee_ach_fail':
			case 'payment_fee_card_fail':
			case 'writeoff_fee_ach_fail':
			case 'writeoff_fee_card_fail':
				$fees['ach_fail']['balance'] += $fee_amount;
				//$fees['card_fail']['balance'] += $fee_amount;
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
				//Really? if it doesn't match one of those specific types we should just throw it away and pretend 
				//That the payment doesn't exist?! "I'm sorry, ma'am, but your payment didn't go into our ACH fee bucket"
			default:
				//Storing unidentified fee into the 'other' bucket for later distribution.
				//$this->Log("Unrecognized fee!".var_export($e, TRUE));
				$fees['other']['balance'] += $fee_amount;
				//continue;
				break;
		}
	}
	
	protected function add_fee_payment($parameters)
	{
		$holidays = Fetch_Holiday_List();
		$pdc = new Pay_Date_Calc_3($holidays);
		$fees = $this->getFeeArray();
				
		//We need to be looking at not only the events that previously existed in the schedule, but new events we just created!
		$schedule = array_merge($parameters->schedule, $this->new_events);

		
		// This look will adjust the fee balances for each fee type.
		foreach($schedule as $e)
		{
			$fee_amount = 0;
			if($e->status != 'failed')
			{
				//Because these might not be events that exist in the schedule yet we're evaluating, we need to 
				//look at the individual event amounts.
				foreach ($e->amounts as $a)
				{
					if($a->event_amount_type === 'fee')
					{
						$fee_amount = $a->amount;
					}
				}
				$this->addFee($e->type, $fees, $fee_amount);
			}
		}
		$action_date = $this->dates['event'][0];
		$effective_date = $pdc->Get_Next_Business_Day($this->dates['event'][0]);
		
		//The goal is to allocate fee payments to fees that don't belong to a fee to fees so that we have 
		//an appropriate balance.  Possible scenarios are $25 ACH fee - $20 arranged next payment
		//The arranged next payment covers $20 of the ACH fee , without actually being the fee payment type.
		//Yes, I totally ripped this off from my DFA for MCC, because it's not retarded! [W!-2009-06-18][#34839]
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
		
		// If there are any fee balances, we'll go ahead and create the 
		// corresponding payments for them.
		foreach ($fees as $fee)
		{
			if($fee['balance'] > 0)
			{
				$this->Log("Adding event: {$fee['pay_type']} with amount {$fee['balance']}");
				$amounts = array();
				$amounts[] = Event_Amount::MakeEventAmount('fee', -$fee['balance']);

				$event = Schedule_Event::MakeEvent($action_date, $effective_date,
													$amounts, $fee['pay_type'], 'Fee Payment');
				$this->addEvent($event);
			}
		}
		
		return 1;
	}

	/**
	 * This is a funny method in that you only want to use it when you aren't making
	 * principal payments for a given payment period.
	 */
	protected function add_interest_payment($parameters)
	{
		// Note: $this->last_date is the date we calculate interest up to.
		// Below we set it to the next effective date in the date list and then
		// only calculate interest up to that period.
		$rate_calc = $this->application->getRateCalculator();

		$first_date = $this->last_date;
		$this->last_date = $this->dates['effective'][0];
		$days = Date_Util_1::dateDiff($first_date, $this->last_date);

		$amount = $rate_calc->calculateCharge($this->principal_balance, $first_date, $this->last_date);

		$first_date_display = date('m/d/Y', strtotime($first_date));
		$last_date_display = date('m/d/Y', strtotime($this->last_date));
		$comment = "Interest accrued from {$first_date_display} to {$last_date_display} ($days days)";
		$this->Log($comment." for $amount");			
		// Create the SC assessment
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('service_charge', $amount);
		$event = Schedule_Event::MakeEvent($this->dates['event'][0],$this->dates['event'][0],
					  					   $amounts, 'assess_service_chg', $comment);
		$this->addEvent($event);

		if($this->service_charge_balance <= 0) return 1;

		// Now create the SC payment event
		$amounts = array();
		if ($this->service_charge_balance > 0 && !$this->skip_first_interest_payment) 
		{
			$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$this->service_charge_balance);
			$event = Schedule_Event::MakeEvent($this->dates['event'][0],$this->dates['effective'][0],
										   	$amounts, $this->sc_payment, "Payment for $comment");
			$this->addEvent($event);
		}
		else
		{
			$this->skip_first_interest_payment = FALSE;
		}

		return 1;

	}
	
	protected function add_principal_payment($parameters) 
	{
		$princ_decrement = $this->get_principal_payment_amount($parameters);
		
		if($princ_decrement == 0) return 1;
		
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('principal', -$princ_decrement);
		$event = Schedule_Event::MakeEvent($this->dates['event'][0], $this->dates['effective'][0],
										     $amounts, $this->princ_payment,'Principal Payment');
		$this->addEvent($event);
		return 1;
	}

	protected function add_min_principal_payment($parameters) 
	{
		$percentage = $this->rules['principal_payment']['min_renew_prin_pmt_prcnt'];
		$rate_calc = $this->application->getRateCalculator();
		$payment_amount = $rate_calc->round($this->principal_balance * ($percentage / 100));
		$this->Log("Calculating amount of $payment_amount using ({$this->principal_balance}) * ({$percentage}/100)");
		
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('principal', -$payment_amount);
		/**
		 * IMPORTANT NOTE: The context of a manual renewal MUST be set to 'manual' instead
		 * of generated.  Complete schedule will check for the existence for a principal 
		 * payment with the context of manual to determine which renewal rules to use.
		 */
		$this->new_events[] = Schedule_Event::MakeEvent($this->dates['event'][0], $this->dates['effective'][0],
					  $amounts, $this->princ_payment,"Pay {$percentage}% of Principal Balance",'scheduled','manual');
		$this->Log("Adding minimum principal payment of {$payment_amount }");
		$this->principal_balance = bcsub($this->principal_balance, $payment_amount,2);
		
		return 1;
	}

	protected function payout($parameters) 
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
						 			$amounts, $this->payout_payment,"Pay full remaining balance of \${$total}");
			$this->addEvent($event);
			$this->Log("Adding payout of {$this->principal_balance }");
		}
		return 1;
	}

	/**
	 * Dev Note: Agean has their own version of this method
	 */
	protected function num_scs_exceeds_max($parameters) 
	{
		$max = intval($this->rules['service_charge']['max_svc_charge_only_pmts']);

		$this->Log("Max SC Only Payments: {$max}.  Current SC Payments: {$this->num_scs_payment}");

		if($this->num_scs_payment < $max) return 'less_than_max';
		if($this->num_scs_payment == $max) return 'at_max';
		if($this->num_scs_payment > $max) return 'above_max';

	}

	protected function first_new_event_is_scs($parameters)
	{
		//GForge [#29467] See if the first 'generated' new event is a service charge payment
		//(fixes [#27907][#25822] in the case where the first event is a paydown)
		if(count($this->new_events))
		{
			foreach($this->new_events as $e)
			{
				if(Is_Service_Charge_Payment($e) && $e->context != 'reattempt')
				{
					//only look at the first 'generated' event
					return 1;
				}
			}
		}
		return 0;
	}

	protected function has_assessment($parameters)
	{
		$current_date = $this->dates['event'][0];
		$this->Log("Checking for assessments already on {$current_date}.");
		
		foreach($this->new_events as $event)
		{
			//$this->Log("Comparing {$current_date} to {$event->date_event}.");
			if($event->date_event == $current_date)
			{
				foreach($event->amounts as $ea)
				{
					//$this->Log("Looking at amount {$ea->amount} of type {$ea->event_amount_type}.");
					if($ea->event_amount_type && $ea->amount > 0)
						return 1;
				}
			}
		}
		return 0;
	}
	
	protected function num_renew_scs_exceeds_max($parameters) 
	{
		$max = intval($this->rules['service_charge']['max_renew_svc_charge_only_pmts']);
		$this->Log("Max Renew SC Only Payments: $max.  Current Service Charge Payments: {$this->num_scs_payment}");
		if ($this->num_scs_payment < $max) return 0;
		if ($this->num_scs_payment >= $max) return 1;
	}

	/**
	 * Dev Note: Agean has their own version of this method
	 */
	protected function has_principal_balance($parameters) 
	{
		$this->Log("Current principal balance: \${$this->principal_balance}");
		return (($this->principal_balance > 0)? 1 : 0);
	}

	protected function has_registered_events($parameters) 
	{
		$this->Log("Number of registered events: {$parameters->status->num_registered_events}");
		if($parameters->status->num_registered_events > 0)	return 1;
		
		return 0;
	}
	
	protected function has_fees_or_service_charges($parameters) 
	{
		/**
		 * If skip_first_interest_payment is set (next payment
		 * arrangement) avoid adding extra (unwanted) charges in the
		 * first payment period, even if the amount selected is less
		 * than the service charge balance.  Let the charges accrue
		 * for the next period. [#27879]
		 */
		if($this->skip_first_interest_payment) return 0;
		
		return (($this->fee_balance > 0 || $this->service_charge_balance > 0) ? 1 : 0);
	}	

	protected function has_fees_balance($parameters) 
	{
		return (($this->fee_balance > 0) ? 1 : 0);
	}

	protected function has_service_charge_balance($parameters) 
	{
		return (($this->service_charge_balance > 0) ? 1 : 0);
	}

	protected function has_registered_fees($parameters) 
	{
		foreach ($parameters->schedule as $event) 
		{
			if (($event->status != 'scheduled') && ($event->fee_amount < 0)) return 1;
		}
		return 0;
	}

	protected function daily_interest_or_flat_fee($parameters)
	{
		// Return 0 for Daily Interest, or 1 for Fixed Interest
		if($this->rules['service_charge']['svc_charge_type'] === 'Daily')
		{
			return 0;
		}
		
		return 1;
	}

	protected function get_principal_payment_amount($parameters)
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
	
	protected function use_manual_renewal_rules($parameters)
	{
		return ($parameters->status->has_manual_renewals === TRUE) ? 1 : 0;
	}
	
	/**
	 * Shifts the date array to the next set of dates, or optionally to the 
	 * date after the next_date
	 *
	 * @param Array $parameters
	 * @param int $next_date - Unix Timestamp
	 * @return int 1
	 */
	protected function shift_dates($parameters, $next_date = NULL)
	{
		/**
		 * Added this to not shift dates for JiffyCash or other accounts that
		 * are supposed to immediately payout. [Mantis:11611]
		 */
		if($this->is_fund_payout($parameters) === 1)
		{
			$this->Log('Payout account, not shifting dates');
		}
		else
		{
			/**
			 * Reset this after we've shifted our dates
			 */
			if($this->skip_first_interest_payment === TRUE) $this->skip_first_interest_payment = FALSE;
			
			if(empty($next_date))
			{
				$this->Log("Shifting dates one time\n");
				array_shift($this->dates['event']);
				array_shift($this->dates['effective']);
			}
			else
			{
				$this->Log("+++ Comparing {$this->dates['event'][0]} <= " . date('Y-m-d', $next_date));
				
				while(count($this->dates['event']) > 1 &&  strtotime($this->dates['event'][0]) <= $next_date)
				{
					$this->Log("{$this->dates['event'][0]} <= " . date('Y-m-d', $next_date) . " :: Shifting dates\n");
					array_shift($this->dates['event']);
					array_shift($this->dates['effective']);
				}
			}
			$this->Log("Shifting Dates, next action date: {$this->dates['event'][0]}");
		}
		return 1;
	}

	/**
	 * Determines whether or not the account should pay off it's
	 * entire balance on the customer's first due date.
	 */
	protected function is_fund_payout($parameters)
	{
		if(isset($this->rules['principal_payment']['principal_payment_percentage'])
		&& $this->rules['principal_payment']['principal_payment_percentage'] == '100' || $parameters->fund_method == 'Fund_Payout')
		{
			return 1;
		}
		
		return 0;
	}

	/*
	 * Determine if this is the first return or not
	 */
	protected function is_first_return($parameters) 
	{
		$count = 0;
		foreach ($parameters->schedule as $e) 
		{
			if ($e->type == 'assess_fee_ach_fail' || $e->type == 'assess_fee_card_fail')
				$count++;
		}
		return $count == 1 ? 1 : 0;
	}

	/**
	 * Situation: We are in a "Held" status, meaning the account is in a status that should not 
	 * transition until an expiration period or some sort of human intervention takes place.
	 * We should not attempt to adjust the account at this time.
	 */
	protected function State_2($parameters)
	{
		return Array();
	}

	/**
	 * Situation: Nothing exists for this person.
	 * For now, error out. We'll decide on this later.
	 */
	protected function State_3($parameters) 
	{ 
		throw new Exception("No existing registered events found."); 
	}
	
	/**
	 * Return out the schedule we've created
	 */
	protected function State_24($parameters) 
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

	protected function addEvent($event)
	{
		// Last Payment / Disbursement?  $this->last_date
		// Calculate Interest up to last completed item? Check.
		$type = $event->type;
		
		$principal = NULL;
		$principal_balance = $this->principal_balance;
		$total = 0;
		
		$this->Log("Adding event of type '{$type}' for '{$event->date_event}'");
		
		//[#29467] calc the payments on 'service_charge' debits
		if(Is_Service_Charge_Payment($event))
		{
			$this->num_scs_payment++;
		}

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
						//Check dis out.  It is possible for a customer, using the 'arrange_next_payment' option, to create a schedule 
						//where there's a service charge payment that's NOT the 'payment_service_chg transaction type. Because of this, a schedule can be thrown
						//out of wack, since that SC payment was not registering as an SC payment.  This should remedy it.
						//if($event->context == 'manual' && $type != 'payment_service_chg' && $ea->amount < 0)
						if($event->context == 'manual'
						   && !in_array($type, array('payment_service_chg','card_payment_service_chg'))
						   && $ea->amount < 0
						)
						{
							$this->num_scs_payment++;
						}
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
			if(! empty($principal) || $event->context == 'arrange_next')
			{
				$this->Log("Principal payment amt = $principal, event context is {$event->context}");
				if(strtotime($event->date_effective) > strtotime($this->last_date))
				{
					$this->Log("Date effective of {$event->date_effective} > is greater than the last assessment date of {$this->last_date}, adding an interest assessment");
   					$this->Log("Getting interest for $principal_balance for period $this->last_date to $event->date_effective");
					$rate_calc = $this->application->getRateCalculator();
					$interest = $rate_calc->calculateCharge($principal_balance, $this->last_date, $event->date_effective);
					//$interest = Interest_Calculator::calculateDailyInterest($this->rules, $principal_balance, $this->last_date, $event->date_effective);
					$this->addInterestAssessment($interest, $this->last_date, $event->date_effective, $event->date_event);
					$this->last_date = $event->date_effective;
				}
				$this->Log("Comparing event date of {$event->date_event} to next event date of {$this->dates['event'][0]} to determine whether we should add a payment");
				if(strtotime($event->date_event) === strtotime($this->dates['event'][0]))
				{
					$this->Log("Dates Match, Interest Balance: {$this->service_charge_balance}, skip first interest payment flag: " . var_export($this->skip_first_interest_payment, true));
					if ($this->service_charge_balance > 0 && !$this->skip_first_interest_payment)
					{
						$sc_amounts = array();
						$sc_amounts[] = Event_Amount::MakeEventAmount('service_charge', -$this->service_charge_balance);
						$sc_event = Schedule_Event::MakeEvent($this->dates['event'][0],$this->dates['effective'][0],
															   $sc_amounts, 'payment_service_chg', "Interest Payment");
						$this->new_events[] = $sc_event;
						$this->num_scs_payment++; //GForge [#25305] make sure number of interest payments is correct
						$this->service_charge_balance = 0;
					}
					else 
					{
						$this->skip_first_interest_payment =  FALSE;
					}
				}
			}
		}

		if ($total != 0) $this->new_events[] = $event;
	}

	public function addInterestAssessment($amount, $first_date, $last_date, $action_date)
	{
		$this->Log("Attempting to add Interest of $amount");
		if($amount <> 0)
		{
			$days = Date_Util_1::dateDiff($first_date, $last_date);
			
			$first_date_display = date('m/d/Y', strtotime($first_date));
			$last_date_display = date('m/d/Y', strtotime($last_date));
			$comment = "Interest accrued from {$first_date_display} to {$last_date_display} ($days days)";
			
			$amounts = array();
			$amounts[] = Event_Amount::MakeEventAmount('service_charge', $amount);
			$event = Schedule_Event::MakeEvent($action_date, $action_date, $amounts, 'assess_service_chg', $comment);
			$this->addEvent($event);
		}
	}
		
}

?>
