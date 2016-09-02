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
	public $num_scs_assessed;
	public $scs_made;
	public $new_events;
	public $special_payments;
	public $dates;
	public $fund_amount;
	private $last_date_effective;
	
	const NUM_STATES = 7;

	public function __construct() 
	{
		for ($i = 0; $i < self::NUM_STATES; $i++) $this->states[$i] = $i;
		$this->initial_state = 0;
		$this->final_states = array(6);
		$this->tr_functions = array(
						0 => 'num_scs_exceeds_max',
						1 => 'add_min_principal_payment',
					    2 => 'add_interest_payment',
					    3 => 'reschedule_payments',
					    4 => 'add_interest_payment',
					    5 => 'payout',
					   );

		$this->transitions = array(
					   0 => array( 0 => 2, 1 => 1),
					   1 => array( 1 => 2),
					   2 => array( 1 => 3),
					   3 => array( 1 => 4),
					   4 => array( 1 => 5),
					   5 => array( 1 => 6)); // Commit changes

		parent::__construct();
	}

	public function run($parameters) 
	{
		$this->num_scs_assessed = $parameters->num_scs_assessed;

		$this->fund_amount  = $parameters->fund_amount;
		$this->scs_made = $parameters->status->posted_service_charge_count;
		$this->fee_balance = $parameters->status->running_fees;
		$this->principal_balance = $parameters->status->posted_and_pending_principal;
		$this->special_payments = $parameters->special_payments;
		$this->last_date_effective = NULL;
		
		$info        = $parameters->info;
		$rules       = $parameters->rules;
		
		// If the last payment date is set, use it as the start date
		$start_date = (! empty($info->last_payment_date)) ? $info->last_payment_date : $parameters->fund_date;
		$this->last_date_effective = (! empty($info->last_payment_date)) ? $info->last_payment_date : NULL;
		
		$dates = Get_Date_List($info, $start_date, $rules, 20);
		// Shift dates forward
		//array_shift($dates['event']);
		//array_shift($dates['effective']);
		
		$this->dates = $dates;
		
		//$this->Log("Dates: " . var_export($dates, TRUE));
		
		/**
		 * Hack the Rules!
		 */
		//$this->rules['principal_payment']['principal_payment_type'] = 'Fixed'; // Fixed or Percentage
		//$this->rules['principal_payment']['principal_payment_amount'] = 100;
		//$this->rules['principal_payment']['principal_payment_percentage'] = 100;
		//$this->rules['service_charge']['svc_charge_type'] = 'Fixed'; // Fixed or Daily
		//$this->rules['service_charge']['max_svc_charge_only_pmts'] = 0;

		// This needs to be configured
		$this->rules['principal_payment']['min_renew_prin_pmt_prcnt'] = 10;
		
		return parent::run($parameters);

	}

	// Handle any previously made payments/arrangements that exist
	// Before the first date in the date list
	public function reschedule_payments($parameters) 
	{
		$events = $this->special_payments;

		// Flag to be used to shift the dates
		// if for some reason we insert a payment
		// on the same date as the first event.
		$shift_dates = false;
		
		// Iterate through the events.  If the event exists before or up to
		// the first date in our current date list, then add the event to the
		// schedule and remove it from the special payments list
		foreach($this->special_payments as $e)
		{
			if(strtotime($e->date_event) <= strtotime($this->dates['event'][0])) 
			{
				// If it's a reattempt, move the date to the next paydate.
				if(! empty($e->origin_id)) {
					$e->date_event     = $this->dates['event'][0];
					$e->date_effective = $this->dates['effective'][0];
				}
				// Add the event to the schedule
				$this->new_schedule[] = $e;
				$this->Log("Adding event of type '{$e->type}' for '{$e->date_event}'");

				switch ($e->type)
				{
					case 'payment_service_chg':
						$this->scs_made++;
						if(empty($e->origin_id) && strtotime($e->date_event) == strtotime($this->dates['event'][0]))
						{
							$shift_dates = true;
						}
						break;
					case 'assess_service_chg':
						$this->num_scs_assessed++;
						if (!$e->origin_id)
						{
							$amount = $this->principal_balance * $this->rules['interest'];
							$e->amounts = array(Event_Amount::MakeEventAmount('service_charge', $amount));
							$e->fee_amount = $amount;
							if(strtotime($e->date_event) == strtotime($this->dates['event'][0]))
							{
								$shift_dates = true;
							}
						}
						break;
					case 'repayment_principal':
						if(strtotime($e->date_event) == strtotime($this->dates['event'][0]))
						{
							$shift_dates = true;
						}
						break;
				}

				// Calculate our balances after the new scheduled payment				
				if($e->principal_amount != 0) {
					$this->principal_balance += $e->principal_amount;
					$this->Log("Added principal payment for: {$e->principal_amount}, Principal Balance: {$this->principal_balance}");
				}
				if($e->fee_amount != 0) {
					$this->fee_balance += $e->fee_amount;
					$this->Log("Added fee payment for: {$e->fee_amount}, Fee Balance: {$this->fee_balance}");
				}
				
				// Remove the current event from the special payments array
				array_shift($this->special_payments);
			}
		}
		
		if($shift_dates === true) {
			// Shift dates forward
			array_shift($this->dates['event']);
			array_shift($this->dates['effective']);
			$this->Log("Special Payments requires us to shift dates");
		}
		
		return 1;
	}

	public function add_interest_payment($parameters)
	{
		require_once("ecash_common/ecash_api/interest_calculator.class.php");

		$rules = $parameters->rules;

		if($this->last_date_effective === NULL)
		{
			$first_date = $parameters->pdc->Get_Next_Business_Day($parameters->fund_date);
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
		$this->new_events[] = Schedule_Event::MakeEvent($this->dates['event'][0],
														$this->dates['event'][0],
					  $amounts, 'assess_service_chg', $comment);

		// Now create the SC payment event
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('service_charge', -$amount);
		$this->new_events[] = Schedule_Event::MakeEvent($this->dates['event'][0],
														$this->dates['effective'][0],
					  $amounts, 'payment_service_chg', "Payment for $comment");

		return 1;

	}

	// Payout the entire principal balance
	public function payout($parameters) 
	{
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('principal', -$this->principal_balance);
		$this->new_events[] = Schedule_Event::MakeEvent($this->dates['event'][0], $this->dates['effective'][0],
					  $amounts, 'repayment_principal','Pay Full Principal Balance');
		$this->Log("Adding payout of {$this->principal_balance }");
		return 1;
	}

	public function add_min_principal_payment($parameters) 
	{
		$percentage = $this->rules['principal_payment']['min_renew_prin_pmt_prcnt'];
		$payment_amount = ($this->principal_balance / $percentage);
		
		$amounts = array();
		$amounts[] = Event_Amount::MakeEventAmount('principal', -$payment_amount);
		/**
		 * IMPORTANT NOTE: The context of a manual renewal MUST be set to 'manual' instead
		 * of generated.  Complete schedule will check for the existence for a principal 
		 * payment with the context of manual to determine which renewal rules to use.
		 */
		$this->new_events[] = Schedule_Event::MakeEvent($this->dates['event'][0], $this->dates['effective'][0],
					  $amounts, 'repayment_principal','Pay 10% of Principal Balance','scheduled','manual');
		$this->Log("Adding minimum principal payment of {$payment_amount }");
		$this->principal_balance -= $payment_amount;
		
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

	// If over Max SVC Charge Payments, we're supposed to charge 10%
	function num_scs_exceeds_max($parameters) 
	{
		/**
		 * Note: We use a different 'max service charge only payments' rule for
		 * manual renewals.  This is because the default may be to pay out immediately
		 * without any service charge only payments.  If the loan is manually renewed, 
		 * a different set of rules applies.  That's where this new rule comes in.
		 */ 
		$max = intval($this->rules['service_charge']['max_renew_svc_charge_only_pmts']);
		$this->Log("Max SC Payments: $max.  Current SC's: {$this->num_scs_assessed}");
		if ($this->num_scs_assessed <= $max) return 0;
		if ($this->num_scs_assessed > $max) return 1;
		return 0; // In case something is jacked rule-wise
	}

	
}

?>
